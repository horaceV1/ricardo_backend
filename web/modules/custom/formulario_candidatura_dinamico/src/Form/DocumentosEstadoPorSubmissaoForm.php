<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;

class DocumentosEstadoPorSubmissaoForm extends FormBase {
  protected $submission;
  protected $fields;
  protected $data;

  public function getFormId() {
    return 'formulario_candidatura_dinamico_documentos_estado_por_submissao_form';
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
    $form_entity = DynamicForm::load($this->submission->getFormId());
    $this->fields = $form_entity->getFields();
    $this->data = $this->submission->getData();

    $options = [
      'submetido' => $this->t('Submetido'),
      'em_falta' => $this->t('Em falta'),
      'caducado' => $this->t('Caducado'),
      'validado' => $this->t('Validado'),
    ];

    foreach ($this->fields as $index => $field) {
      if ($field['type'] === 'documento') {
        $field_key = 'field_' . $index;
        $value = $this->data[0][$field_key] ?? [];
        $estado = $value['estado'] ?? 'submetido';
        $form['estado_' . $field_key] = [
          '#type' => 'select',
          '#title' => $this->t('Estado do documento: @label', ['@label' => $field['label']]),
          '#options' => $options,
          '#default_value' => $estado,
        ];
        if (isset($value['filename'])) {
          $form['file_' . $field_key] = [
            '#markup' => '<div>' . $this->t('Ficheiro: @filename', ['@filename' => $value['filename']]) . '</div>',
          ];
        }
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar estados'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $this->submission->getData();
    foreach ($this->fields as $index => $field) {
      if ($field['type'] === 'documento') {
        $field_key = 'field_' . $index;
        $estado = $form_state->getValue('estado_' . $field_key);
        if (isset($data[0][$field_key])) {
          $data[0][$field_key]['estado'] = $estado;
        }
      }
    }
    $this->submission->set('data', $data);
    $this->submission->save();
    $this->messenger()->addStatus($this->t('Estados dos documentos atualizados.'));
  }
}
