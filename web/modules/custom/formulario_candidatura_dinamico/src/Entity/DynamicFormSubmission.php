<?php
namespace Drupal\formulario_candidatura_dinamico\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Dynamic Form Submission entity.
 *
 * @ContentEntityType(
 *   id = "dynamic_form_submission",
 *   label = @Translation("Dynamic Form Submission"),
 *   base_table = "dynamic_form_submission",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\formulario_candidatura_dinamico\DynamicFormSubmissionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/admin/content/formularios-dinamicos/submission/{dynamic_form_submission}",
 *     "delete-form" = "/admin/content/formularios-dinamicos/submission/{dynamic_form_submission}/delete",
 *   },
 *   admin_permission = "administer site configuration",
 * )
 */
class DynamicFormSubmission extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['form_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Form ID'))
      ->setDescription(t('The ID of the dynamic form.'))
      ->setRequired(TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Submission Data'))
      ->setDescription(t('The submitted form data.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the submission was created.'));

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of the submitter.'));

    $fields['approval_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Approval Status'))
      ->setDescription(t('The approval status of the submission.'))
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'denied' => 'Denied',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['approval_note'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Approval Note'))
      ->setDescription(t('Admin note regarding the approval or denial.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['approval_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Approval Date'))
      ->setDescription(t('The date when the approval decision was made.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_approvals'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Field Approvals'))
      ->setDescription(t('Per-field approval statuses, notes, and dates.'));

    return $fields;
  }

  /**
   * Gets the form ID.
   */
  public function getFormId() {
    return $this->get('form_id')->value;
  }

  /**
   * Gets the submission data.
   */
  public function getData() {
    return $this->get('data')->getValue();
  }

  /**
   * Gets the email.
   */
  public function getEmail() {
    return $this->get('email')->value;
  }

  /**
   * Gets the created timestamp.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Gets the approval status.
   */
  public function getApprovalStatus() {
    return $this->get('approval_status')->value ?? 'pending';
  }

  /**
   * Sets the approval status.
   */
  public function setApprovalStatus($status) {
    $this->set('approval_status', $status);
    return $this;
  }

  /**
   * Gets the approval note.
   */
  public function getApprovalNote() {
    return $this->get('approval_note')->value;
  }

  /**
   * Sets the approval note.
   */
  public function setApprovalNote($note) {
    $this->set('approval_note', $note);
    return $this;
  }

  /**
   * Gets the approval date.
   */
  public function getApprovalDate() {
    return $this->get('approval_date')->value;
  }

  /**
   * Sets the approval date.
   */
  public function setApprovalDate($timestamp) {
    $this->set('approval_date', $timestamp);
    return $this;
  }

  /**
   * Gets the assigned worker user ID.
   */
  public function getAssignedTo() {
    if ($this->hasField('assigned_to')) {
      return $this->get('assigned_to')->target_id;
    }
    return NULL;
  }

  /**
   * Sets the assigned worker.
   */
  public function setAssignedTo($user_id) {
    if ($this->hasField('assigned_to')) {
      $this->set('assigned_to', $user_id);
    }
    return $this;
  }

  /**
   * Gets when the assignment was made.
   */
  public function getAssignedAt() {
    if ($this->hasField('assigned_at')) {
      return $this->get('assigned_at')->value;
    }
    return NULL;
  }

  /**
   * Gets the admin who made the assignment.
   */
  public function getAssignedBy() {
    if ($this->hasField('assigned_by')) {
      return $this->get('assigned_by')->target_id;
    }
    return NULL;
  }

  /**
   * Gets per-field approval data.
   *
   * @return array
   *   Keyed by field label, each containing 'status', 'note', 'date'.
   */
  public function getFieldApprovals() {
    $value = $this->get('field_approvals')->getValue();
    if (!empty($value[0]) && is_array($value[0])) {
      return $value[0];
    }
    return [];
  }

  /**
   * Sets per-field approval data.
   *
   * @param array $approvals
   *   Keyed by field label, each containing 'status', 'note', 'date'.
   */
  public function setFieldApprovals(array $approvals) {
    $this->set('field_approvals', $approvals);
    return $this;
  }

  /**
   * Gets the approval for a specific field.
   *
   * @param string $field_label
   *   The field label.
   *
   * @return array
   *   The approval data with 'status', 'note', 'date' keys.
   */
  public function getFieldApproval($field_label) {
    $approvals = $this->getFieldApprovals();
    return $approvals[$field_label] ?? [
      'status' => 'pending',
      'note' => '',
      'date' => NULL,
    ];
  }

  /**
   * Sets the approval for a specific field.
   */
  public function setFieldApproval($field_label, $status, $note = '', $date = NULL) {
    $approvals = $this->getFieldApprovals();
    $approvals[$field_label] = [
      'status' => $status,
      'note' => $note,
      'date' => $date ?: \Drupal::time()->getRequestTime(),
    ];
    $this->setFieldApprovals($approvals);
    return $this;
  }

  /**
   * Computes the overall approval status from individual field approvals.
   *
   * - If any field is denied => 'denied'
   * - If all fields are approved => 'approved'
   * - Otherwise => 'pending'
   */
  public function computeOverallStatus() {
    $approvals = $this->getFieldApprovals();
    if (empty($approvals)) {
      return 'pending';
    }

    $all_approved = TRUE;
    foreach ($approvals as $approval) {
      if (($approval['status'] ?? 'pending') === 'denied') {
        return 'denied';
      }
      if (($approval['status'] ?? 'pending') !== 'approved') {
        $all_approved = FALSE;
      }
    }

    return $all_approved ? 'approved' : 'pending';
  }

}
