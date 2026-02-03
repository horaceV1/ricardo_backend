<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;

/**
 * Form for approving/denying form submissions.
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
    } else {
      $submission = $dynamic_form_submission;
    }

    if (!$submission) {
      return $form;
    }

    $form_state->set('submission', $submission);

    $current_status = $submission->getApprovalStatus();
    $current_note = $submission->getApprovalNote();

    $form['approval_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Approval Management'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['approval-form-section']],
    ];

    $form['approval_section']['current_status'] = [
      '#type' => 'item',
      '#title' => $this->t('Current Status'),
      '#markup' => '<div class="current-status status-' . $current_status . '">' . 
                   '<strong>' . ucfirst($current_status) . '</strong></div>',
    ];

    if ($submission->getApprovalDate()) {
      $form['approval_section']['approval_date'] = [
        '#type' => 'item',
        '#title' => $this->t('Decision Date'),
        '#markup' => \Drupal::service('date.formatter')->format($submission->getApprovalDate(), 'long'),
      ];
    }

    $form['approval_section']['approval_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Set Approval Status'),
      '#options' => [
        'pending' => $this->t('⏳ Pending'),
        'approved' => $this->t('✅ Approved'),
        'denied' => $this->t('❌ Denied'),
      ],
      '#default_value' => $current_status,
      '#required' => TRUE,
    ];

    $form['approval_section']['approval_note'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Admin Note'),
      '#description' => $this->t('This note will be visible to the user on the frontend.'),
      '#default_value' => $current_note,
      '#rows' => 4,
      '#placeholder' => $this->t('Add a note about your decision...'),
    ];

    $form['approval_section']['actions'] = [
      '#type' => 'actions',
    ];

    $form['approval_section']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Decision'),
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
    $status = $form_state->getValue('approval_status');
    $note = $form_state->getValue('approval_note');

    $submission->setApprovalStatus($status);
    $submission->setApprovalNote($note);
    $submission->setApprovalDate(time());
    $submission->save();

    $status_labels = [
      'pending' => $this->t('Pending'),
      'approved' => $this->t('Approved'),
      'denied' => $this->t('Denied'),
    ];

    $this->messenger()->addStatus(
      $this->t('Submission has been marked as @status.', [
        '@status' => $status_labels[$status],
      ])
    );

    \Drupal::logger('formulario_candidatura_dinamico')->info(
      'Submission @id status changed to @status by user @uid',
      [
        '@id' => $submission->id(),
        '@status' => $status,
        '@uid' => \Drupal::currentUser()->id(),
      ]
    );
  }
}
