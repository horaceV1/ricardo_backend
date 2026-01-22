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
}
