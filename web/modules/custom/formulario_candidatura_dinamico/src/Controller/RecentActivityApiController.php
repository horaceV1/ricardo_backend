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
    $uid = $current_user->id();
    $email = $current_user->getEmail();

    \Drupal::logger('recent_activity')->info('Recent activity request - UID: @uid, Email: @email, Anonymous: @anon', [
      '@uid' => $uid,
      '@email' => $email ?: 'NO EMAIL',
      '@anon' => $current_user->isAnonymous() ? 'YES' : 'NO',
    ]);

    if ($current_user->isAnonymous() || !$email) {
      \Drupal::logger('recent_activity')->error('User not authenticated. UID: @uid, Anonymous: @anon', [
        '@uid' => $uid,
        '@anon' => $current_user->isAnonymous() ? 'YES' : 'NO',
      ]);
      $response = new JsonResponse([
        'success' => false,
        'error' => 'Not authenticated',
        'message' => 'User must be logged in',
        'activities' => [],
        'total' => 0,
      ], 401);
      $this->addCorsHeaders($response);
      return $response;
    }

    \Drupal::logger('recent_activity')->info('Fetching activities for email: @email', ['@email' => $email]);

    // Get submissions for this user by email
    $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
    
    // First check how many submissions exist in total
    $total_query = $storage->getQuery()->accessCheck(FALSE);
    $total_ids = $total_query->execute();
    \Drupal::logger('recent_activity')->info('Total submissions in database: @count', ['@count' => count($total_ids)]);
    
    // Now query for user's submissions
    $query = $storage->getQuery()
      ->condition('email', $email)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $ids = $query->execute();
    
    \Drupal::logger('recent_activity')->info('Found @count submissions for email @email', [
      '@count' => count($ids),
      '@email' => $email,
    ]);

    if (empty($ids)) {
      // Return success with empty array instead of error
      $response = new JsonResponse([
        'success' => true,
        'activities' => [],
        'total' => 0,
        'message' => 'No submissions found for this email',
        'debug_email' => $email,
      ]);
      $this->addCorsHeaders($response);
      return $response;
    }

    $submissions = $storage->loadMultiple($ids);

    $result = [];
    foreach ($submissions as $submission) {
      /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
      $form = DynamicForm::load($submission->getFormId());
      
      $status = $submission->getApprovalStatus();
      $approval_date = $submission->getApprovalDate();
      $approval_note = $submission->getApprovalNote();
      
      // Determine status color and label
      $status_color = 'orange';
      $status_label = 'Pendente';
      if ($status === 'approved') {
        $status_color = 'green';
        $status_label = 'Aprovado';
      } elseif ($status === 'denied') {
        $status_color = 'red';
        $status_label = 'Negado';
      }
      
      $result[] = [
        'id' => $submission->id(),
        'form_name' => $form ? $form->label() : 'Formulário Desconhecido',
        'form_id' => $submission->getFormId(),
        'submitted_date' => $submission->getCreatedTime(),
        'submitted_date_formatted' => \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'medium'),
        'status' => $status,
        'status_label' => $status_label,
        'status_color' => $status_color,
        'approval_note' => $approval_note,
        'approval_date' => $approval_date,
        'approval_date_formatted' => $approval_date ? \Drupal::service('date.formatter')->format($approval_date, 'medium') : null,
        'field_approvals' => $submission->getFieldApprovals(),
      ];
    }

    \Drupal::logger('recent_activity')->info('Returning @count activities', ['@count' => count($result)]);

    $response = new JsonResponse([
      'success' => true,
      'activities' => $result,
      'total' => count($result),
    ]);
    $this->addCorsHeaders($response);
    return $response;
  }

  /**
   * Add CORS headers to response.
   */
  private function addCorsHeaders(JsonResponse $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
  }
}
