<?php
namespace Drupal\formulario_candidatura_dinamico\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;

/**
 * Controller for Recent Activity API.
 */
class RecentActivityApiController extends ControllerBase {

  /**
   * Get recent activity for the current user.
   */
  public function getRecentActivity() {
    $current_user = \Drupal::currentUser();
    $email = $current_user->getEmail();

    if (!$email) {
      return new JsonResponse(['error' => 'Not authenticated'], 403);
    }

    // Get submissions for this user
    $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
    $query = $storage->getQuery()
      ->condition('email', $email)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $ids = $query->execute();
    $submissions = $storage->loadMultiple($ids);

    $result = [];
    foreach ($submissions as $submission) {
      /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
      $form = DynamicForm::load($submission->getFormId());
      
      $status = $submission->getApprovalStatus();
      $approval_date = $submission->getApprovalDate();
      $approval_note = $submission->getApprovalNote();
      
      // Determine status color
      $status_color = 'orange';
      if ($status === 'approved') {
        $status_color = 'green';
      } elseif ($status === 'denied') {
        $status_color = 'red';
      }
      
      $result[] = [
        'id' => $submission->id(),
        'form_name' => $form ? $form->label() : 'Unknown Form',
        'form_id' => $submission->getFormId(),
        'submitted_date' => $submission->getCreatedTime(),
        'submitted_date_formatted' => \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'medium'),
        'status' => $status,
        'status_label' => ucfirst($status),
        'status_color' => $status_color,
        'approval_note' => $approval_note,
        'approval_date' => $approval_date,
        'approval_date_formatted' => $approval_date ? \Drupal::service('date.formatter')->format($approval_date, 'medium') : null,
      ];
    }

    return new JsonResponse([
      'success' => true,
      'activities' => $result,
      'total' => count($result),
    ]);
  }
}
