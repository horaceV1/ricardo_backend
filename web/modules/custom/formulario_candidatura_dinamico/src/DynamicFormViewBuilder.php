<?php
namespace Drupal\formulario_candidatura_dinamico;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * View builder handler for Dynamic Form entities.
 */
class DynamicFormViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    
    $build['form_preview'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dynamic-form-preview']],
    ];

    $build['form_preview']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Pré-visualização do Formulário'),
    ];

    $fields = $entity->getFields();
    
    if (empty($fields)) {
      $build['form_preview']['empty'] = [
        '#markup' => '<p>' . $this->t('Este formulário não tem campos definidos.') . '</p>',
      ];
      return $build;
    }

    $build['form_preview']['fields'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-fields']],
    ];

    foreach ($fields as $index => $field) {
      $field_build = [
        '#type' => 'container',
        '#attributes' => ['class' => ['field-preview', 'field-type-' . $field['type']]],
      ];

      $field_build['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#value' => $field['label'] . ($field['required'] ? ' *' : ''),
        '#attributes' => ['class' => ['field-label']],
      ];

      switch ($field['type']) {
        case 'texto':
          $field_build['element'] = [
            '#type' => 'textfield',
            '#disabled' => TRUE,
            '#attributes' => ['placeholder' => $this->t('Campo de texto')],
          ];
          break;

        case 'imagem':
          $field_build['element'] = [
            '#markup' => '<div class="file-upload-wrapper">' .
              '<input type="file" class="file-upload-input" accept="image/*" style="display:none;" />' .
              '<button type="button" class="file-upload-button">' . $this->t('Escolher imagem') . '</button>' .
              '<span class="file-upload-name">' . $this->t('Nenhum ficheiro selecionado') . '</span>' .
              '</div>',
          ];
          break;

        case 'documento':
          $field_build['element'] = [
            '#markup' => '<div class="file-upload-wrapper">' .
              '<input type="file" class="file-upload-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display:none;" />' .
              '<button type="button" class="file-upload-button">' . $this->t('Escolher documento') . '</button>' .
              '<span class="file-upload-name">' . $this->t('Nenhum ficheiro selecionado') . '</span>' .
              '</div>',
          ];
          
          if (!empty($field['description'])) {
            $field_build['description'] = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $field['description'],
              '#attributes' => ['class' => ['field-description']],
            ];
          }
          
          if (!empty($field['link'])) {
            $field_build['link'] = [
              '#type' => 'link',
              '#title' => $this->t('Onde obter este documento'),
              '#url' => \Drupal\Core\Url::fromUri($field['link']),
              '#attributes' => ['target' => '_blank', 'class' => ['document-link']],
            ];
          }
          break;
      }

      $build['form_preview']['fields'][$index] = $field_build;
    }

    $build['form_preview']['#attached']['library'][] = 'formulario_candidatura_dinamico/form_preview';

    return $build;
  }
}
