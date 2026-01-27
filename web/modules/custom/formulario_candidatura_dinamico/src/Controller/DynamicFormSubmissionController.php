<?php
namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Dynamic Form Submission operations.
 */
class DynamicFormSubmissionController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Lists all submissions for a specific form.
   */
  public function listSubmissions($dynamic_form) {
    if (is_string($dynamic_form)) {
      $dynamic_form = DynamicForm::load($dynamic_form);
    }

    if (!$dynamic_form) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $submissions = $this->entityTypeManager
      ->getStorage('dynamic_form_submission')
      ->loadByProperties(['form_id' => $dynamic_form->id()]);

    $rows = [];
    foreach ($submissions as $submission) {
      /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
      $rows[] = [
        $submission->id(),
        $submission->getEmail(),
        \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'short'),
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'view' => [
                'title' => $this->t('Ver'),
                'url' => \Drupal\Core\Url::fromRoute('entity.dynamic_form_submission.canonical', [
                  'dynamic_form_submission' => $submission->id(),
                ]),
              ],
              'delete' => [
                'title' => $this->t('Eliminar'),
                'url' => \Drupal\Core\Url::fromRoute('entity.dynamic_form_submission.delete_form', [
                  'dynamic_form_submission' => $submission->id(),
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    $build['submissions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Email'),
        $this->t('Data'),
        $this->t('Operações'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('Ainda não há submissões para este formulário.'),
    ];

    return $build;
  }

  /**
   * Title callback for submissions list.
   */
  public function submissionsTitle($dynamic_form) {
    if (is_string($dynamic_form)) {
      $dynamic_form = DynamicForm::load($dynamic_form);
    }
    return $this->t('Submissões: @form', ['@form' => $dynamic_form->label()]);
  }

  /**
   * Title callback for form submission page.
   */
  public function formTitle($form_id) {
    $dynamic_form = DynamicForm::load($form_id);
    if ($dynamic_form) {
      return $dynamic_form->label();
    }
    return $this->t('Formulário');
  }

  /**
   * View a single submission.
   */
  public function viewSubmission($dynamic_form_submission) {
    if (is_numeric($dynamic_form_submission)) {
      $dynamic_form_submission = DynamicFormSubmission::load($dynamic_form_submission);
    }

    if (!$dynamic_form_submission) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $form = DynamicForm::load($dynamic_form_submission->getFormId());
    $fields = $form->getFields();
    $data = $dynamic_form_submission->getData();

    $build['info'] = [
      '#type' => 'table',
      '#header' => [$this->t('Campo'), $this->t('Valor')],
      '#rows' => [],
    ];

    $build['info']['#rows'][] = [
      ['data' => ['#markup' => '<strong>' . $this->t('Email') . '</strong>']],
      $dynamic_form_submission->getEmail(),
    ];

    $build['info']['#rows'][] = [
      ['data' => ['#markup' => '<strong>' . $this->t('Data de submissão') . '</strong>']],
      \Drupal::service('date.formatter')->format($dynamic_form_submission->getCreatedTime(), 'long'),
    ];

    foreach ($fields as $index => $field) {
      $field_key = 'field_' . $index;
      $value = $data[0][$field_key] ?? '';

      if (is_array($value) && isset($value['fid'])) {
        // File field
        $file = \Drupal\file\Entity\File::load($value['fid']);
        if ($file) {
          $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $value = [
            'data' => [
              '#type' => 'link',
              '#title' => $value['filename'],
              '#url' => \Drupal\Core\Url::fromUri($url),
              '#attributes' => ['target' => '_blank'],
            ],
          ];
        }
      }

      $build['info']['#rows'][] = [
        ['data' => ['#markup' => '<strong>' . $field['label'] . '</strong>']],
        $value,
      ];
    }

    return $build;
  }

  /**
   * Title callback for submission view.
   */
  public function submissionTitle($dynamic_form_submission) {
    if (is_numeric($dynamic_form_submission)) {
      $dynamic_form_submission = DynamicFormSubmission::load($dynamic_form_submission);
    }
    return $this->t('Submissão #@id', ['@id' => $dynamic_form_submission->id()]);
  }
}
