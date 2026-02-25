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
      $field_approvals = $submission->getFieldApprovals();

      $docs = [];
      foreach ($fields as $index => $field) {
        $field_key = 'field_' . $index;
        // Try both key formats.
        $value = $data[0][$field_key] ?? $data[0][$field['label']] ?? [];

        $approval = $field_approvals[$field['label']] ?? [
          'status' => 'pending',
          'note' => '',
          'date' => NULL,
        ];

        $doc_entry = [
          'label' => $field['label'],
          'type' => $field['type'],
          'approval_status' => $approval['status'],
          'approval_note' => $approval['note'],
          'approval_date' => $approval['date'],
        ];

        if (is_array($value)) {
          $doc_entry['filename'] = $value['filename'] ?? '';
          $fid = $value['fid'] ?? $value['value'] ?? NULL;
          if ($fid) {
            $file = \Drupal\file\Entity\File::load($fid);
            if ($file) {
              $doc_entry['download_url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            }
          }
        }
        else {
          $doc_entry['value'] = $value;
        }

        $docs[] = $doc_entry;
      }

      $result[] = [
        'submission_id' => $submission->id(),
        'form_id' => $submission->getFormId(),
        'form_label' => $form ? $form->label() : $submission->getFormId(),
        'created' => $submission->getCreatedTime(),
        'approval_status' => $submission->getApprovalStatus(),
        'approval_note' => $submission->getApprovalNote(),
        'approval_date' => $submission->getApprovalDate(),
        'fields' => $docs,
      ];
    }
    return new JsonResponse($result);
  }
}
