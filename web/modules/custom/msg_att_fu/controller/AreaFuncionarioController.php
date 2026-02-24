<?php
namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Symfony\Component\HttpFoundation\Request;

class AreaFuncionarioController extends ControllerBase {
  
  public function areaFuncionario() {
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();

    // Carrega submissões atribuídas ao funcionário autenticado via query SQL
    $connection = \Drupal::database();
    $query = $connection->select('dynamic_form_submission', 'dfs')
      ->fields('dfs', ['id'])
      ->condition('tecnico_responsavel_uid', $user_id)
      ->orderBy('created', 'DESC');
    
    $submission_ids = $query->execute()->fetchCol();
    
    $rows = [];
    if (!empty($submission_ids)) {
      $submissions = DynamicFormSubmission::loadMultiple($submission_ids);
      
      foreach ($submissions as $submission) {
        /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $submission */
        $rows[] = [
          $submission->id(),
          $submission->getEmail(),
          \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'short'),
          [
            'data' => [
              '#type' => 'link',
              '#title' => $this->t('Ver detalhes'),
              '#url' => \Drupal\Core\Url::fromRoute('entity.dynamic_form_submission.canonical', [
                'dynamic_form_submission' => $submission->id(),
              ]),
            ],
          ],
        ];
      }
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
      '#empty' => $this->t('Ainda não há processos atribuídos a você.'),
    ];

    return $build;
  }
}
