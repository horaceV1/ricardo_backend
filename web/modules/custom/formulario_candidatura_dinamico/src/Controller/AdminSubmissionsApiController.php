<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;

/**
 * API controller for admin/tecnico submission management.
 */
class AdminSubmissionsApiController extends ControllerBase {

  /**
   * GET /api/admin/submissions
   *
   * Returns all form submissions for admin/tecnico users.
   * Supports query params: ?form_id=X&status=pending&search=email
   */
  public function getAllSubmissions(Request $request) {
    $current_user = \Drupal::currentUser();

    // Permission check: must be admin or tecnico.
    $is_admin = $current_user->hasPermission('administer site configuration');
    $is_tecnico = in_array('tecnico', $current_user->getRoles());

    if (!$is_admin && !$is_tecnico) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // If tecnico (not admin), get only submissions assigned to this user.
    $assigned_submission_ids = [];
    if ($is_tecnico && !$is_admin) {
      $connection = \Drupal::database();
      $assigned_submission_ids = $connection->select('dynamic_form_submission', 's')
        ->fields('s', ['id'])
        ->condition('assigned_to', $current_user->id())
        ->execute()
        ->fetchCol();

      // If no submissions assigned, return empty.
      if (empty($assigned_submission_ids)) {
        return new JsonResponse([
          'submissions' => [],
          'total' => 0,
          'available_forms' => [],
        ]);
      }
    }

    $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');

    // Build query conditions from request params.
    $properties = [];
    $form_id_filter = $request->query->get('form_id');
    $status_filter = $request->query->get('status');
    $search = $request->query->get('search');

    if ($form_id_filter) {
      $properties['form_id'] = $form_id_filter;
    }

    $submissions = $storage->loadByProperties($properties);

    // Filter to only assigned submissions for tecnico users.
    if ($is_tecnico && !$is_admin) {
      $submissions = array_filter($submissions, function ($submission) use ($assigned_submission_ids) {
        return in_array($submission->id(), $assigned_submission_ids);
      });
    }

    // Load all form definitions once for efficiency.
    $forms_cache = [];

    $result = [];
    foreach ($submissions as $submission) {
      /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */

      $approval_status = $submission->getApprovalStatus();

      // Filter by status if specified.
      if ($status_filter && $approval_status !== $status_filter) {
        continue;
      }

      $email = $submission->getEmail() ?: '';

      // Filter by search (email).
      if ($search && stripos($email, $search) === FALSE) {
        continue;
      }

      $form_id = $submission->getFormId();
      if (!isset($forms_cache[$form_id])) {
        $forms_cache[$form_id] = DynamicForm::load($form_id);
      }
      $form = $forms_cache[$form_id];
      $fields = $form ? $form->getFields() : [];
      $data = $submission->getData();
      $field_approvals = $submission->getFieldApprovals();

      $docs = [];
      foreach ($fields as $index => $field) {
        $field_key = 'field_' . $index;
        $value = $data[0][$field_key] ?? $data[0][$field['label']] ?? [];

        $approval = $field_approvals[$field['label']] ?? [
          'status' => 'pending',
          'note' => '',
          'date' => NULL,
        ];

        $doc_entry = [
          'label' => $field['label'],
          'type' => $field['type'],
          'approval_status' => $approval['status'],
          'approval_note' => $approval['note'],
          'approval_date' => $approval['date'],
        ];

        if (is_array($value)) {
          $doc_entry['filename'] = $value['filename'] ?? '';
          $fid = $value['fid'] ?? $value['value'] ?? NULL;
          if ($fid) {
            $file = \Drupal\file\Entity\File::load($fid);
            if ($file) {
              $doc_entry['download_url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            }
          }
        } else {
          $doc_entry['value'] = $value;
        }

        $docs[] = $doc_entry;
      }

      // Get assignment info.
      $assignment = NULL;
      if (\Drupal::moduleHandler()->moduleExists('submission_assignment')) {
        $connection = \Drupal::database();
        $record = $connection->select('dynamic_form_submission', 's')
          ->fields('s', ['assigned_to', 'assigned_at', 'assigned_by'])
          ->condition('id', $submission->id())
          ->execute()
          ->fetchObject();

        if (!empty($record->assigned_to)) {
          $worker = \Drupal\user\Entity\User::load($record->assigned_to);
          $assignment = [
            'worker_uid' => (int) $record->assigned_to,
            'worker_name' => $worker ? $worker->getDisplayName() : 'Unknown',
            'worker_email' => $worker ? $worker->getEmail() : '',
            'assigned_at' => $record->assigned_at ? (int) $record->assigned_at : NULL,
          ];
        }
      }

      // Count messages.
      $message_count = 0;
      if (\Drupal::moduleHandler()->moduleExists('submission_assignment')) {
        $connection = \Drupal::database();
        $message_count = (int) $connection->select('dynamic_form_messages', 'm')
          ->condition('submission_id', $submission->id())
          ->countQuery()
          ->execute()
          ->fetchField();
      }

      $result[] = [
        'submission_id' => (int) $submission->id(),
        'form_id' => $form_id,
        'form_label' => $form ? $form->label() : $form_id,
        'email' => $email,
        'created' => (int) $submission->getCreatedTime(),
        'approval_status' => $approval_status,
        'approval_note' => $submission->getApprovalNote(),
        'approval_date' => $submission->getApprovalDate(),
        'fields' => $docs,
        'assignment' => $assignment,
        'message_count' => $message_count,
      ];
    }

    // Sort by created date desc.
    usort($result, function ($a, $b) {
      return $b['created'] - $a['created'];
    });

    // Get available forms for filter dropdown.
    $available_forms = [];
    foreach ($forms_cache as $fid => $form) {
      if ($form) {
        $available_forms[] = [
          'id' => $fid,
          'label' => $form->label(),
        ];
      }
    }

    return new JsonResponse([
      'submissions' => $result,
      'total' => count($result),
      'available_forms' => $available_forms,
    ]);
  }

  /**
   * POST /api/admin/submission/{submission_id}/approve-field
   *
   * Approve or deny a specific field in a submission.
   * Body: { "field_label": "...", "status": "approved|denied|pending", "note": "..." }
   */
  public function approveField($submission_id, Request $request) {
    $current_user = \Drupal::currentUser();
    $is_admin = $current_user->hasPermission('administer site configuration');
    $is_tecnico = in_array('tecnico', $current_user->getRoles());

    if (!$is_admin && !$is_tecnico) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    $field_label = $data['field_label'] ?? '';
    $status = $data['status'] ?? '';
    $note = $data['note'] ?? '';

    if (!in_array($status, ['approved', 'denied', 'pending'])) {
      return new JsonResponse(['error' => 'Invalid status'], 400);
    }

    if (empty($field_label)) {
      return new JsonResponse(['error' => 'field_label is required'], 400);
    }

    // Set per-field approval.
    $submission->setFieldApproval($field_label, $status, $note, time());

    // Recompute overall status.
    $overall = $submission->computeOverallStatus();
    $submission->setApprovalStatus($overall);
    $submission->save();

    return new JsonResponse([
      'success' => TRUE,
      'submission_id' => (int) $submission_id,
      'field_label' => $field_label,
      'status' => $status,
      'overall_status' => $overall,
    ]);
  }
}
