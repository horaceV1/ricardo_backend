<?php

namespace Drupal\submission_assignment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the employee (worker/technician) dashboard.
 */
class EmployeeDashboardController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an EmployeeDashboardController.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Employee dashboard showing all assigned submissions.
   */
  public function dashboard() {
    $current_user_id = $this->currentUser()->id();

    // Fetch submissions assigned to this worker.
    $query = $this->database->select('dynamic_form_submission', 'dfs')
      ->fields('dfs', ['id', 'form_id', 'email', 'created', 'assigned_at'])
      ->condition('assigned_to', $current_user_id)
      ->orderBy('created', 'DESC');

    $results = $query->execute()->fetchAll();

    // Compute stats.
    $total = count($results);
    $pending = 0;
    $approved = 0;
    $denied = 0;

    $rows = [];
    foreach ($results as $record) {
      $submission = DynamicFormSubmission::load($record->id);
      if (!$submission) {
        continue;
      }

      $approval_status = $submission->getApprovalStatus();
      if ($approval_status === 'pending') {
        $pending++;
      }
      elseif ($approval_status === 'approved') {
        $approved++;
      }
      elseif ($approval_status === 'denied') {
        $denied++;
      }

      // Load form label.
      $form_entity = DynamicForm::load($record->form_id);
      $form_label = $form_entity ? $form_entity->label() : $record->form_id;

      // Count unread messages (messages not from this user).
      $unread_count = $this->database->select('dynamic_form_messages', 'm')
        ->condition('submission_id', $record->id)
        ->condition('user_id', $current_user_id, '<>')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Status badge.
      $status_markup = '<span class="status-badge status-' . $approval_status . '">' . ucfirst($approval_status) . '</span>';

      $rows[] = [
        $record->id,
        $form_label,
        $record->email ?: '-',
        ['data' => ['#markup' => $status_markup]],
        \Drupal::service('date.formatter')->format($record->created, 'short'),
        $unread_count > 0 ? '💬 ' . $unread_count : '-',
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'view' => [
                'title' => $this->t('Ver Detalhes'),
                'url' => Url::fromRoute('submission_assignment.employee_view_submission', [
                  'submission_id' => $record->id,
                ]),
              ],
              'messages' => [
                'title' => $this->t('Mensagens'),
                'url' => Url::fromRoute('submission_assignment.submission_messages', [
                  'submission_id' => $record->id,
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    $build = [];

    // Stats section.
    $build['stats'] = [
      '#theme' => 'employee_dashboard',
      '#stats' => [
        'total' => $total,
        'pending' => $pending,
        'approved' => $approved,
        'denied' => $denied,
      ],
      '#submissions' => [],
    ];

    // Submissions table.
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Formulário'),
        $this->t('Email do Cliente'),
        $this->t('Estado'),
        $this->t('Data'),
        $this->t('Mensagens'),
        $this->t('Ações'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('Não tem processos atribuídos de momento.'),
      '#attributes' => ['class' => ['employee-submissions-table']],
    ];

    $build['#attached']['library'][] = 'submission_assignment/employee-dashboard';

    return $build;
  }

  /**
   * View a single assigned submission.
   */
  public function viewSubmission($submission_id) {
    $current_user = $this->currentUser();
    $submission = DynamicFormSubmission::load($submission_id);

    if (!$submission) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Check access: assigned worker or admin.
    $assigned_to = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchField();

    $is_assigned = ($assigned_to == $current_user->id());
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_assigned && !$is_admin) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
        $this->t('Não tem permissão para aceder a este processo.')
      );
    }

    // Load form definition.
    $form_entity = DynamicForm::load($submission->getFormId());
    $form_label = $form_entity ? $form_entity->label() : $submission->getFormId();
    $fields = $form_entity ? $form_entity->getFields() : [];
    $data = $submission->getData();

    $build = [];

    // Header.
    $build['header'] = [
      '#markup' => '<h2>' . $this->t('Processo #@id', ['@id' => $submission_id]) . '</h2>',
    ];

    // Basic info table.
    $build['basic_info'] = [
      '#type' => 'table',
      '#header' => [$this->t('Informação'), $this->t('Valor')],
      '#rows' => [
        [$this->t('Email do Cliente'), $submission->getEmail() ?: '-'],
        [$this->t('Formulário'), $form_label],
        [$this->t('Data de submissão'), \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'long')],
        [$this->t('Estado de aprovação'), ucfirst($submission->getApprovalStatus())],
      ],
    ];

    // Submitted data.
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
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($value['fid']);
        if ($file) {
          $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $file_size = $this->formatBytes($file->getSize());
          $value = [
            'data' => [
              '#markup' => '📎 <a href="' . $url . '" target="_blank">' . $value['filename'] . '</a> (' . $file_size . ')',
            ],
          ];
        }
        else {
          $value = ['data' => ['#markup' => '<em>' . $this->t('Ficheiro não disponível') . '</em>']];
        }
      }
      elseif ($value === '' || $value === NULL) {
        $value = ['data' => ['#markup' => '<em style="color: #999;">' . $this->t('Não preenchido') . '</em>']];
      }

      $data_rows[] = [$field['label'], $value];
    }

    $build['submitted_data']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Campo'), $this->t('Resposta')],
      '#rows' => $data_rows,
    ];

    // Approval management section - let assigned worker approve/deny.
    $build['approval_section'] = [
      '#type' => 'details',
      '#title' => $this->t('⚖️ Gestão de Aprovação'),
      '#open' => TRUE,
    ];

    $current_status = $submission->getApprovalStatus();
    $status_colors = [
      'pending' => '#f39c12',
      'approved' => '#27ae60',
      'denied' => '#e74c3c',
    ];
    $status_labels = [
      'pending' => $this->t('Pendente'),
      'approved' => $this->t('Aprovado'),
      'denied' => $this->t('Recusado'),
    ];
    $status_color = $status_colors[$current_status] ?? '#999';
    $status_label = $status_labels[$current_status] ?? ucfirst($current_status);

    $build['approval_section']['current'] = [
      '#markup' => '<div style="margin-bottom: 15px; padding: 12px 16px; background: #f8f9fa; border-left: 4px solid ' . $status_color . '; border-radius: 4px;">'
        . '<strong>' . $this->t('Estado Atual:') . '</strong> '
        . '<span style="color: ' . $status_color . '; font-weight: bold;">' . $status_label . '</span>'
        . ($submission->getApprovalDate() ? '<br><small style="color: #666;">' . $this->t('Última decisão:') . ' ' . \Drupal::service('date.formatter')->format($submission->getApprovalDate(), 'long') . '</small>' : '')
        . ($submission->getApprovalNote() ? '<br><small style="color: #666;">' . $this->t('Nota:') . ' ' . htmlspecialchars($submission->getApprovalNote()) . '</small>' : '')
        . '</div>',
    ];

    // Embed the approval form so the technician can change the status.
    $build['approval_section']['form'] = \Drupal::formBuilder()->getForm(
      'Drupal\formulario_candidatura_dinamico\Form\SubmissionApprovalForm',
      $submission_id
    );

    // Messages section.
    $build['messages_section'] = [
      '#type' => 'details',
      '#title' => $this->t('💬 Mensagens'),
      '#open' => TRUE,
    ];

    // Fetch messages.
    $messages = $this->database->select('dynamic_form_messages', 'm')
      ->fields('m')
      ->condition('submission_id', $submission_id)
      ->orderBy('created', 'ASC')
      ->execute()
      ->fetchAll();

    $messages_list = [];
    foreach ($messages as $msg) {
      $sender = User::load($msg->user_id);
      $is_current_user = ($msg->user_id == $current_user->id());

      $file_markup = '';
      if ($msg->file_id) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($msg->file_id);
        if ($file) {
          $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $file_markup = '<div class="message-file"><a href="' . $file_url . '" target="_blank">📎 ' . $file->getFilename() . '</a></div>';
        }
      }

      $message_class = $is_current_user ? 'message-sent' : 'message-received';
      $sender_name = $sender ? $sender->getDisplayName() : $this->t('Desconhecido');
      $created = \Drupal::service('date.formatter')->format($msg->created, 'custom', 'd/m/Y H:i');

      $messages_list[] = [
        '#markup' => '<div class="message-item ' . $message_class . '">'
          . '<div class="message-header">'
          . '<strong>' . $sender_name . '</strong>'
          . '<span class="message-date">' . $created . '</span>'
          . '</div>'
          . '<div class="message-body">' . nl2br(htmlspecialchars($msg->message)) . '</div>'
          . $file_markup
          . '</div>',
      ];
    }

    if (empty($messages_list)) {
      $messages_list[] = [
        '#markup' => '<p style="color: #666; font-style: italic;">' . $this->t('Ainda não existem mensagens neste processo.') . '</p>',
      ];
    }

    $build['messages_section']['list'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['messages-thread'],
        'style' => 'max-height: 400px; overflow-y: auto; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;',
      ],
      'items' => $messages_list,
    ];

    // Message form.
    $build['messages_section']['form'] = \Drupal::formBuilder()->getForm(
      'Drupal\submission_assignment\Form\SubmissionMessageForm',
      $submission_id
    );

    // Back button.
    $build['back'] = [
      '#type' => 'link',
      '#title' => $this->t('← Voltar aos Meus Processos'),
      '#url' => Url::fromRoute('submission_assignment.employee_dashboard'),
      '#attributes' => ['class' => ['button'], 'style' => 'margin-top: 20px;'],
    ];

    $build['#attached']['library'][] = 'submission_assignment/messages';

    return $build;
  }

  /**
   * Title callback for submission view.
   */
  public function submissionTitle($submission_id) {
    return $this->t('Processo #@id', ['@id' => $submission_id]);
  }

  /**
   * Format bytes to human-readable size.
   */
  protected function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
      $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
  }

}
