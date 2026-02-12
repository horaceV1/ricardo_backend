<?php

namespace Drupal\jwt_auth_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Debug controller for articles.
 */
class ArticlesDebugController extends ControllerBase {

  /**
   * Get all articles for debugging.
   */
  public function getArticles(Request $request) {
    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      
      // Get all article nodes
      $query = $node_storage->getQuery()
        ->condition('type', 'article')
        ->accessCheck(FALSE);
      
      $nids = $query->execute();
      $nodes = $node_storage->loadMultiple($nids);
      
      $articles = [];
      foreach ($nodes as $node) {
        $article_data = [
          'nid' => $node->id(),
          'uuid' => $node->uuid(),
          'title' => $node->getTitle(),
          'created' => $node->getCreatedTime(),
          'has_field_parent' => $node->hasField('field_parent'),
          'has_field_curso' => $node->hasField('field_curso'),
        ];
        
        // Check field_parent
        if ($node->hasField('field_parent') && !$node->get('field_parent')->isEmpty()) {
          $parent = $node->get('field_parent')->entity;
          if ($parent) {
            $article_data['field_parent'] = [
              'nid' => $parent->id(),
              'uuid' => $parent->uuid(),
              'title' => $parent->getTitle(),
            ];
          }
        }
        
        // Check field_curso
        if ($node->hasField('field_curso') && !$node->get('field_curso')->isEmpty()) {
          $curso = $node->get('field_curso')->entity;
          if ($curso) {
            $article_data['field_curso'] = [
              'nid' => $curso->id(),
              'uuid' => $curso->uuid(),
              'title' => $curso->getTitle(),
            ];
          }
        }
        
        $articles[] = $article_data;
      }
      
      return new JsonResponse([
        'total' => count($articles),
        'articles' => $articles,
      ]);
      
    } catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

}
