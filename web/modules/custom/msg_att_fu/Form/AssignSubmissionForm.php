<?php

namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form para atribuir funcionário a uma submissão.
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
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
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
    return 'assign_submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $form_state->set('submission_id', $id);

    // Obter submissão atual
    $submission = $this->database->select('dynamic_form_submission', 'dfs')
      ->fields('dfs')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$submission) {
      $this->messenger()->addError($this->t('Submissão não encontrada.'));
      return $form;
    }

    // Listar utilizadores com permissão para gerir processos
    $users_query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->accessCheck(TRUE);
    $user_ids = $users_query->execute();

    $user_options = ['' => '- Não atribuído -'];
    foreach (User::loadMultiple($user_ids) as $user) {
      // Mostrar apenas utilizadores com permissão para gerir submissões ou admins
      if ($user->hasPermission('manage assigned submissions') || $user->hasPermission('administer site configuration')) {
        $user_options[$user->id()] = $user->getDisplayName() . ' (' . $user->getEmail() . ')';
      }
    }

    $form['info'] = [
      '#markup' => '<p><strong>Formulário:</strong> ' . $submission->form_id . '<br>' .
                   '<strong>Email:</strong> ' . ($submission->email ?? '-') . '<br>' .
                   '<strong>Estado:</strong> ' . ($submission->estado ?? 'submetido') . '</p>',
    ];

    $form['assigned_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Atribuir a'),
      '#options' => $user_options,
      '#default_value' => $submission->assigned_to ?? '',
      '#required' => FALSE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Atribuir'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => \Drupal\Core\Url::fromRoute('entity.dynamic_form_submission.canonical', ['dynamic_form_submission' => $id]),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission_id = $form_state->get('submission_id');
    $assigned_to = $form_state->getValue('assigned_to');

    $this->database->update('dynamic_form_submission')
      ->fields(['assigned_to' => $assigned_to ?: NULL])
      ->condition('id', $submission_id)
      ->execute();

    if ($assigned_to) {
      $user = User::load($assigned_to);
      $this->messenger()->addStatus($this->t('Submissão atribuída a @user com sucesso.', [
        '@user' => $user->getDisplayName(),
      ]));
    } else {
      $this->messenger()->addStatus($this->t('Atribuição removida.'));
    }

    $form_state->setRedirect('entity.dynamic_form_submission.canonical', ['dynamic_form_submission' => $submission_id]);
  }

}
