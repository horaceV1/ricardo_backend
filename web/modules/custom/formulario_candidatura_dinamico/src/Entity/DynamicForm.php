<?php
namespace Drupal\formulario_candidatura_dinamico\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Dynamic Form configuration entity.
 *
 * @ConfigEntityType(
 *   id = "dynamic_form",
 *   label = @Translation("Dynamic Form"),
 *   handlers = {
 *     "list_builder" = "Drupal\formulario_candidatura_dinamico\DynamicFormListBuilder",
 *     "view_builder" = "Drupal\formulario_candidatura_dinamico\DynamicFormViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\formulario_candidatura_dinamico\Form\DynamicFormAddForm",
 *       "edit" = "Drupal\formulario_candidatura_dinamico\Form\DynamicFormEditForm",
 *       "delete" = "Drupal\formulario_candidatura_dinamico\Form\DynamicFormDeleteForm"
 *     }
 *   },
 *   config_prefix = "dynamic_form",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "fields"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/formularios-dinamicos/{dynamic_form}",
 *     "collection" = "/admin/content/formularios-dinamicos",
 *     "add-form" = "/admin/content/formularios-dinamicos/add",
 *     "edit-form" = "/admin/content/formularios-dinamicos/{dynamic_form}/edit",
 *     "delete-form" = "/admin/content/formularios-dinamicos/{dynamic_form}/delete"
 *   }
 * )
 */
class DynamicForm extends ConfigEntityBase {
  /**
   * The Dynamic Form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Dynamic Form label.
   *
   * @var string
   */
  protected $label;

  /**
   * The form fields.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Gets the form fields.
   *
   * @return array
   *   The form fields.
   */
  public function getFields() {
    return $this->fields ?? [];
  }

  /**
   * Sets the form fields.
   *
   * @param array $fields
   *   The form fields.
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
    return $this;
  }
}
