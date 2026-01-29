<?php

namespace Drupal\artigos_relacionados\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'Related Articles' Block.
 *
 * @Block(
 *   id = "artigos_relacionados_block",
 *   admin_label = @Translation("Artigos Relacionados"),
 *   category = @Translation("Custom"),
 * )
 */
class ArtigosRelacionadosBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ArtigosRelacionadosBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    
    // Verify it's an article node
    if (!$node instanceof NodeInterface || $node->bundle() !== 'article') {
      return [];
    }

    // Get tags from current article
    $tags = [];
    if ($node->hasField('field_tags') && !$node->get('field_tags')->isEmpty()) {
      foreach ($node->get('field_tags')->referencedEntities() as $tag) {
        $tags[] = $tag->id();
      }
    }

    // If no tags, return empty
    if (empty($tags)) {
      return [];
    }

    // Find related articles
    $related_articles = $this->getRelatedArticles($node->id(), $tags);

    if (empty($related_articles)) {
      return [];
    }

    return [
      '#theme' => 'artigos_relacionados_carousel',
      '#articles' => $related_articles,
      '#attached' => [
        'library' => [
          'artigos_relacionados/artigos_relacionados.carousel',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:article'],
        'contexts' => ['route'],
      ],
    ];
  }

  /**
   * Get related articles based on tags.
   *
   * @param int $current_nid
   *   Current node ID to exclude.
   * @param array $tags
   *   Array of tag IDs.
   *
   * @return array
   *   Array of related article nodes.
   */
  protected function getRelatedArticles($current_nid, array $tags) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('nid', $current_nid, '!=')
      ->condition('field_tags', $tags, 'IN')
      ->sort('created', 'DESC')
      ->range(0, 6)
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    
    $articles = [];
    foreach ($nodes as $node) {
      $articles[] = $view_builder->view($node, 'teaser');
    }

    return $articles;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      return array_merge(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    return parent::getCacheTags();
  }

}
