<?php

namespace Drupal\submission_assignment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin controller for viewing all submission assignments.
 */
class AdminAssignmentController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an AdminAssignmentController.
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
   * Admin overview of all assignments.
   */
  public function overview() {
    $build = [];

    // Stats summary.
    $total_submissions = $this->database->select('dynamic_form_submission', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $assigned_count = $this->database->select('dynamic_form_submission', 's')
      ->condition('assigned_to', NULL, 'IS NOT NULL')
      ->condition('assigned_to', 0, '<>')
      ->countQuery()
      ->execute()
      ->fetchField();

    $unassigned_count = $total_submissions - $assigned_count;

    $build['stats'] = [
      '#markup' => '<div class="admin-assignment-stats">'
        . '<div class="stat-card"><span class="stat-number">' . $total_submissions . '</span><span class="stat-label">' . $this->t('Total Submissões') . '</span></div>'
        . '<div class="stat-card stat-assigned"><span class="stat-number">' . $assigned_count . '</span><span class="stat-label">' . $this->t('Atribuídas') . '</span></div>'
        . '<div class="stat-card stat-unassigned"><span class="stat-number">' . $unassigned_count . '</span><span class="stat-label">' . $this->t('Não Atribuídas') . '</span></div>'
        . '</div>',
    ];

    // Filter: show unassigned first, then assigned.
    $query = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['id', 'form_id', 'email', 'created', 'assigned_to', 'assigned_at', 'assigned_by'])
      ->orderBy('assigned_to', 'ASC')
      ->orderBy('created', 'DESC');

    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $record) {
      // Load form label.
      $form_entity = DynamicForm::load($record->form_id);
      $form_label = $form_entity ? $form_entity->label() : $record->form_id;

      // Assigned worker info.
      $assigned_markup = '<span class="badge badge-unassigned">' . $this->t('Não atribuído') . '</span>';
      if (!empty($record->assigned_to)) {
        $worker = User::load($record->assigned_to);
        if ($worker) {
          $assigned_markup = '<span class="badge badge-assigned">' . $worker->getDisplayName() . '</span>';
        }
      }

      // Assigned by info.
      $assigned_by_text = '-';
      if (!empty($record->assigned_by)) {
        $admin = User::load($record->assigned_by);
        $assigned_by_text = $admin ? $admin->getDisplayName() : '-';
      }

      // Assigned date.
      $assigned_date = !empty($record->assigned_at)
        ? \Drupal::service('date.formatter')->format($record->assigned_at, 'short')
        : '-';

      // Message count.
      $msg_count = $this->database->select('dynamic_form_messages', 'm')
        ->condition('submission_id', $record->id)
        ->countQuery()
        ->execute()
        ->fetchField();

      $rows[] = [
        $record->id,
        $form_label,
        $record->email ?: '-',
        ['data' => ['#markup' => $assigned_markup]],
        $assigned_by_text,
        $assigned_date,
        $msg_count > 0 ? '💬 ' . $msg_count : '-',
        \Drupal::service('date.formatter')->format($record->created, 'short'),
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'assign' => [
                'title' => $this->t('Atribuir'),
                'url' => Url::fromRoute('submission_assignment.assign_form', [
                  'submission_id' => $record->id,
                ]),
              ],
              'view' => [
                'title' => $this->t('Ver Submissão'),
                'url' => Url::fromRoute('entity.dynamic_form_submission.canonical', [
                  'dynamic_form_submission' => $record->id,
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

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Formulário'),
        $this->t('Email do Cliente'),
        $this->t('Atribuído a'),
        $this->t('Atribuído por'),
        $this->t('Data Atribuição'),
        $this->t('Mensagens'),
        $this->t('Data Submissão'),
        $this->t('Ações'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('Não existem submissões.'),
      '#attributes' => ['class' => ['admin-assignments-table']],
    ];

    $build['#attached']['library'][] = 'submission_assignment/assignment';

    // Disable caching - this page is dynamic.
    $build['#cache'] = [
      'max-age' => 0,
    ];

    return $build;
  }

}
