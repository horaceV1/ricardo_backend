<?php

namespace Drupal\barra_pesquisa_artigos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

/**
 * Controller para pesquisa de artigos.
 */
class ArticleSearchController extends ControllerBase {

  /**
   * Pesquisa artigos por termo.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   O request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resposta JSON com os resultados.
   */
  public function search(Request $request) {
    $query_string = $request->query->get('q', '');
    $limit = $request->query->get('limit', 10);
    
    // Se não houver termo de pesquisa, retornar todos os artigos
    if (empty($query_string)) {
      return $this->getAllArticles($limit);
    }

    // Pesquisar artigos
    $results = $this->searchArticles($query_string, $limit);

    return new JsonResponse([
      'results' => $results,
      'count' => count($results),
      'query' => $query_string,
    ]);
  }

  /**
   * Retorna todos os artigos publicados.
   */
  private function getAllArticles($limit) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $results = $this->buildResults($nids);

    return new JsonResponse([
      'results' => $results,
      'count' => count($results),
      'query' => '',
    ]);
  }

  /**
   * Pesquisa artigos por termo.
   */
  private function searchArticles($query_string, $limit) {
    // Query para pesquisar no título
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(TRUE);

    // Criar grupo OR para pesquisar em múltiplos campos
    $or_group = $query->orConditionGroup()
      ->condition('title', $query_string, 'CONTAINS')
      ->condition('body.value', $query_string, 'CONTAINS');

    $query->condition($or_group)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    $nids = $query->execute();
    
    return $this->buildResults($nids);
  }

  /**
   * Constrói array de resultados com dados dos artigos.
   */
  private function buildResults($nids) {
    if (empty($nids)) {
      return [];
    }

    $nodes = Node::loadMultiple($nids);
    $results = [];

    foreach ($nodes as $node) {
      $image_url = NULL;
      
      // Obter imagem se existir
      if ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $image = $node->get('field_image')->entity;
        if ($image) {
          $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image->getFileUri());
        }
      }

      // Obter autor
      $author = $node->getOwner();
      $author_name = $author ? $author->getDisplayName() : 'Anónimo';

      // Obter resumo ou primeiros caracteres do body
      $summary = '';
      if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
        $body = $node->get('body')->getValue();
        if (!empty($body[0]['summary'])) {
          $summary = $body[0]['summary'];
        } elseif (!empty($body[0]['value'])) {
          $summary = substr(strip_tags($body[0]['value']), 0, 200) . '...';
        }
      }

      $results[] = [
        'id' => $node->id(),
        'uuid' => $node->uuid(),
        'title' => $node->getTitle(),
        'summary' => $summary,
        'created' => date('Y-m-d H:i:s', $node->getCreatedTime()),
        'author' => $author_name,
        'image' => $image_url,
        'path' => $node->toUrl()->toString(),
      ];
    }

    return $results;
  }

}
