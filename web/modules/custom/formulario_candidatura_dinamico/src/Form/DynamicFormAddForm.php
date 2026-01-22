<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class DynamicFormAddForm extends EntityForm {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome do formulário'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\formulario_candidatura_dinamico\Entity\DynamicForm::load',
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $status = $entity->save();
    $form_state->setRedirect('entity.dynamic_form.collection');
  }
}
