<?php

namespace Drupal\audiodescription\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Entity\Index;

/**
 * Service to search movies.
 */
class MovieSearchManager {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The movies index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected Index $moviesIndex;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moviesIndex = Index::load('movies');
  }

  /**
   * Query movies.
   */
  public function queryMovies(int $offset, int $pageSize, ?string $search) :array {
    $query = $this->moviesIndex->query();

    if (!is_null($search)) {
      $query->keys($search);
    }

    $query->range($offset, $pageSize);

    $results = $query->execute();

    $total = $results->getResultCount();
    $pagesCount = ceil($total / $pageSize);

    $ids = [];

    foreach ($results->getResultItems() as $item) {
      $entity_id = $item->getId();
      $id = explode('/', $entity_id)[1];
      $id = explode(':', $id)[0];

      $ids[] = $id;
    }

    $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);

    return [
      $total,
      $pagesCount,
      $entities,
    ];
  }

  /**
   * Count number of movies with AD.
   */
  public function countAdMovies(?string $search): int {
    $queryWithAd = $this->moviesIndex->query();

    if (!is_null($search)) {
      $queryWithAd->keys($search);
    }
    $queryWithAd->addCondition('field_has_ad', 1);
    $results = $queryWithAd->execute();

    return $results->getResultCount();
  }

}
