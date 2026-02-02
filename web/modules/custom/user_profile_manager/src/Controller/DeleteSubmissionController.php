<?php

namespace Drupal\user_profile_manager\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for deleting form submissions.
 */
class DeleteSubmissionController extends ControllerBase {

  /**
   * Delete a submission.
   */
  public function deleteSubmission(UserInterface $user, $submission_id) {
    try {
      // Load user's profile
      $profile_storage = $this->entityTypeManager()->getStorage('profile');
      $profiles = $profile_storage->loadByProperties([
        'uid' => $user->id(),
        'type' => 'user_submissions',
      ]);
      
      if (empty($profiles)) {
        $this->messenger()->addError($this->t('No submissions found.'));
        return $this->redirect('entity.user.submissions', ['user' => $user->id()]);
      }
      
      $profile = reset($profiles);
      
      if ($profile->hasField('field_submissions') && !$profile->get('field_submissions')->isEmpty()) {
        $submissions_json = $profile->get('field_submissions')->value;
        $submissions = json_decode($submissions_json, TRUE);
        
        if (is_array($submissions)) {
          // Find and remove the submission
          $found = FALSE;
          foreach ($submissions as $key => $submission) {
            if (isset($submission['submission_id']) && $submission['submission_id'] === $submission_id) {
              // Delete associated files
              if (isset($submission['data']) && is_array($submission['data'])) {
                foreach ($submission['data'] as $field_data) {
                  if (is_array($field_data) && isset($field_data['type']) && $field_data['type'] === 'file' && isset($field_data['value'])) {
                    $file = \Drupal\file\Entity\File::load($field_data['value']);
                    if ($file) {
                      $file->delete();
                    }
                  }
                }
              }
              
              unset($submissions[$key]);
              $found = TRUE;
              break;
            }
          }
          
          if ($found) {
            // Re-index array and save
            $submissions = array_values($submissions);
            $profile->set('field_submissions', json_encode($submissions));
            $profile->save();
            
            $this->messenger()->addStatus($this->t('Submission deleted successfully.'));
          } else {
            $this->messenger()->addWarning($this->t('Submission not found.'));
          }
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('user_profile_manager')->error('Error deleting submission: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Failed to delete submission.'));
    }
    
    return $this->redirect('entity.user.submissions', ['user' => $user->id()]);
  }

  /**
   * Checks access for deletion.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    // User can delete own submissions, or admin can delete any
    if ($account->hasPermission('administer users') || $user->id() == $account->id()) {
      return AccessResult::allowed();
    }
    
    return AccessResult::forbidden();
  }

}
