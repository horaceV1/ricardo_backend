<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

/**
 * API Controller for submission messages.
 */
class SubmissionMessagesApiController extends ControllerBase {

  /**
   * Get messages for a submission.
   *
   * GET /api/submission/{submission_id}/messages
   */
  public function getMessages($submission_id, Request $request) {
    $connection = \Drupal::database();
    $current_user = \Drupal::currentUser();

    // Check access
    $submission = $connection->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to', 'email'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchObject();

    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    // Verify access (assigned staff or admin)
    // Note: Para clientes anónimos via Next.js, considerar usar email + token de acesso
    $is_assigned = $current_user->id() == $submission->assigned_to;
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_assigned && !$is_admin && $current_user->isAnonymous()) {
      // Permitir acesso anónimo se tiver token válido (implementar depois)
      // Por agora, bloquear
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // Get messages
    $messages = $connection->select('dynamic_form_messages', 'm')
      ->fields('m')
      ->condition('submission_id', $submission_id)
      ->orderBy('created', 'ASC')
      ->execute()
      ->fetchAll();

    $messages_data = [];
    foreach ($messages as $msg) {
      $sender = User::load($msg->user_id);
      
      $file_data = null;
      if ($msg->file_id) {
        $file = File::load($msg->file_id);
        if ($file) {
          $file_data = [
            'id' => $file->id(),
            'filename' => $file->getFilename(),
            'url' => file_create_url($file->getFileUri()),
            'size' => $file->getSize(),
          ];
        }
      }

      $messages_data[] = [
        'id' => $msg->id,
        'sender_id' => $msg->user_id,
        'sender_name' => $sender ? $sender->getDisplayName() : 'Unknown',
        'message' => $msg->message,
        'file' => $file_data,
        'created' => $msg->created,
        'is_current_user' => $msg->user_id == $current_user->id(),
      ];
    }

    return new JsonResponse([
      'submission_id' => $submission_id,
      'messages' => $messages_data,
    ]);
  }

  /**
   * Send a new message.
   *
   * POST /api/submission/{submission_id}/messages
   * Body: { message: string, file_id?: number }
   */
  public function sendMessage($submission_id, Request $request) {
    $connection = \Drupal::database();
    $current_user = \Drupal::currentUser();

    // Check access
    $submission = $connection->select('dynamic_form_submission', 's')
      ->fields('s', ['user_id', 'assigned_to', 'email'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchObject();

    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    $is_owner = $current_user->id() == $submission->user_id;
    $is_assigned = $current_user->id() == $submission->assigned_to;
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_owner && !$is_assigned && !$is_admin) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // Get request data
    $data = json_decode($request->getContent(), TRUE);
    $message = $data['message'] ?? '';
    $file_id = $data['file_id'] ?? NULL;

    if (empty($message)) {
      return new JsonResponse(['error' => 'Message cannot be empty'], 400);
    }

    // Make file permanent if provided
    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    // Insert message
    $message_id = $connection->insert('dynamic_form_messages')
      ->fields([
        'submission_id' => $submission_id,
        'user_id' => $current_user->id(),
        'message' => $message,
        'file_id' => $file_id,
        'created' => time(),
      ])
      ->execute();

    // Send email notification
    $this->sendMessageNotification($submission_id, $current_user->id(), $message, $submission);

    // Return the created message
    $sender = User::load($current_user->id());
    $file_data = null;
    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file_data = [
          'id' => $file->id(),
          'filename' => $file->getFilename(),
          'url' => file_create_url($file->getFileUri()),
          'size' => $file->getSize(),
        ];
      }
    }

    return new JsonResponse([
      'success' => true,
      'message' => [
        'id' => $message_id,
        'sender_id' => $current_user->id(),
        'sender_name' => $sender->getDisplayName(),
        'message' => $message,
        'file' => $file_data,
        'created' => time(),
        'is_current_user' => true,
      ],
    ]);
  }

  /**
   * Upload file for message.
   *
   * POST /api/submission/message/upload
   * FormData: file
   */
  public function uploadFile(Request $request) {
    $current_user = \Drupal::currentUser();
    
    if (!$current_user->isAuthenticated()) {
      return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    $files = $request->files->get('file');
    if (!$files) {
      return new JsonResponse(['error' => 'No file provided'], 400);
    }

    // Validate file type
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($files->getClientOriginalName(), PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
      return new JsonResponse(['error' => 'File type not allowed'], 400);
    }

    // Validate file size (10MB)
    if ($files->getSize() > 10 * 1024 * 1024) {
      return new JsonResponse(['error' => 'File too large (max 10MB)'], 400);
    }

    // Save file
    $directory = 'private://submission_messages';
    \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

    $file = file_save_data(
      file_get_contents($files->getPathname()),
      $directory . '/' . $files->getClientOriginalName(),
      \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME
    );

    if ($file) {
      return new JsonResponse([
        'success' => true,
        'file_id' => $file->id(),
        'filename' => $file->getFilename(),
        'size' => $file->getSize(),
      ]);
    }

    return new JsonResponse(['error' => 'Failed to upload file'], 500);
  }

  /**
   * Send email notification.
   */
  protected function sendMessageNotification($submission_id, $sender_id, $message, $submission) {
    $sender = User::load($sender_id);
    $mailManager = \Drupal::service('plugin.manager.mail');
    
    // Determine recipient
    $is_staff = \Drupal::service('current_user')->hasPermission('administer site configuration');
    
    if ($is_staff) {
      // Staff sent message, notify applicant
      $to = $submission->email;
    } else {
      // Applicant sent message, notify assigned staff
      if ($submission->assigned_to) {
        $assigned_user = User::load($submission->assigned_to);
        $to = $assigned_user->getEmail();
      } else {
        $to = \Drupal::config('system.site')->get('mail');
      }
    }

    if ($to) {
      $params = [
        'sender_name' => $sender->getDisplayName(),
        'message' => $message,
        'submission_id' => $submission_id,
      ];
      
      $mailManager->mail('formulario_candidatura_dinamico', 'new_message', $to, 'pt-pt', $params, NULL, TRUE);
    }
  }

}
