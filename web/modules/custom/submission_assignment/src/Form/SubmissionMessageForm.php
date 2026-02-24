<?php

namespace Drupal\submission_assignment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Form for sending messages on a submission (worker <-> client communication).
 */
class SubmissionMessageForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a SubmissionMessageForm.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_assignment_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $submission_id = NULL) {
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
      '#placeholder' => $this->t('Escreva a sua mensagem...'),
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Anexar documento (opcional)'),
      '#upload_location' => 'private://submission_messages/',
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
    $submission_id = $form_state->getValue('submission_id');
    $submission = DynamicFormSubmission::load($submission_id);

    if (!$submission) {
      $form_state->setErrorByName('submission_id', $this->t('Submissão não encontrada.'));
      return;
    }

    // Check access: must be assigned worker, admin, or submission owner.
    $current_user = $this->currentUser();
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_admin) {
      $assigned_to = $this->database->select('dynamic_form_submission', 's')
        ->fields('s', ['assigned_to'])
        ->condition('id', $submission_id)
        ->execute()
        ->fetchField();

      if ($assigned_to != $current_user->id()) {
        $form_state->setErrorByName('message', $this->t('Não tem permissão para enviar mensagens nesta submissão.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission_id = $form_state->getValue('submission_id');
    $message = $form_state->getValue('message');
    $current_user = $this->currentUser();

    // Handle file upload.
    $file_value = $form_state->getValue('file');
    $file_id = !empty($file_value) && is_array($file_value) ? reset($file_value) : NULL;

    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    // Insert message.
    $this->database->insert('dynamic_form_messages')
      ->fields([
        'submission_id' => $submission_id,
        'user_id' => $current_user->id(),
        'message' => $message,
        'file_id' => $file_id,
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    // Send email notification to the other party.
    $this->sendMessageNotification($submission_id, $current_user->id(), $message);

    $this->messenger()->addStatus($this->t('Mensagem enviada com sucesso.'));

    // Redirect back to the submission messages page.
    $form_state->setRedirect('submission_assignment.submission_messages', [
      'submission_id' => $submission_id,
    ]);
  }

  /**
   * Send email notification to the other party.
   */
  protected function sendMessageNotification($submission_id, $sender_id, $message) {
    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      return;
    }

    $sender = User::load($sender_id);
    $current_user = $this->currentUser();
    $is_staff = $current_user->hasPermission('manage assigned submissions') || $current_user->hasPermission('administer site configuration');

    if ($is_staff) {
      // Staff sent message → notify client.
      $to = $submission->getEmail();
    }
    else {
      // Client sent message → notify assigned worker.
      $assigned_to = $this->database->select('dynamic_form_submission', 's')
        ->fields('s', ['assigned_to'])
        ->condition('id', $submission_id)
        ->execute()
        ->fetchField();

      if ($assigned_to) {
        $assigned_user = User::load($assigned_to);
        $to = $assigned_user ? $assigned_user->getEmail() : NULL;
      }
      else {
        $to = \Drupal::config('system.site')->get('mail');
      }
    }

    if ($to) {
      $params = [
        'sender_name' => $sender->getDisplayName(),
        'message' => $message,
        'submission_id' => $submission_id,
      ];

      \Drupal::service('plugin.manager.mail')->mail(
        'submission_assignment',
        'new_message',
        $to,
        'pt-pt',
        $params,
        NULL,
        TRUE
      );
    }
  }

}
