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

    // Get per-field approvals for inline display.
    $field_approvals = $dynamic_form_submission->getFieldApprovals();
    $status_icons = [
      'pending' => '⏳',
      'approved' => '✅',
      'denied' => '❌',
    ];

    foreach ($fields as $index => $field) {
      $field_key = 'field_' . $index;
      $value = $data[0][$field_key] ?? $data[0][$field['label']] ?? '';

      // Handle file uploads - multiple storage formats.
      if (is_array($value)) {
        $fid = $value['fid'] ?? $value['value'] ?? NULL;
        $filename = $value['filename'] ?? '';
        $uri = $value['uri'] ?? '';

        if ($fid) {
          $file = \Drupal\file\Entity\File::load($fid);
          if ($file) {
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            $value = [
              'data' => [
                '#type' => 'link',
                '#title' => $filename ?: $file->getFilename(),
                '#url' => \Drupal\Core\Url::fromUri($url),
                '#attributes' => ['target' => '_blank'],
              ],
            ];
          }
          elseif ($uri) {
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
            $value = [
              'data' => [
                '#markup' => '📎 <a href="' . $url . '" target="_blank">' . htmlspecialchars($filename) . '</a>',
              ],
            ];
          }
        }
      }

      // Add per-field approval badge next to the label.
      $approval = $field_approvals[$field['label']] ?? ['status' => 'pending', 'note' => '', 'date' => NULL];
      $icon = $status_icons[$approval['status']] ?? '⏳';
      $label_markup = '<strong>' . $field['label'] . '</strong> ' . $icon;
      if (!empty($approval['note'])) {
        $label_markup .= '<br><small style="color: #888;">' . htmlspecialchars($approval['note']) . '</small>';
      }

      $build['info']['#rows'][] = [
        ['data' => ['#markup' => $label_markup]],
        $value,
      ];
    }

    // Attach approval CSS and JS libraries.
    $build['#attached']['library'][] = 'formulario_candidatura_dinamico/submission_approval';

    // Add overall approval status section (computed from per-field).
    $status = $dynamic_form_submission->getApprovalStatus();
    $status_labels = [
      'pending' => $this->t('Pendente'),
      'approved' => $this->t('Aprovado'),
      'denied' => $this->t('Recusado'),
    ];
    $status_colors = [
      'pending' => '#f39c12',
      'approved' => '#27ae60',
      'denied' => '#e74c3c',
    ];
    $status_class = 'status-' . $status;
    $status_label = $status_labels[$status] ?? ucfirst($status);
    $status_icon = $status_icons[$status] ?? '⏳';
    $status_color = $status_colors[$status] ?? '#999';

    $build['approval_status'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['submission-approval-status', $status_class]],
      '#weight' => -10,
    ];

    $build['approval_status']['status'] = [
      '#markup' => '<div class="approval-badge"><h3>' . $status_icon . ' '
        . $this->t('Estado Geral: @status', ['@status' => $status_label]) . '</h3>'
        . '<small style="color: #666;">' . $this->t('Calculado automaticamente a partir dos documentos individuais.') . '</small>'
        . '</div>',
    ];

    // Per-field approval summary.
    if (!empty($field_approvals)) {
      $field_summary_rows = [];
      foreach ($field_approvals as $label => $approval) {
        $a_icon = $status_icons[$approval['status']] ?? '⏳';
        $a_label = $status_labels[$approval['status']] ?? ucfirst($approval['status']);
        $a_color = $status_colors[$approval['status']] ?? '#999';
        $note_text = !empty($approval['note']) ? htmlspecialchars($approval['note']) : '-';

        $field_summary_rows[] = [
          htmlspecialchars($label),
          ['data' => ['#markup' => '<span style="color: ' . $a_color . '; font-weight: bold;">' . $a_icon . ' ' . $a_label . '</span>']],
          $note_text,
        ];
      }

      $build['approval_status']['field_summary'] = [
        '#type' => 'table',
        '#header' => [$this->t('Documento'), $this->t('Estado'), $this->t('Nota')],
        '#rows' => $field_summary_rows,
        '#attributes' => ['style' => 'margin-top: 15px;'],
      ];
    }

    // Add per-field approval form.
    $build['approval_form'] = \Drupal::formBuilder()->getForm(
      'Drupal\\formulario_candidatura_dinamico\\Form\\SubmissionApprovalForm',
      $dynamic_form_submission->id()
    );

    // Add assignment section if submission_assignment module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('submission_assignment')) {
      $connection = \Drupal::database();
      $assignment = $connection->select('dynamic_form_submission', 's')
        ->fields('s', ['assigned_to', 'assigned_at', 'assigned_by'])
        ->condition('id', $dynamic_form_submission->id())
        ->execute()
        ->fetchObject();

      $build['assignment_section'] = [
        '#type' => 'details',
        '#title' => $this->t('👤 Atribuição de Funcionário'),
        '#open' => TRUE,
        '#weight' => -5,
      ];

      if (!empty($assignment->assigned_to)) {
        $worker = \Drupal\user\Entity\User::load($assignment->assigned_to);
        $assigned_by_user = !empty($assignment->assigned_by) ? \Drupal\user\Entity\User::load($assignment->assigned_by) : NULL;
        $build['assignment_section']['current'] = [
          '#markup' => '<div class="current-assignment-card">'
            . '<p><strong>' . $this->t('Atribuído a:') . '</strong> '
            . ($worker ? $worker->getDisplayName() . ' (' . $worker->getEmail() . ')' : $this->t('Desconhecido'))
            . '</p>'
            . ($assigned_by_user ? '<p><strong>' . $this->t('Atribuído por:') . '</strong> ' . $assigned_by_user->getDisplayName() . '</p>' : '')
            . (!empty($assignment->assigned_at) ? '<p><strong>' . $this->t('Data:') . '</strong> ' . \Drupal::service('date.formatter')->format($assignment->assigned_at, 'short') . '</p>' : '')
            . '</div>',
        ];
      }
      else {
        $build['assignment_section']['current'] = [
          '#markup' => '<p style="color: #999;"><em>' . $this->t('Ainda não atribuído a nenhum funcionário.') . '</em></p>',
        ];
      }

      // Link to assignment form.
      if (\Drupal::currentUser()->hasPermission('assign submissions to workers')) {
        $build['assignment_section']['assign_link'] = [
          '#type' => 'link',
          '#title' => $this->t('Atribuir / Alterar Funcionário'),
          '#url' => \Drupal\Core\Url::fromRoute('submission_assignment.assign_form', [
            'submission_id' => $dynamic_form_submission->id(),
          ]),
          '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        // Link to messages.
        $build['assignment_section']['messages_link'] = [
          '#type' => 'link',
          '#title' => $this->t('💬 Ver Mensagens'),
          '#url' => \Drupal\Core\Url::fromRoute('submission_assignment.submission_messages', [
            'submission_id' => $dynamic_form_submission->id(),
          ]),
          '#attributes' => ['class' => ['button'], 'style' => 'margin-left: 10px;'],
        ];
      }

      $build['#attached']['library'][] = 'submission_assignment/assignment';
    }

    // Disable caching.
    $build['#cache'] = ['max-age' => 0];

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
