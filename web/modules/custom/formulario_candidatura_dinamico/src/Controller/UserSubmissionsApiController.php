<?php
namespace Drupal\formulario_candidatura_dinamico\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;

class UserSubmissionsApiController extends ControllerBase {
  public function getUserSubmissions() {
    $current_user = \Drupal::currentUser();
    $email = $current_user->getEmail();
    if (!$email) {
      return new JsonResponse(['error' => 'Not authenticated'], 403);
    }
    $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
    $submissions = $storage->loadByProperties(['email' => $email]);
    $result = [];
    foreach ($submissions as $submission) {
      /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
      $form = DynamicForm::load($submission->getFormId());
      $fields = $form ? $form->getFields() : [];
      $data = $submission->getData();
      $docs = [];
      foreach ($fields as $index => $field) {
        if ($field['type'] === 'documento') {
          $field_key = 'field_' . $index;
          $value = $data[0][$field_key] ?? [];
          $docs[] = [
            'label' => $field['label'],
            'filename' => $value['filename'] ?? '',
            'estado' => $value['estado'] ?? 'submetido',
          ];
        }
      }
      $result[] = [
        'submission_id' => $submission->id(),
        'form_id' => $submission->getFormId(),
        'created' => $submission->getCreatedTime(),
        'documentos' => $docs,
      ];
    }
    return new JsonResponse($result);
  }
}
