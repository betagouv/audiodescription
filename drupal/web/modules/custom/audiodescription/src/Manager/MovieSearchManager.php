<?php

namespace Drupal\audiodescription\Manager;

use Drupal\audiodescription\Popo\MovieSearchParametersBag;
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
  protected ?Index $moviesIndex;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moviesIndex = Index::load('movies');
  }

  /**
   * Query movies.
   */
  public function queryMovies(int $offset, int $pageSize, ?MovieSearchParametersBag $params) :array {
    $query = $this->moviesIndex->query();

    $search = $params->search ?? null;
    if (!is_null($search)) {
      $query->keys($search);
    }

    if ($params->withAd) {
      $query->addCondition('field_has_ad', 1);
    }

    if (!empty($params->genre)) {
      $andGroup = $query->createConditionGroup('AND');

      foreach ($params->genre as $genre) {
        $andGroup->addCondition('field_genres', $genre, '=');
      }

      $query->addConditionGroup($andGroup);

    }

    if (!empty($params->nationality)) {
      $andGroup = $query->createConditionGroup('AND');

      foreach ($params->nationality as $nationality) {
        $andGroup->addCondition('field_nationalities', $nationality, '=');
      }

      $query->addConditionGroup($andGroup);
    }

    if (!empty($params->public)) {
      $andGroup = $query->createConditionGroup('OR');

      foreach ($params->public as $public) {
        $andGroup->addCondition('field_public', $public, '=');
      }

      $query->addConditionGroup($andGroup);
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
  public function countAdMovies(MovieSearchParametersBag $params): int {
    $queryWithAd = $this->moviesIndex->query();

    if (!is_null($params->search)) {
      $queryWithAd->keys($params->search);
    }

    if (!empty($params->genre)) {
      $andGroup = $queryWithAd->createConditionGroup('AND');

      foreach ($params->genre as $genre) {
        $andGroup->addCondition('field_genres', $genre, '=');
      }

      $queryWithAd->addConditionGroup($andGroup);
    }

    if (!empty($params->nationality)) {
      $andGroup = $queryWithAd->createConditionGroup('AND');

      foreach ($params->nationality as $nationality) {
        $andGroup->addCondition('field_nationalities', $nationality, '=');
      }

      $queryWithAd->addConditionGroup($andGroup);
    }

    if (!empty($params->public)) {
      $andGroup = $queryWithAd->createConditionGroup('OR');

      foreach ($params->public as $public) {
        $andGroup->addCondition('field_public', $public, '=');
      }

      $queryWithAd->addConditionGroup($andGroup);
    }

    $queryWithAd->addCondition('field_has_ad', 1);
    $results = $queryWithAd->execute();

    return $results->getResultCount();
  }

}
