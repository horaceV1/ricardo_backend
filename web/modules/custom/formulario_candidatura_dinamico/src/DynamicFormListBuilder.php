<?php
namespace Drupal\formulario_candidatura_dinamico;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

class DynamicFormListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Nome do formulário');
    $header['id'] = $this->t('ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    
    $operations['submissions'] = [
      'title' => $this->t('Ver submissões'),
      'weight' => 15,
      'url' => Url::fromRoute('dynamic_form.submissions', [
        'dynamic_form' => $entity->id(),
      ]),
    ];
    
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    
    // Add "Add form" link at the top
    $build['add_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Adicionar formulário'),
      '#url' => Url::fromRoute('entity.dynamic_form.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'button--small'],
      ],
      '#weight' => -10,
    ];
    
    $build['table']['#empty'] = $this->t('Não existem formulários dinâmicos. <a href=":url">Adicionar formulário</a>.', [
      ':url' => Url::fromRoute('entity.dynamic_form.add_form')->toString(),
    ]);
    
    return $build;
  }
}
