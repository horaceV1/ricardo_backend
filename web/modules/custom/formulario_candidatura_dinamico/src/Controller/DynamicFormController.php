<?php
namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;

/**
 * Controller for Dynamic Form entities.
 */
class DynamicFormController extends ControllerBase {

  /**
   * Title callback for the dynamic form view page.
   *
   * @param \Drupal\formulario_candidatura_dinamico\Entity\DynamicForm $dynamic_form
   *   The dynamic form entity.
   *
   * @return string
   *   The page title.
   */
  public function title(DynamicForm $dynamic_form) {
    return $dynamic_form->label();
  }

}
