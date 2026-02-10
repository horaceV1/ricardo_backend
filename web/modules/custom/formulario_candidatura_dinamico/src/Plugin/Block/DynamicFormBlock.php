<?php

namespace Drupal\formulario_candidatura_dinamico\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'Dynamic Form' Block.
 *
 * @Block(
 *   id = "dynamic_form_block",
 *   admin_label = @Translation("Dynamic form"),
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
   * Constructs a new DynamicFormBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_id' => '',
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

    $form['form_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Dynamic Form'),
      '#options' => $options,
      '#default_value' => $this->configuration['form_id'],
      '#required' => TRUE,
      '#description' => $this->t('Select which dynamic form to display in this block.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['form_id'] = $form_state->getValue('form_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form_id = $this->configuration['form_id'];

    if (empty($form_id)) {
      return [
        '#markup' => $this->t('No form selected.'),
      ];
    }

    // Load the dynamic form entity
    $form_entity = $this->entityTypeManager
      ->getStorage('dynamic_form')
      ->load($form_id);

    if (!$form_entity) {
      return [
        '#markup' => $this->t('Form not found.'),
      ];
    }

    // Return a simple markup that the frontend will pick up
    // The actual form rendering is done by the frontend via the API
    return [
      '#type' => 'markup',
      '#markup' => '<div class="dynamic-form-placeholder" data-form-id="' . $form_id . '"></div>',
      '#attached' => [
        'library' => [
          'formulario_candidatura_dinamico/dynamic_form',
        ],
      ],
    ];
  }

}
