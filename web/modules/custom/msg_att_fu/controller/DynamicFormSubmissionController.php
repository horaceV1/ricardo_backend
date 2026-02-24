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
              'messages' => [
                'title' => $this->t('Mensagens'),
                'url' => \Drupal\Core\Url::fromRoute('formulario_candidatura_dinamico.submission_messages', [
                  'submission_id' => $submission->id(),
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

    // Access control: check if user can view this submission
    $current_user = \Drupal::currentUser();
    $is_admin = $current_user->hasPermission('administer site configuration');
    
    if (!$is_admin) {
      // If not admin, check if assigned to this user
      $connection = \Drupal::database();
      $assigned_to = $connection->select('dynamic_form_submission', 's')
        ->fields('s', ['assigned_to'])
        ->condition('id', $dynamic_form_submission->id())
        ->execute()
        ->fetchField();

      if ($assigned_to != $current_user->id()) {
        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
          $this->t('You do not have permission to view this submission.')
        );
      }
    }

    $form = DynamicForm::load($dynamic_form_submission->getFormId());
    $fields = $form->getFields();
    $data = $dynamic_form_submission->getData();

    // Header da submissão
    $build['header'] = [
      '#markup' => '<h2>Submissão #' . $dynamic_form_submission->id() . '</h2>',
    ];

    // Informação básica
    $build['basic_info'] = [
      '#type' => 'table',
      '#header' => [$this->t('Informação'), $this->t('Valor')],
      '#rows' => [
        [$this->t('Email'), $dynamic_form_submission->getEmail()],
        [$this->t('Formulário'), $form->label()],
        [$this->t('Data de submissão'), \Drupal::service('date.formatter')->format($dynamic_form_submission->getCreatedTime(), 'long')],
      ],
    ];

    // Atribuição de funcionário (admin only)
    if ($is_admin) {
      $connection = \Drupal::database();
      $assigned_to = $connection->select('dynamic_form_submission', 's')
        ->fields('s', ['assigned_to'])
        ->condition('id', $dynamic_form_submission->id())
        ->execute()
        ->fetchField();

      $build['assign_section'] = [
        '#type' => 'details',
        '#title' => $this->t('👤 Atribuir Funcionário'),
        '#open' => TRUE,
        '#weight' => -5,
      ];

      if ($assigned_to) {
        $employee = \Drupal\user\Entity\User::load($assigned_to);
        $build['assign_section']['current'] = [
          '#markup' => '<p><strong>' . $this->t('Atualmente atribuído a:') . '</strong> ' . 
                       $employee->getDisplayName() . ' (' . $employee->getEmail() . ')</p>',
        ];
      } else {
        $build['assign_section']['current'] = [
          '#markup' => '<p style="color: #999;"><em>' . $this->t('Ainda não atribuído a nenhum funcionário.') . '</em></p>',
        ];
      }

      $build['assign_section']['form'] = \Drupal::formBuilder()->getForm(
        'Drupal\\formulario_candidatura_dinamico\\Form\\AssignSubmissionForm',
        $dynamic_form_submission->id()
      );
    }

    // Dados submetidos pelo cliente
    $build['submitted_data'] = [
      '#type' => 'details',
      '#title' => $this->t('📋 Dados Submetidos pelo Cliente'),
      '#open' => TRUE,
    ];

    $data_rows = [];
    foreach ($fields as $index => $field) {
      $field_key = 'field_' . $index;
      $value = $data[0][$field_key] ?? '';

      if (is_array($value) && isset($value['fid'])) {
        // Campo de ficheiro
        $file = \Drupal\file\Entity\File::load($value['fid']);
        if ($file) {
          $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $file_size = $this->formatBytes($file->getSize());
          $value = [
            'data' => [
              '#markup' => '📎 <a href="' . $url . '" target="_blank" style="color: #0073aa;">' . 
                           $value['filename'] . '</a> <span style="color: #666;">(' . $file_size . ')</span>',
            ],
          ];
        } else {
          $value = ['data' => ['#markup' => '<em style="color: #cc0000;">Ficheiro não disponível</em>']];
        }
      } elseif ($value === '' || $value === NULL) {
        $value = ['data' => ['#markup' => '<em style="color: #999;">Não preenchido</em>']];
      }

      $data_rows[] = [$field['label'], $value];
    }

    $build['submitted_data']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Campo'), $this->t('Resposta')],
      '#rows' => $data_rows,
    ];

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

  /**
   * Custom access check for submission view.
   */
  public function checkSubmissionAccess($dynamic_form_submission) {
    $current_user = \Drupal::currentUser();
    
    // Admin always has access
    if ($current_user->hasPermission('administer site configuration')) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }

    // Load submission
    if (is_numeric($dynamic_form_submission)) {
      $dynamic_form_submission = DynamicFormSubmission::load($dynamic_form_submission);
    }

    if (!$dynamic_form_submission) {
      return \Drupal\Core\Access\AccessResult::forbidden();
    }

    // Check if assigned to current user
    $connection = \Drupal::database();
    $assigned_to = $connection->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to'])
      ->condition('id', $dynamic_form_submission->id())
      ->execute()
      ->fetchField();

    if ($assigned_to == $current_user->id()) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }

    return \Drupal\Core\Access\AccessResult::forbidden();
  }

  /**
   * Format bytes to human-readable size.
   */
  private function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
  }
}
