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
    try {
      $node_entity = $this->entityTypeManager->getStorage('node')->load($node);
      
      if (!$node_entity || $node_entity->bundle() !== 'article') {
        return new ResourceResponse(['error' => 'Article not found'], 404);
      }

      $forms = [];
      
      // Check if layout builder is enabled
      if ($node_entity->hasField('layout_builder__layout')) {
        $layout = $node_entity->get('layout_builder__layout')->getValue();
        
        foreach ($layout as $section) {
          if (isset($section['section'])) {
            $section_object = $section['section'];
            $components = $section_object->getComponents();
            
            foreach ($components as $component) {
              $plugin = $component->getPlugin();
              
              // Check if this is a dynamic form block
              if ($plugin->getPluginId() === 'dynamic_form_block') {
                $config = $plugin->getConfiguration();
                $form_id = $config['dynamic_form_id'] ?? null;
                
                if ($form_id) {
                  $form = $this->entityTypeManager
                    ->getStorage('dynamic_form')
                    ->load($form_id);
                  
                  if ($form) {
                    $forms[] = [
                      'id' => $form->id(),
                      'label' => $form->label(),
                      'fields' => $form->get('fields') ?: [],
                    ];
                  }
                }
              }
            }
          }
        }
      }

      return new ResourceResponse(['forms' => $forms]);
    } catch (\Exception $e) {
      return new ResourceResponse(['error' => $e->getMessage()], 500);
    }
  }

}
