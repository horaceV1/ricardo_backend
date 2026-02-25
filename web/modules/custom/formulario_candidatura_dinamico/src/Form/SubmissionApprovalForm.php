<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;

/**
 * Form for approving/denying individual document fields in a submission.
 */
class SubmissionApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $dynamic_form_submission = NULL) {
    if (is_numeric($dynamic_form_submission)) {
      $submission = DynamicFormSubmission::load($dynamic_form_submission);
    }
    else {
      $submission = $dynamic_form_submission;
    }

    if (!$submission) {
      return $form;
    }

    $form_state->set('submission', $submission);

    // Load form definition to get field labels and types.
    $form_entity = DynamicForm::load($submission->getFormId());
    $fields = $form_entity ? $form_entity->getFields() : [];
    $data = $submission->getData();
    $field_approvals = $submission->getFieldApprovals();

    // Overall status display.
    $overall_status = $submission->getApprovalStatus();
    $status_colors = [
      'pending' => '#f39c12',
      'approved' => '#27ae60',
      'denied' => '#e74c3c',
    ];
    $status_labels = [
      'pending' => $this->t('Pendente'),
      'approved' => $this->t('Aprovado'),
      'denied' => $this->t('Recusado'),
    ];
    $status_color = $status_colors[$overall_status] ?? '#999';
    $status_label = $status_labels[$overall_status] ?? ucfirst($overall_status);

    $form['overall_status'] = [
      '#markup' => '<div style="margin-bottom: 20px; padding: 12px 16px; background: #f8f9fa; border-left: 4px solid ' . $status_color . '; border-radius: 4px;">'
        . '<strong>' . $this->t('Estado Geral:') . '</strong> '
        . '<span style="color: ' . $status_color . '; font-weight: bold;">' . $status_label . '</span>'
        . '<br><small style="color: #666;">' . $this->t('O estado geral é calculado automaticamente a partir dos documentos individuais.') . '</small>'
        . '</div>',
    ];

    $form['fields_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['per-field-approvals']],
    ];

    foreach ($fields as $index => $field) {
      $label = $field['label'];
      $field_key = 'field_' . $index;

      // Get field value (try both key formats).
      $value = $data[0][$field_key] ?? $data[0][$label] ?? '';

      // Get existing per-field approval.
      $approval = $field_approvals[$label] ?? [
        'status' => 'pending',
        'note' => '',
        'date' => NULL,
      ];

      $safe_key = 'field_approval_' . $index;

      $form['fields_container'][$safe_key] = [
        '#type' => 'fieldset',
        '#title' => $label,
        '#attributes' => [
          'class' => ['field-approval-item'],
          'style' => 'margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #fafafa;',
        ],
      ];

      // Show the submitted value/file.
      $value_markup = '';
      if (is_array($value)) {
        $fid = $value['fid'] ?? $value['value'] ?? NULL;
        $filename = $value['filename'] ?? '';
        $uri = $value['uri'] ?? '';

        if ($fid) {
          $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
          if ($file) {
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            $value_markup = '📎 <a href="' . $url . '" target="_blank" style="color: #0073e6; font-weight: 500;">' . htmlspecialchars($filename ?: $file->getFilename()) . '</a>';
          }
          elseif ($uri) {
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
            $value_markup = '📎 <a href="' . $url . '" target="_blank" style="color: #0073e6; font-weight: 500;">' . htmlspecialchars($filename) . '</a>';
          }
          else {
            $value_markup = '<em style="color: #999;">' . $this->t('Ficheiro não disponível') . '</em>';
          }
        }
        else {
          $value_markup = '<em style="color: #999;">' . $this->t('Não preenchido') . '</em>';
        }
      }
      elseif ($value === '' || $value === NULL) {
        $value_markup = '<em style="color: #999;">' . $this->t('Não preenchido') . '</em>';
      }
      else {
        $value_markup = htmlspecialchars($value);
      }

      $form['fields_container'][$safe_key]['submitted_value'] = [
        '#markup' => '<div style="margin-bottom: 10px;"><strong>' . $this->t('Valor submetido:') . '</strong> ' . $value_markup . '</div>',
      ];

      // Status radios for this field.
      $form['fields_container'][$safe_key]['status'] = [
        '#type' => 'radios',
        '#title' => $this->t('Estado'),
        '#options' => [
          'pending' => $this->t('⏳ Pendente'),
          'approved' => $this->t('✅ Aprovado'),
          'denied' => $this->t('❌ Recusado'),
        ],
        '#default_value' => $approval['status'],
        '#attributes' => ['class' => ['field-status-radios']],
      ];

      // Note for this field.
      $form['fields_container'][$safe_key]['note'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Nota'),
        '#default_value' => $approval['note'],
        '#rows' => 2,
        '#placeholder' => $this->t('Nota sobre este documento...'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar Decisões'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'formulario_candidatura_dinamico/submission_approval';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission = $form_state->get('submission');

    // Load form definition for field labels.
    $form_entity = DynamicForm::load($submission->getFormId());
    $fields = $form_entity ? $form_entity->getFields() : [];

    $now = \Drupal::time()->getRequestTime();
    $field_approvals = [];

    foreach ($fields as $index => $field) {
      $safe_key = 'field_approval_' . $index;
      $status = $form_state->getValue([$safe_key, 'status']) ?? 'pending';
      $note = $form_state->getValue([$safe_key, 'note']) ?? '';

      $field_approvals[$field['label']] = [
        'status' => $status,
        'note' => $note,
        'date' => $now,
      ];
    }

    $submission->setFieldApprovals($field_approvals);

    // Auto-compute overall status from individual fields.
    $overall = $submission->computeOverallStatus();
    $submission->setApprovalStatus($overall);
    $submission->setApprovalDate($now);

    // Build a combined note from individual notes.
    $combined_notes = [];
    foreach ($field_approvals as $label => $approval) {
      if (!empty($approval['note'])) {
        $combined_notes[] = $label . ': ' . $approval['note'];
      }
    }
    if (!empty($combined_notes)) {
      $submission->setApprovalNote(implode("\n", $combined_notes));
    }

    $submission->save();

    $status_labels = [
      'pending' => $this->t('Pendente'),
      'approved' => $this->t('Aprovado'),
      'denied' => $this->t('Recusado'),
    ];

    $this->messenger()->addStatus(
      $this->t('As decisões foram guardadas. Estado geral: @status.', [
        '@status' => $status_labels[$overall],
      ])
    );

    \Drupal::logger('formulario_candidatura_dinamico')->info(
      'Submission @id field approvals updated by user @uid. Overall: @status',
      [
        '@id' => $submission->id(),
        '@uid' => \Drupal::currentUser()->id(),
        '@status' => $overall,
      ]
    );
  }

}
