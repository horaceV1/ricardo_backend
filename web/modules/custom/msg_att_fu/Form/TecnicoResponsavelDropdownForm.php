<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\user\Entity\User;

class TecnicoResponsavelDropdownForm extends FormBase {
  protected $submission;

  public function getFormId() {
    return 'formulario_candidatura_dinamico_tecnico_responsavel_dropdown_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $dynamic_form_submission = NULL) {
    if (is_numeric($dynamic_form_submission)) {
      $this->submission = DynamicFormSubmission::load($dynamic_form_submission);
    } else {
      $this->submission = $dynamic_form_submission;
    }
    if (!$this->submission) {
      $form['error'] = ['#markup' => $this->t('Submissão não encontrada.')];
      return $form;
    }

    // Controlo de permissões
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('administer site configuration')) {
      $form['error'] = ['#markup' => $this->t('Sem permissão para atribuir técnicos.')];
      return $form;
    }

    // Lista de utilizadores (técnicos)
    $users = User::loadMultiple();
    $options = [];
    foreach ($users as $user) {
      if ($user->isActive() && !$user->isAnonymous()) {
        $options[$user->id()] = $user->getDisplayName();
      }
    }

    $form['tecnico_responsavel'] = [
      '#type' => 'select',
      '#title' => $this->t('Técnico Responsável'),
      '#options' => $options,
      '#default_value' => $this->submission->getTecnicoResponsavelUid() ?? '',
      '#empty_option' => $this->t('- Nenhum -'),
    ];

    $form['submission_id'] = [
      '#type' => 'hidden',
      '#value' => $this->submission->id(),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission_id = $form_state->getValue('submission_id');
    $tecnico_id = $form_state->getValue('tecnico_responsavel');
    
    $submission = DynamicFormSubmission::load($submission_id);
    if ($submission) {
      $submission->setTecnicoResponsavel($tecnico_id);
      \Drupal::messenger()->addStatus($this->t('Técnico responsável atualizado.'));
    }
  }
}

