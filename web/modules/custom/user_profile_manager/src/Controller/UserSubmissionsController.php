<?php

namespace Drupal\user_profile_manager\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Controller for user submissions page.
 */
class UserSubmissionsController extends ControllerBase {

  /**
   * Displays user's form submissions.
   */
  public function view(UserInterface $user) {
    $build = [];
    
    // Always display user information first
    $build['user_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Information'),
      '#attributes' => ['class' => ['user-info-section']],
    ];
    
    $build['user_info']['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Name'),
      '#markup' => $user->getAccountName(),
    ];
    
    $build['user_info']['email'] = [
      '#type' => 'item',
      '#title' => $this->t('Email'),
      '#markup' => $user->getEmail(),
    ];
    
    try {
      // Get user's profile
      $profile_storage = $this->entityTypeManager()->getStorage('profile');
      $profiles = $profile_storage->loadByProperties([
        'uid' => $user->id(),
        'type' => 'user_submissions',
      ]);
      
      if (empty($profiles)) {
        $build['no_submissions'] = [
          '#markup' => '<div class="messages messages--warning">' . $this->t('No submissions found for this user yet. Submit a form to see your submissions here.') . '</div>',
        ];
        return $build;
      }
      
      $profile = reset($profiles);
    }
    catch (\Exception $e) {
      \Drupal::logger('user_profile_manager')->error('Error loading profile: @message', ['@message' => $e->getMessage()]);
      $build['error'] = [
        '#markup' => '<div class="messages messages--error">' . $this->t('Unable to load user submissions. Please contact the administrator.') . '</div>',
      ];
      return $build;
    }
    
    // Display additional profile information if available
    if ($profile->hasField('field_first_name') && !$profile->get('field_first_name')->isEmpty()) {
      $build['user_info']['first_name'] = [
        '#type' => 'item',
        '#title' => $this->t('First Name'),
        '#markup' => $profile->get('field_first_name')->value,
      ];
    }
    
    if ($profile->hasField('field_last_name') && !$profile->get('field_last_name')->isEmpty()) {
      $build['user_info']['last_name'] = [
        '#type' => 'item',
        '#title' => $this->t('Last Name'),
        '#markup' => $profile->get('field_last_name')->value,
      ];
    }
    
    if ($profile->hasField('field_phone') && !$profile->get('field_phone')->isEmpty()) {
      $build['user_info']['phone'] = [
        '#type' => 'item',
        '#title' => $this->t('Phone'),
        '#markup' => $profile->get('field_phone')->value,
      ];
    }
    
    if ($profile->hasField('field_company') && !$profile->get('field_company')->isEmpty()) {
      $build['user_info']['company'] = [
        '#type' => 'item',
        '#title' => $this->t('Company'),
        '#markup' => $profile->get('field_company')->value,
      ];
    }
    
    // Display form submissions
    if ($profile->hasField('field_submissions') && !$profile->get('field_submissions')->isEmpty()) {
      $submissions_json = $profile->get('field_submissions')->value;
      $submissions = json_decode($submissions_json, TRUE);
      
      \Drupal::logger('user_profile_manager')->info('Submissions JSON: @json', [
        '@json' => substr($submissions_json, 0, 500),
      ]);
      \Drupal::logger('user_profile_manager')->info('Decoded submissions count: @count', [
        '@count' => is_array($submissions) ? count($submissions) : 0,
      ]);
      
      if (!empty($submissions)) {
        $build['submissions'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Form Submissions History'),
          '#attributes' => ['class' => ['submissions-section']],
        ];
        
        $rows = [];
        foreach (array_reverse($submissions) as $index => $submission) {
          $webform_id = $submission['webform_id'] ?? 'Unknown';
          $submission_id = $submission['submission_id'] ?? 'N/A';
          $timestamp = $submission['timestamp'] ?? time();
          $data = $submission['data'] ?? [];
          
          \Drupal::logger('user_profile_manager')->info('Processing submission @id with @count fields', [
            '@id' => $submission_id,
            '@count' => count($data),
          ]);
          
          // Format the data with better file display
          $data_output = '<div class="submission-data">';
          foreach ($data as $key => $value) {
            // Check if value is a file array
            if (is_array($value) && isset($value['type']) && $value['type'] === 'file') {
              // This is a file field - create download button
              $file_id = $value['value'] ?? NULL;
              $filename = $value['filename'] ?? 'Unknown file';
              
              if ($file_id) {
                $file = \Drupal\file\Entity\File::load($file_id);
                if ($file) {
                  $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
                  $value_display = '<div class="file-item"><strong>' . htmlspecialchars($key) . ':</strong> <a href="' . $file_url . '" download class="button button--primary file-download-btn">📄 ' . htmlspecialchars($filename) . ' ⬇</a></div>';
                } else {
                  $value_display = '<div class="file-item"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($filename) . ' <em>(' . $this->t('File not found') . ')</em></div>';
                }
              } else {
                $value_display = '<div class="file-item"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($filename) . '</div>';
              }
            } elseif (is_array($value)) {
              $value_display = '<div class="text-item"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars(implode(', ', $value)) . '</div>';
            } else {
              $value_display = '<div class="text-item"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</div>';
            }
            
            $data_output .= $value_display;
          }
          $data_output .= '</div>';
          
          // Add delete button
          $delete_url = \Drupal\Core\Url::fromRoute('user_profile_manager.delete_submission', [
            'user' => $user->id(),
            'submission_id' => $submission_id,
          ]);
          $delete_link = '<a href="' . $delete_url->toString() . '" class="button button--danger delete-submission-btn" onclick="return confirm(\'Are you sure you want to delete this submission?\')">🗑️ Delete</a>';
          
          $rows[] = [
            ['data' => $submission_id],
            ['data' => $webform_id],
            ['data' => date('Y-m-d H:i:s', $timestamp)],
            ['data' => ['#markup' => new FormattableMarkup($data_output, [])]],
            ['data' => ['#markup' => new FormattableMarkup($delete_link, [])]],
          ];
        }
        
        $build['submissions']['table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Submission ID'),
            $this->t('Form'),
            $this->t('Date'),
            $this->t('Data'),
            $this->t('Actions'),
          ],
          '#rows' => $rows,
          '#empty' => $this->t('No submissions found.'),
          '#attributes' => ['class' => ['submissions-table']],
        ];
      }
    }
    
    // Add some basic styling
    $build['#attached']['library'][] = 'user_profile_manager/submissions_page';
    
    return $build;
  }

  /**
   * Checks access for user submissions page.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    // User can view own submissions, or admin can view any
    if ($account->hasPermission('administer users') || $user->id() == $account->id()) {
      return AccessResult::allowed();
    }
    
    return AccessResult::forbidden();
  }

}
