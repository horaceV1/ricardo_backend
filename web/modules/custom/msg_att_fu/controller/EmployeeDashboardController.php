<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para dashboard do funcionário.
 */
class EmployeeDashboardController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EmployeeDashboardController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
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
   * Dashboard do funcionário.
   */
  public function dashboard() {
    $current_user_id = $this->currentUser()->id();

    // Buscar submissões atribuídas ao usuário atual
    $query = $this->database->select('dynamic_form_submission', 'dfs')
      ->fields('dfs')
      ->condition('assigned_to', $current_user_id)
      ->orderBy('created', 'DESC');

    $submissions = $query->execute()->fetchAll();

    $rows = [];
    foreach ($submissions as $submission) {
      // Load full entity to use getData() method
      $submission_entity = DynamicFormSubmission::load($submission->id);
      $data = $submission_entity ? $submission_entity->getData() : [];
      
      $rows[] = [
        $submission->id,
        $submission->form_id,
        $submission->email ?? '-',
        $submission->estado ?? 'submetido',
        date('d/m/Y H:i', $submission->created),
        Link::createFromRoute('Ver Detalhes', 'formulario_candidatura_dinamico.employee_view_submission', ['id' => $submission->id]),
      ];
    }

    $build = [];
    
    $build['stats'] = [
      '#markup' => '<div style="background: #f5f5f5; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
        <h3>Estatísticas</h3>
        <p><strong>Total de processos atribuídos:</strong> ' . count($submissions) . '</p>
      </div>',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        'ID',
        'Formulário',
        'Email',
        'Estado',
        'Data',
        'Ações',
      ],
      '#rows' => $rows,
      '#empty' => $this->t('Não tens processos atribuídos.'),
    ];

    return $build;
  }

  /**
   * Vista de submissão para funcionário.
   */
  public function viewSubmission($id) {
    $current_user = $this->currentUser();

    // Carregar submissão usando entity
    $submission = DynamicFormSubmission::load($id);

    if (!$submission) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Verificar se está atribuído ao funcionário atual ou se é admin
    $is_assigned = $submission->getAssignedTo() == $current_user->id();
    $is_admin = $current_user->hasPermission('administer site configuration');
    
    if (!$is_assigned && !$is_admin) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Não tens permissão para aceder a este processo.');
    }

    $data = $submission->getData();
    
    // Carregar informação do formulário
    $form_entity = \Drupal\formulario_candidatura_dinamico\Entity\DynamicForm::load($submission->getFormId());
    $form_label = $form_entity ? $form_entity->label() : $submission->getFormId();
    $fields = $form_entity ? $form_entity->getFields() : [];

    $build = [];

    // Header da submissão
    $build['header'] = [
      '#markup' => '<h2>Processo #' . $submission->id() . '</h2>',
    ];

    // Informação básica
    $build['basic_info'] = [
      '#type' => 'table',
      '#header' => ['Informação', 'Valor'],
      '#rows' => [
        ['Email', $submission->getEmail() ?? '-'],
        ['Formulário', $form_label],
        ['Data de submissão', date('d/m/Y H:i', $submission->getCreatedTime())],
      ],
    ];

    // Dados submetidos pelo cliente
    $build['submitted_data'] = [
      '#type' => 'details',
      '#title' => '📋 Dados Submetidos pelo Cliente',
      '#open' => TRUE,
    ];

    $data_rows = [];
    foreach ($fields as $index => $field) {
      $field_key = 'field_' . $index;
      $value = $data[0][$field_key] ?? '';

      if (is_array($value) && isset($value['fid'])) {
        // Campo de ficheiro
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($value['fid']);
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
      '#header' => ['Campo', 'Resposta'],
      '#rows' => $data_rows,
    ];

    // Estado dos documentos
    $build['document_status'] = [
      '#type' => 'details',
      '#title' => '📄 Estado dos Documentos',
      '#open' => TRUE,
    ];

    $build['document_status']['form'] = \Drupal::formBuilder()->getForm(
      'Drupal\formulario_candidatura_dinamico\Form\DocumentosEstadoPorSubmissaoForm',
      $id
    );

    // Sistema de mensagens integrado
    $build['messages'] = [
      '#type' => 'details',
      '#title' => '💬 Mensagens com o Cliente',
      '#open' => TRUE,
      '#attributes' => ['class' => ['submission-messages-section']],
    ];

    // Buscar mensagens
    $messages = $this->database->select('dynamic_form_messages', 'm')
      ->fields('m')
      ->condition('submission_id', $id)
      ->orderBy('created', 'ASC')
      ->execute()
      ->fetchAll();

    // Renderizar mensagens
    $messages_list = [];
    foreach ($messages as $msg) {
      $sender = \Drupal\user\Entity\User::load($msg->user_id);
      $is_current_user = $msg->user_id == $current_user->id();
      
      $file_markup = '';
      if ($msg->file_id) {
        $file = \Drupal\file\Entity\File::load($msg->file_id);
        if ($file) {
          $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $file_markup = '<div class="message-file"><a href="' . $file_url . '" target="_blank">📎 ' . $file->getFilename() . '</a></div>';
        }
      }

      $message_class = $is_current_user ? 'message-sent' : 'message-received';
      $sender_name = $sender ? $sender->getDisplayName() : 'Desconhecido';
      $created = \Drupal::service('date.formatter')->format($msg->created, 'custom', 'd/m/Y H:i');

      $messages_list[] = [
        '#markup' => '
          <div class="message-item ' . $message_class . '">
            <div class="message-header">
              <strong>' . $sender_name . '</strong>
              <span class="message-date">' . $created . '</span>
            </div>
            <div class="message-body">' . nl2br(htmlspecialchars($msg->message)) . '</div>
            ' . $file_markup . '
          </div>
        ',
      ];
    }

    if (empty($messages_list)) {
      $messages_list[] = ['#markup' => '<p style="color: #666; font-style: italic;">Ainda não existem mensagens neste processo.</p>'];
    }

    $build['messages']['list'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['messages-thread'], 'style' => 'max-height: 400px; overflow-y: auto; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;'],
      'items' => $messages_list,
    ];

    // Formulário de envio de mensagem
    $build['messages']['form'] = \Drupal::formBuilder()->getForm('Drupal\formulario_candidatura_dinamico\Form\SubmissionMessageForm', $id);

    // CSS inline para mensagens
    $build['messages']['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .message-item { margin-bottom: 15px; padding: 10px; border-radius: 8px; }
          .message-sent { background: #0073aa; color: white; margin-left: 20%; }
          .message-received { background: white; border: 1px solid #ddd; margin-right: 20%; }
          .message-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9em; }
          .message-sent .message-header { color: rgba(255,255,255,0.9); }
          .message-received .message-header { color: #666; }
          .message-date { font-size: 0.85em; }
          .message-body { line-height: 1.5; }
          .message-file { margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.1); }
          .message-file a { color: inherit; text-decoration: underline; }
        ',
      ],
      'submission-messages-styles',
    ];

    // Botão voltar
    $build['back'] = [
      '#type' => 'link',
      '#title' => '← Voltar aos Meus Processos',
      '#url' => Url::fromRoute('formulario_candidatura_dinamico.employee_dashboard'),
      '#attributes' => ['class' => ['button'], 'style' => 'margin-top: 20px;'],
    ];

    return $build;
  }

  /**
   * Title callback para vista de submissão.
   */
  public function submissionTitle($id) {
    return $this->t('Processo #@id', ['@id' => $id]);
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
