<?php

namespace Drupal\submission_assignment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for assignment and messaging (Next.js frontend integration).
 */
class AssignmentApiController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an AssignmentApiController.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * GET /api/submission/{submission_id}/assignment
   *
   * Returns assignment info for a submission.
   */
  public function getAssignment($submission_id) {
    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    $record = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to', 'assigned_at', 'assigned_by'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchObject();

    $worker_data = NULL;
    if (!empty($record->assigned_to)) {
      $worker = User::load($record->assigned_to);
      if ($worker) {
        $worker_data = [
          'uid' => (int) $worker->id(),
          'name' => $worker->getDisplayName(),
          'email' => $worker->getEmail(),
        ];
      }
    }

    $assigned_by_data = NULL;
    if (!empty($record->assigned_by)) {
      $admin = User::load($record->assigned_by);
      if ($admin) {
        $assigned_by_data = [
          'uid' => (int) $admin->id(),
          'name' => $admin->getDisplayName(),
        ];
      }
    }

    return new JsonResponse([
      'submission_id' => (int) $submission_id,
      'assigned_to' => $worker_data,
      'assigned_by' => $assigned_by_data,
      'assigned_at' => $record->assigned_at ? (int) $record->assigned_at : NULL,
    ]);
  }

  /**
   * POST /api/submission/{submission_id}/assign
   *
   * Assign a worker to a submission.
   * Body: { "worker_uid": 123 } or { "worker_uid": null } to unassign.
   */
  public function assignWorker($submission_id, Request $request) {
    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    $worker_uid = $data['worker_uid'] ?? NULL;

    // Validate worker exists if provided.
    if ($worker_uid) {
      $worker = User::load($worker_uid);
      if (!$worker || !$worker->isActive()) {
        return new JsonResponse(['error' => 'Worker user not found or inactive'], 400);
      }
    }

    $current_user = $this->currentUser();

    // Update assignment.
    $this->database->update('dynamic_form_submission')
      ->fields([
        'assigned_to' => $worker_uid ?: NULL,
        'assigned_at' => $worker_uid ? \Drupal::time()->getRequestTime() : NULL,
        'assigned_by' => $worker_uid ? $current_user->id() : NULL,
      ])
      ->condition('id', $submission_id)
      ->execute();

    // Log the assignment.
    $this->database->insert('submission_assignment_log')
      ->fields([
        'submission_id' => $submission_id,
        'assigned_to' => $worker_uid ?: NULL,
        'assigned_by' => $current_user->id(),
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    // Send notification email.
    if ($worker_uid) {
      $worker = User::load($worker_uid);
      $form_entity = DynamicForm::load($submission->getFormId());

      $params = [
        'submission_id' => $submission_id,
        'worker_name' => $worker->getDisplayName(),
        'form_name' => $form_entity ? $form_entity->label() : 'N/A',
        'client_email' => $submission->getEmail() ?: 'N/A',
      ];

      \Drupal::service('plugin.manager.mail')->mail(
        'submission_assignment',
        'assignment_notification',
        $worker->getEmail(),
        'pt-pt',
        $params,
        NULL,
        TRUE
      );
    }

    return new JsonResponse([
      'success' => TRUE,
      'submission_id' => (int) $submission_id,
      'assigned_to' => $worker_uid ? (int) $worker_uid : NULL,
      'message' => $worker_uid
        ? 'Worker assigned successfully'
        : 'Assignment removed successfully',
    ]);
  }

  /**
   * GET /api/submission/{submission_id}/messages
   *
   * Returns messages for a submission.
   */
  public function getMessages($submission_id, Request $request) {
    $current_user = $this->currentUser();

    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    // Access check.
    $assigned_to = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchField();

    $is_assigned = ($current_user->id() == $assigned_to);
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_assigned && !$is_admin) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // Fetch messages.
    $messages = $this->database->select('dynamic_form_messages', 'm')
      ->fields('m')
      ->condition('submission_id', $submission_id)
      ->orderBy('created', 'ASC')
      ->execute()
      ->fetchAll();

    $messages_data = [];
    foreach ($messages as $msg) {
      $sender = User::load($msg->user_id);

      $file_data = NULL;
      if ($msg->file_id) {
        $file = File::load($msg->file_id);
        if ($file) {
          $file_data = [
            'id' => (int) $file->id(),
            'filename' => $file->getFilename(),
            'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
            'size' => (int) $file->getSize(),
          ];
        }
      }

      $messages_data[] = [
        'id' => (int) $msg->id,
        'sender_id' => (int) $msg->user_id,
        'sender_name' => $sender ? $sender->getDisplayName() : 'Unknown',
        'message' => $msg->message,
        'file' => $file_data,
        'created' => (int) $msg->created,
        'is_current_user' => ($msg->user_id == $current_user->id()),
      ];
    }

    return new JsonResponse([
      'submission_id' => (int) $submission_id,
      'messages' => $messages_data,
      'count' => count($messages_data),
    ]);
  }

  /**
   * POST /api/submission/{submission_id}/messages
   *
   * Send a message on a submission.
   * Body: { "message": "text", "file_id": 123 (optional) }
   */
  public function sendMessage($submission_id, Request $request) {
    $current_user = $this->currentUser();

    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    // Access check.
    $assigned_to = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchField();

    $is_assigned = ($current_user->id() == $assigned_to);
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_assigned && !$is_admin) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    $data = json_decode($request->getContent(), TRUE);
    $message = $data['message'] ?? '';
    $file_id = $data['file_id'] ?? NULL;

    if (empty(trim($message))) {
      return new JsonResponse(['error' => 'Message cannot be empty'], 400);
    }

    // Make file permanent if provided.
    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    // Insert message.
    $message_id = $this->database->insert('dynamic_form_messages')
      ->fields([
        'submission_id' => $submission_id,
        'user_id' => $current_user->id(),
        'message' => $message,
        'file_id' => $file_id,
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    // Send email notification.
    $sender = User::load($current_user->id());
    $is_staff = $current_user->hasPermission('manage assigned submissions') || $is_admin;

    $to = NULL;
    if ($is_staff) {
      $to = $submission->getEmail();
    }
    elseif ($assigned_to) {
      $worker = User::load($assigned_to);
      $to = $worker ? $worker->getEmail() : NULL;
    }

    if ($to) {
      $params = [
        'sender_name' => $sender->getDisplayName(),
        'message' => $message,
        'submission_id' => $submission_id,
      ];

      \Drupal::service('plugin.manager.mail')->mail(
        'submission_assignment',
        'new_message',
        $to,
        'pt-pt',
        $params,
        NULL,
        TRUE
      );
    }

    // Build response.
    $file_data = NULL;
    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file_data = [
          'id' => (int) $file->id(),
          'filename' => $file->getFilename(),
          'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
          'size' => (int) $file->getSize(),
        ];
      }
    }

    return new JsonResponse([
      'success' => TRUE,
      'message' => [
        'id' => (int) $message_id,
        'sender_id' => (int) $current_user->id(),
        'sender_name' => $sender->getDisplayName(),
        'message' => $message,
        'file' => $file_data,
        'created' => \Drupal::time()->getRequestTime(),
        'is_current_user' => TRUE,
      ],
    ]);
  }

  /**
   * POST /api/submission/message/upload
   *
   * Upload a file for a message attachment.
   */
  public function uploadFile(Request $request) {
    $current_user = $this->currentUser();

    if (!$current_user->isAuthenticated()) {
      return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    $files = $request->files->get('file');
    if (!$files) {
      return new JsonResponse(['error' => 'No file provided'], 400);
    }

    // Validate file extension.
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($files->getClientOriginalName(), PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions)) {
      return new JsonResponse(['error' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_extensions)], 400);
    }

    // Validate file size (10MB max).
    if ($files->getSize() > 10 * 1024 * 1024) {
      return new JsonResponse(['error' => 'File too large (max 10MB)'], 400);
    }

    // Save file.
    $directory = 'private://submission_messages';
    \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

    $file = \Drupal::service('file.repository')->writeData(
      file_get_contents($files->getPathname()),
      $directory . '/' . $files->getClientOriginalName(),
      \Drupal\Core\File\FileExists::Rename
    );

    if ($file) {
      return new JsonResponse([
        'success' => TRUE,
        'file_id' => (int) $file->id(),
        'filename' => $file->getFilename(),
        'size' => (int) $file->getSize(),
      ]);
    }

    return new JsonResponse(['error' => 'Failed to upload file'], 500);
  }

  /**
   * GET /api/employee/dashboard
   *
   * Returns dashboard data for the authenticated worker.
   */
  public function employeeDashboard() {
    $current_user_id = $this->currentUser()->id();

    // Get assigned submissions.
    $query = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['id', 'form_id', 'email', 'created', 'assigned_at'])
      ->condition('assigned_to', $current_user_id)
      ->orderBy('created', 'DESC');

    $results = $query->execute()->fetchAll();

    $submissions = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'denied' => 0];

    foreach ($results as $record) {
      $submission = DynamicFormSubmission::load($record->id);
      if (!$submission) {
        continue;
      }

      $approval_status = $submission->getApprovalStatus();
      $stats['total']++;
      if (isset($stats[$approval_status])) {
        $stats[$approval_status]++;
      }

      $form_entity = DynamicForm::load($record->form_id);

      // Count messages.
      $msg_count = $this->database->select('dynamic_form_messages', 'm')
        ->condition('submission_id', $record->id)
        ->countQuery()
        ->execute()
        ->fetchField();

      $submissions[] = [
        'id' => (int) $record->id,
        'form_id' => $record->form_id,
        'form_name' => $form_entity ? $form_entity->label() : $record->form_id,
        'client_email' => $record->email ?: NULL,
        'approval_status' => $approval_status,
        'created' => (int) $record->created,
        'assigned_at' => $record->assigned_at ? (int) $record->assigned_at : NULL,
        'message_count' => (int) $msg_count,
      ];
    }

    return new JsonResponse([
      'stats' => $stats,
      'submissions' => $submissions,
    ]);
  }

  /**
   * GET /api/workers
   *
   * Returns a list of users who can be assigned to submissions.
   */
  public function getWorkersList() {
    $users_query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->accessCheck(TRUE);
    $user_ids = $users_query->execute();

    $workers = [];
    foreach (User::loadMultiple($user_ids) as $user) {
      if ($user->hasPermission('manage assigned submissions') || $user->hasPermission('administer site configuration') || $user->hasRole('tecnico')) {
        $workers[] = [
          'uid' => (int) $user->id(),
          'name' => $user->getDisplayName(),
          'email' => $user->getEmail(),
        ];
      }
    }

    return new JsonResponse([
      'workers' => $workers,
      'count' => count($workers),
    ]);
  }

}
