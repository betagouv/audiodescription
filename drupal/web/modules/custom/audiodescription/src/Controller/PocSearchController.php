<?php

namespace Drupal\audiodescription\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\search_api\Entity\Index;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PocSearchController extends ControllerBase
{
  private const PAGE_SIZE = 6;

  public function search(Request $request) {
    $index = Index::load('movies');
    $params = $request->query;
    $search = $params->get('search_api_fulltext', null);

    if ($index) {
      $page = $params->get('page_has_ad', 1);
      $offsetHasAd = ($page-1)*self::PAGE_SIZE;

      $page = $params->get('page_no_ad', 1);
      $offsetNoAd = ($page-1)*self::PAGE_SIZE;

      return [
        '#theme' => 'poc_search',
        '#has_ad' => $this->queryAdMovies($index, $offsetHasAd, 1, $search),
        '#no_ad' => $this->queryAdMovies($index, $offsetNoAd, 0, $search),
      ];

      return new JsonResponse($items);
    }
    else {
      return new JsonResponse(['error' => 'Index not found'], 404);
    }
  }

  public function queryAdMovies(Index $index, int $offset, bool $hasAd, ?string $search) :array {
    $query = $index->query();
    $query->addCondition('field_has_ad', (int) $hasAd);

    if (!is_null($search)) {
      $query->keys($search);
    }

    $query->range($offset, self::PAGE_SIZE);

    $results = $query->execute();

    $total = $results->getResultCount();
    $pagesCount = ceil($total/self::PAGE_SIZE);

    $ids = [];
    $rendered_entities = [];

    foreach ($results->getResultItems() as $item) {
      $entity_id = $item->getId(); // Par exemple, pour les nœuds, cela donne 'node/1'.
      $id = explode('/', $entity_id)[1];
      $id = explode(':', $id)[0];

      $ids[] = $id;
    }

    $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);

    foreach ($entities as $entity) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $rendered_entities[] = $view_builder->view($entity, 'teaser');
    }
    return [
      'total' => $total,
      'pagesCount' => $pagesCount,
      'items' => $rendered_entities,
    ];
  }
}
