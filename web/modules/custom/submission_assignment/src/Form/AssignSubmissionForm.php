<?php

namespace Drupal\submission_assignment\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to assign a worker/technician to a client form submission.
 */
class AssignSubmissionForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new AssignSubmissionForm.
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
    return 'submission_assignment_assign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $submission_id = NULL) {
    $form_state->set('submission_id', $submission_id);

    // Load the submission entity.
    $submission = DynamicFormSubmission::load($submission_id);
    if (!$submission) {
      $this->messenger()->addError($this->t('Submissão não encontrada.'));
      return $form;
    }

    // Get current assignment from database.
    $current_assignment = $this->database->select('dynamic_form_submission', 'dfs')
      ->fields('dfs', ['assigned_to', 'assigned_at', 'assigned_by'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchObject();

    // Load the form entity for display.
    $form_entity = \Drupal::entityTypeManager()
      ->getStorage('dynamic_form')
      ->load($submission->getFormId());
    $form_label = $form_entity ? $form_entity->label() : $submission->getFormId();

    // Submission info header.
    $form['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['assignment-info-section']],
    ];
    $form['info']['details'] = [
      '#markup' => '<div class="submission-info-card">'
        . '<h3>' . $this->t('Informação da Submissão') . '</h3>'
        . '<p><strong>' . $this->t('ID:') . '</strong> #' . $submission_id . '</p>'
        . '<p><strong>' . $this->t('Formulário:') . '</strong> ' . $form_label . '</p>'
        . '<p><strong>' . $this->t('Email do cliente:') . '</strong> ' . ($submission->getEmail() ?: '-') . '</p>'
        . '<p><strong>' . $this->t('Data de submissão:') . '</strong> ' . \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'short') . '</p>'
        . '<p><strong>' . $this->t('Estado de aprovação:') . '</strong> ' . ($submission->getApprovalStatus() ?: 'pending') . '</p>'
        . '</div>',
    ];

    // Show current assignment.
    if (!empty($current_assignment->assigned_to)) {
      $current_worker = User::load($current_assignment->assigned_to);
      $assigned_by_user = !empty($current_assignment->assigned_by) ? User::load($current_assignment->assigned_by) : NULL;

      $form['current_assignment'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['current-assignment-section']],
      ];
      $form['current_assignment']['info'] = [
        '#markup' => '<div class="current-assignment-card">'
          . '<h3>👤 ' . $this->t('Atribuição Atual') . '</h3>'
          . '<p><strong>' . $this->t('Funcionário:') . '</strong> '
          . ($current_worker ? $current_worker->getDisplayName() . ' (' . $current_worker->getEmail() . ')' : $this->t('Utilizador não encontrado'))
          . '</p>'
          . ($assigned_by_user ? '<p><strong>' . $this->t('Atribuído por:') . '</strong> ' . $assigned_by_user->getDisplayName() . '</p>' : '')
          . (!empty($current_assignment->assigned_at) ? '<p><strong>' . $this->t('Data de atribuição:') . '</strong> ' . \Drupal::service('date.formatter')->format($current_assignment->assigned_at, 'short') . '</p>' : '')
          . '</div>',
      ];
    }

    // Build list of assignable workers.
    $user_options = ['' => '- ' . $this->t('Não atribuído') . ' -'];

    $users_query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->accessCheck(TRUE);
    $user_ids = $users_query->execute();

    foreach (User::loadMultiple($user_ids) as $user) {
      // Show users who have 'manage assigned submissions' permission or are admins.
      if ($user->hasPermission('manage assigned submissions') || $user->hasPermission('administer site configuration')) {
        $user_options[$user->id()] = $user->getDisplayName() . ' (' . $user->getEmail() . ')';
      }
    }

    $form['assigned_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Atribuir a'),
      '#description' => $this->t('Selecione o funcionário/técnico responsável por esta submissão.'),
      '#options' => $user_options,
      '#default_value' => $current_assignment->assigned_to ?? '',
      '#required' => FALSE,
    ];

    $form['notify_worker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notificar funcionário por email'),
      '#default_value' => TRUE,
      '#description' => $this->t('Envia um email ao funcionário selecionado a informar da atribuição.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar Atribuição'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => Url::fromRoute('entity.dynamic_form_submission.canonical', [
        'dynamic_form_submission' => $submission_id,
      ]),
      '#attributes' => ['class' => ['button']],
    ];

    $form['#attached']['library'][] = 'submission_assignment/assignment';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission_id = $form_state->get('submission_id');
    $assigned_to = $form_state->getValue('assigned_to');
    $notify = $form_state->getValue('notify_worker');
    $current_user = $this->currentUser();

    // Get previous assignment for comparison.
    $previous = $this->database->select('dynamic_form_submission', 'dfs')
      ->fields('dfs', ['assigned_to'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchField();

    // Update the submission record.
    $update_fields = [
      'assigned_to' => $assigned_to ?: NULL,
      'assigned_at' => $assigned_to ? \Drupal::time()->getRequestTime() : NULL,
      'assigned_by' => $assigned_to ? $current_user->id() : NULL,
    ];

    $this->database->update('dynamic_form_submission')
      ->fields($update_fields)
      ->condition('id', $submission_id)
      ->execute();

    // Log the assignment change.
    $this->database->insert('submission_assignment_log')
      ->fields([
        'submission_id' => $submission_id,
        'assigned_to' => $assigned_to ?: NULL,
        'assigned_by' => $current_user->id(),
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    // Send notifications.
    if ($assigned_to && $notify) {
      $this->sendAssignmentNotification($submission_id, $assigned_to);
    }

    // Notify previous worker of unassignment.
    if ($previous && $previous != $assigned_to) {
      $this->sendUnassignmentNotification($submission_id, $previous);
    }

    if ($assigned_to) {
      $worker = User::load($assigned_to);
      $this->messenger()->addStatus($this->t('Submissão #@id atribuída a @user com sucesso.', [
        '@id' => $submission_id,
        '@user' => $worker->getDisplayName(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Atribuição removida da submissão #@id.', [
        '@id' => $submission_id,
      ]));
    }

    $form_state->setRedirect('entity.dynamic_form_submission.canonical', [
      'dynamic_form_submission' => $submission_id,
    ]);
  }

  /**
   * Send email notification to the newly assigned worker.
   */
  protected function sendAssignmentNotification($submission_id, $worker_uid) {
    $worker = User::load($worker_uid);
    if (!$worker) {
      return;
    }

    $submission = DynamicFormSubmission::load($submission_id);
    $form_entity = $submission ? \Drupal::entityTypeManager()->getStorage('dynamic_form')->load($submission->getFormId()) : NULL;

    $params = [
      'submission_id' => $submission_id,
      'worker_name' => $worker->getDisplayName(),
      'form_name' => $form_entity ? $form_entity->label() : 'N/A',
      'client_email' => $submission ? $submission->getEmail() : 'N/A',
    ];

    \Drupal::service('plugin.manager.mail')->mail(
      'submission_assignment',
      'assignment_notification',
      $worker->getEmail(),
      'pt-pt',
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Send email notification to a previously assigned worker about unassignment.
   */
  protected function sendUnassignmentNotification($submission_id, $previous_worker_uid) {
    $worker = User::load($previous_worker_uid);
    if (!$worker) {
      return;
    }

    $params = [
      'submission_id' => $submission_id,
      'worker_name' => $worker->getDisplayName(),
    ];

    \Drupal::service('plugin.manager.mail')->mail(
      'submission_assignment',
      'unassignment_notification',
      $worker->getEmail(),
      'pt-pt',
      $params,
      NULL,
      TRUE
    );
  }

}
