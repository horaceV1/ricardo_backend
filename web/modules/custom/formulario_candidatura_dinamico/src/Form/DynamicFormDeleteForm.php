<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DynamicFormDeleteForm extends EntityConfirmFormBase {
  public function getQuestion() {
    return $this->t('Tem a certeza que quer apagar o formulário "@label"?', ['@label' => $this->getEntity()->label()]);
  }

  public function getCancelUrl() {
    return new Url('entity.dynamic_form.collection');
  }

  public function getConfirmText() {
    return $this->t('Apagar');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getEntity()->delete();
    $form_state->setRedirect('entity.dynamic_form.collection');
  }
}
