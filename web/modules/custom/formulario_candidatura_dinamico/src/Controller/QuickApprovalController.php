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

      // Load submission - try direct load first, then search
      $submission = DynamicFormSubmission::load($submission_id);
      
      // If direct load fails, try to find by timestamp/created date
      if (!$submission) {
        \Drupal::logger('quick_approve')->info('Direct load failed, searching by created timestamp');
        $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
        $query = $storage->getQuery()
          ->condition('created', $submission_id)
          ->accessCheck(FALSE)
          ->range(0, 1);
        $ids = $query->execute();
        
        if (!empty($ids)) {
          $entity_id = reset($ids);
          $submission = $storage->load($entity_id);
          \Drupal::logger('quick_approve')->info('Found submission by timestamp. Entity ID: @id', ['@id' => $entity_id]);
        }
      }

      if (!$submission) {
        \Drupal::logger('quick_approve')->error('Submission not found: @id', [
          '@id' => $submission_id,
        ]);
        return new JsonResponse(['success' => false, 'message' => 'Submission not found'], 404);
      }

      // Get data from request
      $content = $request->getContent();
      \Drupal::logger('quick_approve')->info('Request body: @body', ['@body' => $content]);
      
      $data = json_decode($content, TRUE);
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
