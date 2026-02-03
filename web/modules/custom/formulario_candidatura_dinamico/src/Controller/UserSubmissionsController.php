<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;
use Drupal\user\UserInterface;

/**
 * Controller for user submissions tab.
 */
class UserSubmissionsController extends ControllerBase {

  /**
   * List all submissions for a specific user.
   */
  public function listUserSubmissions(UserInterface $user) {
    $build = [];
    
    // Get all submissions for this user's email
    $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
    $query = $storage->getQuery()
      ->condition('email', $user->getEmail())
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $ids = $query->execute();
    $submissions = $storage->loadMultiple($ids);

    if (empty($submissions)) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('Este utilizador ainda não submeteu nenhum formulário.') . '</p>',
      ];
      return $build;
    }

    $build['submissions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['user-submissions-list']],
    ];

    foreach ($submissions as $submission) {
      /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
      $form = DynamicForm::load($submission->getFormId());
      $status = $submission->getApprovalStatus();
      
      $build['submissions'][$submission->id()] = [
        '#type' => 'details',
        '#title' => $form ? $form->label() : $this->t('Unknown Form'),
        '#open' => FALSE,
        '#attributes' => ['class' => ['submission-item']],
      ];

      // Submission info
      $build['submissions'][$submission->id()]['info'] = [
        '#type' => 'table',
        '#header' => [$this->t('Campo'), $this->t('Valor')],
        '#rows' => [],
      ];

      $build['submissions'][$submission->id()]['info']['#rows'][] = [
        ['data' => ['#markup' => '<strong>' . $this->t('Data de submissão') . '</strong>']],
        \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'long'),
      ];

      $build['submissions'][$submission->id()]['info']['#rows'][] = [
        ['data' => ['#markup' => '<strong>' . $this->t('Status') . '</strong>']],
        [
          'data' => [
            '#markup' => $this->getStatusBadge($status),
          ],
        ],
      ];

      if ($submission->getApprovalDate()) {
        $build['submissions'][$submission->id()]['info']['#rows'][] = [
          ['data' => ['#markup' => '<strong>' . $this->t('Data da decisão') . '</strong>']],
          \Drupal::service('date.formatter')->format($submission->getApprovalDate(), 'long'),
        ];
      }

      if ($submission->getApprovalNote()) {
        $build['submissions'][$submission->id()]['info']['#rows'][] = [
          ['data' => ['#markup' => '<strong>' . $this->t('Nota do admin') . '</strong>']],
          nl2br(htmlspecialchars($submission->getApprovalNote())),
        ];
      }

      // Show submission data
      $fields = $form ? $form->getFields() : [];
      $data = $submission->getData();

      foreach ($fields as $index => $field) {
        $field_key = 'field_' . $index;
        $value = $data[0][$field_key] ?? '';

        if (is_array($value) && isset($value['fid'])) {
          $file = \Drupal\file\Entity\File::load($value['fid']);
          if ($file) {
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            $value = [
              'data' => [
                '#type' => 'link',
                '#title' => $value['filename'],
                '#url' => Url::fromUri($url),
                '#attributes' => ['target' => '_blank'],
              ],
            ];
          }
        }

        $build['submissions'][$submission->id()]['info']['#rows'][] = [
          ['data' => ['#markup' => '<strong>' . $field['label'] . '</strong>']],
          $value,
        ];
      }

      // Add approval form
      $build['submissions'][$submission->id()]['approval_form'] = \Drupal::formBuilder()->getForm(
        'Drupal\\formulario_candidatura_dinamico\\Form\\SubmissionApprovalForm',
        $submission->id()
      );
    }

    // Attach the library for styling
    $build['#attached']['library'][] = 'formulario_candidatura_dinamico/submission_approval';

    return $build;
  }

  /**
   * Get status badge HTML.
   */
  private function getStatusBadge($status) {
    $icons = [
      'pending' => '⏳',
      'approved' => '✅',
      'denied' => '❌',
    ];
    $labels = [
      'pending' => $this->t('Pendente'),
      'approved' => $this->t('Aprovado'),
      'denied' => $this->t('Negado'),
    ];
    
    $icon = $icons[$status] ?? '⏳';
    $label = $labels[$status] ?? ucfirst($status);
    
    return '<span class="status-badge status-' . $status . '">' . $icon . ' ' . $label . '</span>';
  }

  /**
   * Title callback.
   */
  public function title(UserInterface $user) {
    return $this->t('Submissões de @name', ['@name' => $user->getDisplayName()]);
  }
}
