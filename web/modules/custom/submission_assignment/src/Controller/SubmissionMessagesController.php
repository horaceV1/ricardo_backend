<?php

namespace Drupal\submission_assignment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the submission messages page.
 */
class SubmissionMessagesController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a SubmissionMessagesController.
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
   * Display messages for a submission.
   */
  public function viewMessages($submission_id) {
    $current_user = $this->currentUser();
    $submission = DynamicFormSubmission::load($submission_id);

    if (!$submission) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Access check: assigned worker or admin.
    $assigned_to = $this->database->select('dynamic_form_submission', 's')
      ->fields('s', ['assigned_to'])
      ->condition('id', $submission_id)
      ->execute()
      ->fetchField();

    $is_assigned = ($assigned_to == $current_user->id());
    $is_admin = $current_user->hasPermission('administer site configuration');

    if (!$is_assigned && !$is_admin) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Load form name.
    $form_entity = \Drupal::entityTypeManager()
      ->getStorage('dynamic_form')
      ->load($submission->getFormId());
    $form_name = $form_entity ? $form_entity->label() : $submission->getFormId();

    // Fetch messages.
    $messages = $this->database->select('dynamic_form_messages', 'm')
      ->fields('m')
      ->condition('submission_id', $submission_id)
      ->orderBy('created', 'ASC')
      ->execute()
      ->fetchAll();

    // Build messages list using theme.
    $messages_list = [];
    foreach ($messages as $msg) {
      $sender = User::load($msg->user_id);
      $is_current_user = ($msg->user_id == $current_user->id());

      $file_link = '';
      if ($msg->file_id) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($msg->file_id);
        if ($file) {
          $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $file_link = [
            '#type' => 'link',
            '#title' => '📎 ' . $file->getFilename(),
            '#url' => Url::fromUri($file_url),
            '#attributes' => ['target' => '_blank', 'class' => ['file-attachment']],
          ];
        }
      }

      $messages_list[] = [
        '#theme' => 'submission_message_item',
        '#sender_name' => $sender ? $sender->getDisplayName() : $this->t('Desconhecido'),
        '#message' => $msg->message,
        '#file' => $file_link,
        '#created' => \Drupal::service('date.formatter')->format($msg->created, 'custom', 'd/m/Y H:i'),
        '#is_current_user' => $is_current_user,
      ];
    }

    // Get message form.
    $message_form = \Drupal::formBuilder()->getForm(
      'Drupal\submission_assignment\Form\SubmissionMessageForm',
      $submission_id
    );

    $build = [
      '#theme' => 'submission_messages',
      '#submission_id' => $submission_id,
      '#form_name' => $form_name,
      '#messages' => $messages_list,
      '#message_form' => $message_form,
      '#attached' => [
        'library' => [
          'submission_assignment/messages',
        ],
      ],
    ];

    return $build;
  }

}
