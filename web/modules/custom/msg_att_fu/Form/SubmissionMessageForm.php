<?php

namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Form for sending messages on a submission.
 */
class SubmissionMessageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $submission_id = NULL) {
    // CRITICAL: Set form to accept file uploads
    $form['#attributes'] = ['enctype' => 'multipart/form-data'];
    
    $form['submission_id'] = [
      '#type' => 'hidden',
      '#value' => $submission_id,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensagem'),
      '#required' => TRUE,
      '#rows' => 4,
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Anexar documento (opcional)'),
      '#upload_location' => 'public://submission_messages/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'pdf doc docx xls xlsx jpg jpeg png',
        ],
      ],
      '#description' => $this->t('Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (máx. 10MB)'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enviar Mensagem'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation handled by managed_file widget
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission_id = $form_state->getValue('submission_id');
    $message = $form_state->getValue('message');
    
    // Get file ID - managed_file returns array like [0 => file_id]
    $file_value = $form_state->getValue('file');
    $file_id = !empty($file_value) && is_array($file_value) ? reset($file_value) : NULL;
    
    $current_user = \Drupal::currentUser();

    // Make file permanent if uploaded
    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    // Insert message
    $connection = \Drupal::database();
    $connection->insert('dynamic_form_messages')
      ->fields([
        'submission_id' => $submission_id,
        'user_id' => $current_user->id(),
        'message' => $message,
        'file_id' => $file_id,
        'created' => time(),
      ])
      ->execute();

    // Send email notification
    $this->sendMessageNotification($submission_id, $current_user->id(), $message);

    $this->messenger()->addStatus($this->t('Mensagem enviada com sucesso.'));
    
    // Redirect back to employee view
    $form_state->setRedirect('formulario_candidatura_dinamico.employee_view_submission', ['id' => $submission_id]);
  }

  /**
   * Send email notification to the other party.
   */
  protected function sendMessageNotification($submission_id, $sender_id, $message) {
    // Load submission entity
    $submission = \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission::load($submission_id);

    if (!$submission) {
      return;
    }

    $current_user = \Drupal::currentUser();
    $sender = \Drupal\user\Entity\User::load($sender_id);
    
    // Determine recipient
    $is_staff = \Drupal::service('current_user')->hasPermission('administer site configuration');
    
    if ($is_staff) {
      // Staff sent message, notify applicant
      $to = $submission->getEmail();
      $recipient_name = 'Cliente';
    } else {
      // Applicant sent message, notify assigned staff
      if ($submission->getAssignedTo()) {
        $assigned_user = \Drupal\user\Entity\User::load($submission->getAssignedTo());
        $to = $assigned_user->getEmail();
        $recipient_name = $assigned_user->getDisplayName();
      } else {
        // No assigned user, send to site admin email
        $to = \Drupal::config('system.site')->get('mail');
        $recipient_name = 'Administrador';
      }
    }

    if ($to) {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $params = [
        'sender_name' => $sender->getDisplayName(),
        'message' => $message,
        'submission_id' => $submission_id,
      ];
      
      $mailManager->mail('formulario_candidatura_dinamico', 'new_message', $to, 'pt-pt', $params, NULL, TRUE);
    }
  }

}
