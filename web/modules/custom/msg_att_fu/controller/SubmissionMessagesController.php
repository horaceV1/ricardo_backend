<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;

/**
 * Controller for submission messages.
 */
class SubmissionMessagesController extends ControllerBase {

  /**
   * Display messages for a submission.
   */
  public function viewMessages($submission_id) {
    $connection = \Drupal::database();
    
    // Check access
    $current_user = \Drupal::currentUser();
    $submission = DynamicFormSubmission::load($submission_id);

    if (!$submission) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Check if user has access (assigned staff or admin)
    $is_assigned = $current_user->id() == $submission->getAssignedTo();
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_assigned && !$is_admin) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Get submission info
    $data = $submission->getData();
    $form_name = $data['form_name'] ?? 'Formulário';

    // Get messages
    $messages = $connection->select('dynamic_form_messages', 'm')
      ->fields('m')
      ->condition('submission_id', $submission_id)
      ->orderBy('created', 'ASC')
      ->execute()
      ->fetchAll();

    // Build messages list
    $messages_list = [];
    foreach ($messages as $msg) {
      $sender = User::load($msg->user_id);
      $is_current_user = $msg->user_id == $current_user->id();
      
      $file_link = '';
      if ($msg->file_id) {
        $file = File::load($msg->file_id);
        if ($file) {
          $file_link = [
            '#type' => 'link',
            '#title' => $file->getFilename(),
            '#url' => \Drupal\Core\Url::fromUri(file_create_url($file->getFileUri())),
            '#attributes' => ['target' => '_blank', 'class' => ['file-attachment']],
          ];
        }
      }

      $messages_list[] = [
        '#theme' => 'submission_message_item',
        '#sender_name' => $sender ? $sender->getDisplayName() : $this->t('Unknown'),
        '#message' => $msg->message,
        '#file' => $file_link,
        '#created' => \Drupal::service('date.formatter')->format($msg->created, 'custom', 'd/m/Y H:i'),
        '#is_current_user' => $is_current_user,
      ];
    }

    // Get message form
    $message_form = \Drupal::formBuilder()->getForm(
      'Drupal\formulario_candidatura_dinamico\Form\SubmissionMessageForm',
      $submission_id
    );

    $build = [
      '#theme' => 'submission_messages',
      '#submission_id' => $submission_id,
      '#form_name' => $form_name,
      '#messages' => $messages_list,
      '#message_form' => $message_form,
      '#attached' => [
        'library' => [
          'formulario_candidatura_dinamico/messages',
        ],
      ],
    ];

    return $build;
  }

}
