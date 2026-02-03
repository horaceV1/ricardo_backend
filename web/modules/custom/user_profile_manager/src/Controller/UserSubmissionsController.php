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
        
        // Load DynamicFormSubmission entities to get approval status
        $submission_storage = $this->entityTypeManager()->getStorage('dynamic_form_submission');
        
        $rows = [];
        $shown_entity_ids = []; // Track entities we've already displayed
        
        // First, get all database entities for this user
        $query = $submission_storage->getQuery()
          ->condition('email', $user->getEmail())
          ->accessCheck(FALSE)
          ->sort('created', 'DESC');
        $all_entity_ids = $query->execute();
        
        \Drupal::logger('user_profile_manager')->info('Found @count database entities for email @email', [
          '@count' => count($all_entity_ids),
          '@email' => $user->getEmail(),
        ]);
        
        foreach (array_reverse($submissions) as $index => $submission) {
          $webform_id = $submission['webform_id'] ?? 'Unknown';
          $submission_id = $submission['submission_id'] ?? 'N/A';
          $entity_id = $submission['entity_id'] ?? null; // New field for direct entity ID
          $timestamp = $submission['timestamp'] ?? time();
          $data = $submission['data'] ?? [];
          
          \Drupal::logger('user_profile_manager')->info('Processing submission @id (entity_id: @eid) with @count fields', [
            '@id' => $submission_id,
            '@eid' => $entity_id ?? 'none',
            '@count' => count($data),
          ]);
          
          // Try to load the DynamicFormSubmission entity for approval status
          $approval_status = 'pending';
          $approval_note = '';
          $approval_date = '';
          $actual_entity_id = null;
          
          // Try to find the actual entity
          try {
            $entity = null;
            $best_match_id = null;
            
            // First, try direct entity_id if available
            if ($entity_id && is_numeric($entity_id)) {
              // Check if already shown
              if (in_array($entity_id, $shown_entity_ids)) {
                \Drupal::logger('user_profile_manager')->info('Skipping duplicate entity @id (direct match)', ['@id' => $entity_id]);
                continue;
              }
              
              $entity = $submission_storage->load($entity_id);
              if ($entity) {
                $best_match_id = $entity_id;
                \Drupal::logger('user_profile_manager')->info('Direct entity match found: @id', ['@id' => $entity_id]);
              }
            }
            
            // If no direct match, try timestamp matching
            if (!$entity) {
              \Drupal::logger('user_profile_manager')->info('No direct entity_id, searching by timestamp for submission @sid', [
                '@sid' => $submission_id,
              ]);
              
              $smallest_diff = PHP_INT_MAX;
              
              foreach ($all_entity_ids as $eid) {
                // Skip already shown entities
                if (in_array($eid, $shown_entity_ids)) {
                  continue;
                }
                
                $candidate = $submission_storage->load($eid);
                if ($candidate) {
                  $created = $candidate->get('created')->value;
                  $diff = abs($created - $timestamp);
                  
                  \Drupal::logger('user_profile_manager')->info('Checking entity @eid: created=@created, diff=@diff seconds', [
                    '@eid' => $eid,
                    '@created' => $created,
                    '@diff' => $diff,
                  ]);
                  
                  // If timestamp is within 5 minutes (300 seconds), consider it a match
                  if ($diff < $smallest_diff && $diff < 300) {
                    $smallest_diff = $diff;
                    $best_match_id = $eid;
                    $entity = $candidate;
                  }
                }
              }
            }
            
            if ($entity && $best_match_id) {
              $actual_entity_id = $best_match_id;
              
              // Mark this entity as shown
              $shown_entity_ids[] = $actual_entity_id;
              
              $approval_status = $entity->getApprovalStatus() ?: 'pending';
              $approval_note = $entity->getApprovalNote();
              $approval_date = $entity->getApprovalDate();
              
              \Drupal::logger('user_profile_manager')->info('Matched to entity @id', [
                '@id' => $actual_entity_id,
              ]);
            } else {
              // No matching entity found - skip this profile entry to avoid duplicates
              \Drupal::logger('user_profile_manager')->warning('Could not find entity for submission @sid - skipping to avoid duplicate', [
                '@sid' => $submission_id,
              ]);
              continue;
            }
          } catch (\Exception $e) {
            \Drupal::logger('user_profile_manager')->warning('Could not load submission entity: @msg - skipping', [
              '@msg' => $e->getMessage(),
            ]);
            continue;
          }
          
          // Status badge
          $status_icons = ['pending' => '⏳', 'approved' => '✅', 'denied' => '❌'];
          $status_labels = [
            'pending' => $this->t('Pendente'),
            'approved' => $this->t('Aprovado'),
            'denied' => $this->t('Negado'),
          ];
          $status_badge = '<span class="approval-badge status-' . $approval_status . '">' . 
                         ($status_icons[$approval_status] ?? '⏳') . ' ' . 
                         ($status_labels[$approval_status] ?? ucfirst($approval_status)) . '</span>';
          
          // Format the data with better file display
          $data_output = '<div class="submission-data">';
          
          // Add approval info at the top
          if ($approval_note) {
            $data_output .= '<div class="approval-note-display"><strong>' . $this->t('Admin Note:') . '</strong> ' . nl2br(htmlspecialchars($approval_note)) . '</div>';
          }
          if ($approval_date) {
            $data_output .= '<div class="approval-date-display"><strong>' . $this->t('Decision Date:') . '</strong> ' . date('Y-m-d H:i', $approval_date) . '</div>';
          }
          
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
          
          // Build actions column with both delete and approval buttons (for admins)
          $actions_output = '';
          
          // Add delete button
          $delete_url = \Drupal\Core\Url::fromRoute('user_profile_manager.delete_submission', [
            'user' => $user->id(),
            'submission_id' => $submission_id,
          ]);
          $actions_output .= '<a href="' . $delete_url->toString() . '" class="button button--danger delete-submission-btn" data-submission-id="' . htmlspecialchars($submission_id) . '" onclick="return confirmDelete(this, \'' . htmlspecialchars($submission_id, ENT_QUOTES) . '\')">🗑️ ' . $this->t('Delete') . '</a>';
          
          // Add approve/deny buttons for admins if submission is pending
          $current_user = \Drupal::currentUser();
          if ($current_user->hasPermission('administer users')) {
            if ($approval_status === 'pending') {
              // Use actual_entity_id if found, otherwise fall back to numeric_id
              $button_id = $actual_entity_id ?: $numeric_id;
              $actions_output .= '<div style="margin-top: 10px;">';
              $actions_output .= '<button type="button" class="button button--small button--primary" onclick="quickApprove(' . $button_id . ', \'approved\')" data-entity-id="' . ($actual_entity_id ?: 'not-found') . '" data-numeric-id="' . $numeric_id . '">✅ ' . $this->t('Approve') . '</button> ';
              $actions_output .= '<button type="button" class="button button--small button--danger" onclick="quickApprove(' . $button_id . ', \'denied\')" data-entity-id="' . ($actual_entity_id ?: 'not-found') . '" data-numeric-id="' . $numeric_id . '">❌ ' . $this->t('Deny') . '</button>';
              $actions_output .= '</div>';
            }
          }
          
          $rows[] = [
            ['data' => $submission_id, 'data-submission-row' => $submission_id],
            ['data' => $webform_id],
            ['data' => date('Y-m-d H:i:s', $timestamp)],
            ['data' => ['#markup' => new FormattableMarkup($status_badge, [])]],
            ['data' => ['#markup' => new FormattableMarkup($data_output, [])]],
            ['data' => ['#markup' => new FormattableMarkup($actions_output, [])]],
          ];
        }
        
        $build['submissions']['table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Submission ID'),
            $this->t('Form'),
            $this->t('Date'),
            $this->t('Status'),
            $this->t('Data'),
            $this->t('Actions'),
          ],
          '#rows' => $rows,
          '#empty' => $this->t('No submissions found.'),
          '#attributes' => ['class' => ['submissions-table']],
        ];
        
        // Attach libraries
        $build['#attached']['library'][] = 'formulario_candidatura_dinamico/submission_approval';
      }
    }
    
    // Add some basic styling
    $build['#attached']['library'][] = 'user_profile_manager/submissions_page';
    
    // Disable caching so deletions show immediately
    $build['#cache']['max-age'] = 0;
    
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
