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

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Submission Data'))
      ->setDescription(t('The submitted form data (JSON).'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the submission was created.'));

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of the submitter.'));

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Assigned To'))
      ->setDescription(t('The user assigned to this submission.'))
      ->setSetting('target_type', 'user');

    $fields['estado'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Estado'))
      ->setDescription(t('The state of the submission.'))
      ->setDefaultValue('submetido');

    $fields['tecnico_responsavel_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Técnico Responsável'))
      ->setDescription(t('The technical staff member responsible.'))
      ->setSetting('target_type', 'user');

    $fields['tecnico_atribuido_em'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Técnico Atribuído Em'))
      ->setDescription(t('When the technician was assigned.'));

    $fields['tecnico_atribuido_por'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Técnico Atribuído Por'))
      ->setDescription(t('Who assigned the technician.'))
      ->setSetting('target_type', 'user');

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
    $value = $this->get('data')->value;
    return $value ? json_decode($value, TRUE) : [];
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
   * Gets the assigned user ID.
   */
  public function getAssignedTo() {
    return $this->get('assigned_to')->target_id;
  }

  /**
   * Sets the assigned user.
   */
  public function setAssignedTo($user_id) {
    $this->set('assigned_to', $user_id);
    return $this;
  }

  /**
   * Gets the estado.
   */
  public function getEstado() {
    return $this->get('estado')->value ?? 'submetido';
  }

  /**
   * Sets the estado.
   */
  public function setEstado($estado) {
    $this->set('estado', $estado);
    return $this;
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
}
