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
  public function quickApprove($dynamic_form_submission, Request $request) {
    // Check if user has permission
    if (!$this->currentUser()->hasPermission('administer users')) {
      return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Load submission
    if (is_numeric($dynamic_form_submission)) {
      $submission = DynamicFormSubmission::load($dynamic_form_submission);
    } else {
      $submission = $dynamic_form_submission;
    }

    if (!$submission) {
      return new JsonResponse(['success' => false, 'message' => 'Submission not found'], 404);
    }

    // Get data from request
    $data = json_decode($request->getContent(), TRUE);
    $status = $data['status'] ?? null;
    $note = $data['note'] ?? '';

    if (!in_array($status, ['approved', 'denied', 'pending'])) {
      return new JsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
    }

    // Update submission
    $submission->setApprovalStatus($status);
    $submission->setApprovalDate(time());
    if ($note) {
      $submission->setApprovalNote($note);
    }
    $submission->save();

    return new JsonResponse([
      'success' => true,
      'message' => 'Submission updated successfully',
      'status' => $status,
    ]);
  }
}
