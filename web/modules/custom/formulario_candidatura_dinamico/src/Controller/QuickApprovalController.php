<?php
namespace Drupal\formulario_candidatura_dinamico\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;

/**
 * Controller for quick approval actions.
 */
class QuickApprovalController extends ControllerBase {

  /**
   * Quick approve/deny a submission.
   */
  public function quickApprove($submission_id, Request $request) {
    try {
      \Drupal::logger('quick_approve')->info('Quick approve request received for submission: @id', [
        '@id' => $submission_id,
      ]);

      // Check if user has permission
      if (!$this->currentUser()->hasPermission('administer users')) {
        \Drupal::logger('quick_approve')->warning('Access denied for user @uid', [
          '@uid' => $this->currentUser()->id(),
        ]);
        return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
      }

      // Load submission directly by entity ID
      $submission = DynamicFormSubmission::load($submission_id);
      
      // If not found, try to find it by searching all recent submissions
      if (!$submission) {
        \Drupal::logger('quick_approve')->info('Direct load failed for ID @id, searching by recent submissions', ['@id' => $submission_id]);
        
        $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
        
        // Try to find by created timestamp (in case submission_id is a timestamp)
        $query = $storage->getQuery()
          ->condition('created', $submission_id - 300, '>=')
          ->condition('created', $submission_id + 300, '<=')
          ->accessCheck(FALSE)
          ->range(0, 5);
        $entity_ids = $query->execute();
        
        if (!empty($entity_ids)) {
          $submission = $storage->load(reset($entity_ids));
          \Drupal::logger('quick_approve')->info('Found by timestamp range: entity @id for search @sid', [
            '@id' => reset($entity_ids),
            '@sid' => $submission_id,
          ]);
        }
      }
      
      if (!$submission) {
        \Drupal::logger('quick_approve')->error('Submission not found: @id', [
          '@id' => $submission_id,
        ]);
        return new JsonResponse(['success' => false, 'message' => 'Submission not found'], 404);
      }
      
      \Drupal::logger('quick_approve')->info('Loaded submission entity ID: @id', ['@id' => $submission->id()]);

      // Get data from request
      $content = $request->getContent();
      \Drupal::logger('quick_approve')->info('Request body: @body', ['@body' => $content]);
      
      $data = json_decode($content, TRUE);

      // Support per-field approvals.
      if (isset($data['field_approvals']) && is_array($data['field_approvals'])) {
        $now = time();
        $field_approvals = [];
        foreach ($data['field_approvals'] as $field_label => $approval) {
          $field_status = $approval['status'] ?? 'pending';
          if (!in_array($field_status, ['approved', 'denied', 'pending'])) {
            $field_status = 'pending';
          }
          $field_approvals[$field_label] = [
            'status' => $field_status,
            'note' => $approval['note'] ?? '',
            'date' => $now,
          ];
        }
        $submission->setFieldApprovals($field_approvals);

        // Auto-compute overall status.
        $overall = $submission->computeOverallStatus();
        $submission->setApprovalStatus($overall);
        $submission->setApprovalDate($now);
        $submission->save();

        return new JsonResponse([
          'success' => true,
          'message' => 'Per-field approvals updated',
          'status' => $overall,
          'field_approvals' => $field_approvals,
        ]);
      }

      // Legacy: whole-form approval.
      $status = $data['status'] ?? null;
      $note = $data['note'] ?? '';

      if (!in_array($status, ['approved', 'denied', 'pending'])) {
        \Drupal::logger('quick_approve')->error('Invalid status: @status', ['@status' => $status]);
        return new JsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
      }

      // Update submission
      $submission->setApprovalStatus($status);
      $submission->setApprovalDate(time());
      if ($note) {
        $submission->setApprovalNote($note);
      }
      $submission->save();

      \Drupal::logger('quick_approve')->info('Submission @id updated to status: @status', [
        '@id' => $submission->id(),
        '@status' => $status,
      ]);

      return new JsonResponse([
        'success' => true,
        'message' => 'Submission updated successfully',
        'status' => $status,
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('quick_approve')->error('Exception in quickApprove: @msg, Trace: @trace', [
        '@msg' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
      return new JsonResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
      ], 500);
    }
  }
}
