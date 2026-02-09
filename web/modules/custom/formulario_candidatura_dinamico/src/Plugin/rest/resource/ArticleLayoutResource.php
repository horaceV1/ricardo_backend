<?php

namespace Drupal\formulario_candidatura_dinamico\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a resource to get article with layout builder content.
 *
 * @RestResource(
 *   id = "article_layout_resource",
 *   label = @Translation("Article Layout Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/article-layout/{node}"
 *   }
 * )
 */
class ArticleLayoutResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    $logger,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('formulario_candidatura_dinamico'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param int $node
   *   The node ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the article with layout builder forms.
   */
  public function get($node) {
    $node_entity = $this->entityTypeManager->getStorage('node')->load($node);
    
    if (!$node_entity) {
      return new ResourceResponse(['error' => 'Node not found'], 404);
    }

    $forms = [];
    
    // Check if node has layout builder enabled
    if ($node_entity->hasField('layout_builder__layout')) {
      $sections = $node_entity->get('layout_builder__layout')->getSections();
      
      foreach ($sections as $section) {
        $components = $section->getComponents();
        
        foreach ($components as $component) {
          $plugin = $component->getPlugin();
          $plugin_id = $plugin->getPluginId();
          
          // Look for blocks with "dynamic" or "formulario" in their ID
          if (
            strpos($plugin_id, 'dynamic') !== FALSE || 
            strpos($plugin_id, 'formulario') !== FALSE ||
            strpos($plugin_id, 'candidatura') !== FALSE
          ) {
            $configuration = $plugin->getConfiguration();
            
            // Get the block label/title
            $label = '';
            if (isset($configuration['label'])) {
              $label = $configuration['label'];
            } elseif (method_exists($plugin, 'label')) {
              $label = $plugin->label();
            }
            
            $forms[] = [
              'id' => $plugin_id,
              'label' => $label,
              'plugin_id' => $plugin_id,
              'configuration' => $configuration,
            ];
            
            \Drupal::logger('formulario_candidatura_dinamico')->info('Found form block: @id with label: @label', [
              '@id' => $plugin_id,
              '@label' => $label,
            ]);
          }
        }
      }
    }
    
    \Drupal::logger('formulario_candidatura_dinamico')->info('Article @nid: Found @count form blocks', [
      '@nid' => $node,
      '@count' => count($forms),
    ]);
    
    return new ResourceResponse(['forms' => $forms]);
  }

}
