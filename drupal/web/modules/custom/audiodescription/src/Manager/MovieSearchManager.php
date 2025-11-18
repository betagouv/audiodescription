<?php

namespace Drupal\audiodescription\Manager;

use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\audiodescription\Popo\MovieSearchParametersBag;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;

/**
 * Service to search movies.
 */
class MovieSearchManager {
  // Display 2 pages before current and 2 pages after current.
  private const PAGINATION_SIZE = 2;

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

    $search = !empty($params->search) ? $params->search : null;

    if (!is_null($search)) {
      $query->keys($search);
    }

    /**if (!is_null($params->withAd) && $params->withAd) {
      $query->addCondition('field_has_ad', 1);
    }**/

    if (!empty($params->genre)) {
      $andGroup = $query->createConditionGroup('OR');

      foreach ($params->genre as $genre) {
        $andGroup->addCondition('field_genres', $genre, '=');
      }

      $query->addConditionGroup($andGroup);
    }

    if (!empty($params->partner)) {
      $andGroup = $query->createConditionGroup('OR');

      foreach ($params->partner as $partner) {
        $andGroup->addCondition('field_pg_partner', $partner, '=');
      }

      $query->addConditionGroup($andGroup);
    }

    if (!is_null($params->isFree) && $params->isFree) {
      $offers = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'field_taxo_code' => "FREE_ACCESS",
        'vid' => Taxonomy::OFFER->value,
      ]);

      $offer = reset($offers)->tid->value;

      $connection = Database::getConnection();

      $sql = "
        SELECT DISTINCT pg_partner.field_pg_partner_target_id
        FROM node_field_data m
        LEFT JOIN node__field_offers fo ON fo.entity_id = m.nid
        LEFT JOIN paragraphs_item offer_paragraph ON offer_paragraph.id = fo.field_offers_target_id
        LEFT JOIN paragraph__field_pg_offer taxo_ref ON taxo_ref.entity_id = offer_paragraph.id
        LEFT JOIN paragraph__field_pg_partners pg_partners on pg_partners.entity_id = taxo_ref.entity_id
        LEFT JOIN paragraph__field_pg_partner pg_partner on pg_partner.entity_id = pg_partners.field_pg_partners_target_id
        LEFT JOIN paragraphs_item s ON s.id = pg_partners.field_pg_partners_target_id
        LEFT JOIN paragraph__field_pg_start_rights sr ON s.id = sr.entity_id
        LEFT JOIN paragraph__field_pg_end_rights er ON s.id = er.entity_id
        WHERE taxo_ref.field_pg_offer_target_id = ". $offer ."
        AND (
          to_date(sr.field_pg_start_rights_value, 'YYYY-MM-DD') < NOW()
          OR sr.field_pg_start_rights_value IS NULL
          AND to_date(er.field_pg_end_rights_value, 'YYYY-MM-DD') > NOW()
          OR er.field_pg_end_rights_value IS NULL
        )
        ";

      $partners = $connection->query($sql)->fetchCol();

      $andGroup = $query->createConditionGroup('OR');

      foreach ($partners as $partner) {
        $andGroup->addCondition('field_pg_partner', $partner, '=');
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

    $queryWithAd->addCondition('field_has_ad', 1);
    $results = $queryWithAd->execute();

    return $results->getResultCount();
  }

  /**
   * Build pagination.
   */
  public function buildPagination(MovieSearchParametersBag $params, int $pagesCount) {
    $parameters = $params->filtersToArray();
    $urlParameters = $parameters;
    $urlParameters['page'] = 1;

    $first = ($params->page == 1) ? FALSE : $this->buildUrl($urlParameters);

    $urlParameters['page'] = $params->page - 1;
    $prev = ($params->page == 1) ? FALSE : $this->buildUrl($urlParameters);

    $urlParameters['page'] = $params->page + 1;
    $next = ($params->page == $pagesCount) ? FALSE : $this->buildUrl($urlParameters);

    $urlParameters['page'] = $pagesCount;
    $last = ($params->page == $pagesCount) ? FALSE : $this->buildUrl($urlParameters);

    $befores = [];
    $afters = [];

    for ($i = 1; $i <= self::PAGINATION_SIZE; $i++) {
      $indexBefore = $params->page - $i;
      $indexAfter = $params->page + $i;

      if ($indexBefore > 0) {
        $befores[] = $indexBefore;
      }

      if ($indexAfter < $pagesCount) {
        $afters[] = $indexAfter;
      }
    }

    sort($befores);
    $pages = [];
    foreach ($befores as $before) {
      $urlParameters['page'] = $before;

      $pages[] = [
        'title' => $before,
        'url' => $this->buildUrl($urlParameters),
      ];
    }

    $pages[] = [
      'title' => $params->page,
    ];

    foreach ($afters as $after) {
      $urlParameters['page'] = $after;
      $pages[] = [
        'title' => $after,
        'url' => $this->buildUrl($urlParameters),
      ];
    }

    return [
      'first' => $first,
      'prev' => $prev,
      'pages' => $pages,
      'next' => $next,
      'last' => $last,
    ];
  }

  /**
   * Build URL with params.
   *
   * @return string
   *   URL stringified.
   */
  private function buildUrl(array $params) {
    $url = Url::fromRoute('audiodescription.movie_search', $params);

    return $url->toString();
  }
}
