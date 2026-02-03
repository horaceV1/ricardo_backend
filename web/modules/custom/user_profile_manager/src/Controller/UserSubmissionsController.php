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
    
    // Display form submissions from DynamicFormSubmission entities (not from JSON)
    $submission_storage = $this->entityTypeManager()->getStorage('dynamic_form_submission');
    $submission_query = $submission_storage->getQuery()
      ->condition('email', $user->getEmail())
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $submission_ids = $submission_query->execute();
    $submissions = $submission_storage->loadMultiple($submission_ids);
    
    // Also get JSON submissions as fallback
    $json_submissions = [];
    if ($profile->hasField('field_submissions') && !$profile->get('field_submissions')->isEmpty()) {
      $submissions_json = $profile->get('field_submissions')->value;
      $json_submissions = json_decode($submissions_json, TRUE) ?? [];
    }
    
    if (!empty($submissions)) {
      $build['submissions'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Form Submissions History'),
        '#attributes' => ['class' => ['submissions-section']],
      ];
      
      // Display DynamicFormSubmission entities with approval system
      foreach ($submissions as $submission) {
        /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
        $form = \Drupal\formulario_candidatura_dinamico\Entity\DynamicForm::load($submission->getFormId());
        
        $build['submissions']['submission_' . $submission->id()] = [
          '#type' => 'details',
          '#title' => ($form ? $form->label() : $this->t('Unknown Form')) . ' - ' . date('Y-m-d H:i', $submission->getCreatedTime()),
          '#open' => FALSE,
          '#attributes' => ['class' => ['submission-item']],
        ];
        
        $item_build = &$build['submissions']['submission_' . $submission->id()];
        
        // Approval status section
        $status = $submission->getApprovalStatus();
        $item_build['status_section'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['approval-status-section', 'status-' . $status]],
        ];
        
        $status_icons = ['pending' => '⏳', 'approved' => '✅', 'denied' => '❌'];
        $status_labels = [
          'pending' => $this->t('Pendente'),
          'approved' => $this->t('Aprovado'),
          'denied' => $this->t('Negado'),
        ];
        
        $item_build['status_section']['badge'] = [
          '#markup' => '<div class="approval-badge status-' . $status . '"><strong>' . 
                       $status_icons[$status] . ' ' . $status_labels[$status] . '</strong></div>',
        ];
        
        if ($submission->getApprovalNote()) {
          $item_build['status_section']['note'] = [
            '#markup' => '<div class="approval-note"><strong>' . $this->t('Admin Note:') . '</strong><br>' . 
                         nl2br(htmlspecialchars($submission->getApprovalNote())) . '</div>',
          ];
        }
        
        if ($submission->getApprovalDate()) {
          $item_build['status_section']['date'] = [
            '#markup' => '<div class="approval-date"><strong>' . $this->t('Decision Date:') . '</strong> ' . 
                         date('Y-m-d H:i', $submission->getApprovalDate()) . '</div>',
          ];
        }
        
        // Submission data
        $item_build['data'] = [
          '#type' => 'table',
          '#header' => [$this->t('Field'), $this->t('Value')],
          '#rows' => [],
        ];
        
        $fields = $form ? $form->getFields() : [];
        $data = $submission->getData();
        
        foreach ($fields as $index => $field) {
          $field_key = 'field_' . $index;
          $value = $data[0][$field_key] ?? '';

          if (is_array($value) && isset($value['fid'])) {
            $file = \Drupal\file\Entity\File::load($value['fid']);
            if ($file) {
              $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
              $value = [
                'data' => [
                  '#type' => 'link',
                  '#title' => '📄 ' . $value['filename'],
                  '#url' => \Drupal\Core\Url::fromUri($url),
                  '#attributes' => ['target' => '_blank', 'class' => ['file-download-link']],
                ],
              ];
            }
          }

          $item_build['data']['#rows'][] = [
            ['data' => ['#markup' => '<strong>' . $field['label'] . '</strong>']],
            $value,
          ];
        }
        
        // Add approval form for admins
        $current_user = \Drupal::currentUser();
        if ($current_user->hasPermission('administer users')) {
          $item_build['approval_form'] = \Drupal::formBuilder()->getForm(
            'Drupal\\formulario_candidatura_dinamico\\Form\\SubmissionApprovalForm',
            $submission->id()
          );
        }
      }
      
      // Attach approval system library
      $build['#attached']['library'][] = 'formulario_candidatura_dinamico/submission_approval';
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
