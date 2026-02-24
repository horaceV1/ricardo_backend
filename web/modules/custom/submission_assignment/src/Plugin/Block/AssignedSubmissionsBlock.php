<?php

namespace Drupal\submission_assignment\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block showing assigned submissions stats for the current employee.
 *
 * @Block(
 *   id = "assigned_submissions_block",
 *   admin_label = @Translation("Assigned Submissions Summary"),
 *   category = @Translation("Submission Assignment"),
 * )
 */
class AssignedSubmissionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an AssignedSubmissionsBlock.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view employee dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $uid = $this->currentUser->id();

    // Count total assigned.
    $total = $this->database->select('dynamic_form_submission', 's')
      ->condition('assigned_to', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();

    // Count by approval status (via entity load for accuracy).
    $ids = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['id'])
      ->condition('assigned_to', $uid)
      ->execute()
      ->fetchCol();

    $pending = 0;
    $approved = 0;

    if (!empty($ids)) {
      $submissions = \Drupal::entityTypeManager()
        ->getStorage('dynamic_form_submission')
        ->loadMultiple($ids);

      foreach ($submissions as $submission) {
        $status = $submission->getApprovalStatus();
        if ($status === 'pending') {
          $pending++;
        }
        elseif ($status === 'approved') {
          $approved++;
        }
      }
    }

    // Count messages from others.
    $unread_messages = 0;
    if (!empty($ids)) {
      $unread_messages = $this->database->select('dynamic_form_messages', 'm')
        ->condition('submission_id', $ids, 'IN')
        ->condition('user_id', $uid, '<>')
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    $dashboard_link = [
      '#type' => 'link',
      '#title' => $this->t('Ver Painel Completo'),
      '#url' => Url::fromRoute('submission_assignment.employee_dashboard'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return [
      '#theme' => 'assigned_submissions_block',
      '#total' => $total,
      '#pending' => $pending,
      '#approved' => $approved,
      '#unread_messages' => $unread_messages,
      '#dashboard_link' => $dashboard_link,
      '#attached' => [
        'library' => ['submission_assignment/employee-dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 60,
      ],
    ];
  }

}
