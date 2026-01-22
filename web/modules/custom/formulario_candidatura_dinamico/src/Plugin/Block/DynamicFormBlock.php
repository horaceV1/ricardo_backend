<?php

namespace Drupal\formulario_candidatura_dinamico\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a 'Dynamic Form' Block.
 *
 * @Block(
 *   id = "dynamic_form_block",
 *   admin_label = @Translation("Formulário Dinâmico"),
 *   category = @Translation("Forms"),
 * )
 */
class DynamicFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'dynamic_form_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Get all dynamic forms
    $forms = $this->entityTypeManager
      ->getStorage('dynamic_form')
      ->loadMultiple();

    $options = [];
    foreach ($forms as $form_entity) {
      $options[$form_entity->id()] = $form_entity->label();
    }

    $form['dynamic_form_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Selecionar Formulário'),
      '#options' => $options,
      '#default_value' => $this->configuration['dynamic_form_id'],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Selecione -'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['dynamic_form_id'] = $form_state->getValue('dynamic_form_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form_id = $this->configuration['dynamic_form_id'];

    if (empty($form_id)) {
      return [
        '#markup' => $this->t('Por favor configure o bloco para selecionar um formulário.'),
      ];
    }

    // Build the form
    $form = $this->formBuilder->getForm(
      'Drupal\formulario_candidatura_dinamico\Form\DynamicFormSubmissionForm',
      $form_id
    );

    return $form;
  }

}
