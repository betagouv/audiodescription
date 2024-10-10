<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for building the search page V2 content.
 */
class MovieSearchController extends ControllerBase {
  private const PAGE_SIZE = 6;

  // Display 2 pages before current and 2 pages after current.
  private const PAGINATION_SIZE = 2;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MovieSearchController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(FormBuilderInterface $form_builder, EntityTypeManagerInterface $entityTypeManager) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Provides the render array for the search page v2 (POC).
   *
   * @return array|JsonResponse
   *   A render array representing the content of the search page v2 (POC).
   */
  public function search(Request $request) {
    $index = Index::load('movies');
    $params = $request->query;
    $search = $params->get('search_api_fulltext', '');

    if ($index) {
      $pageHasAd = $params->get('page_has_ad', 1);
      $pageHasAd = empty($pageHasAd) ? 1 : $pageHasAd;

      $offsetHasAd = ($pageHasAd - 1) * self::PAGE_SIZE;

      $pageNoAd = $params->get('page_no_ad', 1);
      $pageNoAd = empty($pageNoAd) ? 1 : $pageNoAd;

      $offsetNoAd = ($pageNoAd - 1) * self::PAGE_SIZE;

      [$totalHasAd, $pagesCountHasAd, $entitiesHasAd] = $this->queryAdMovies($index, $offsetHasAd, 1, $search);
      [$totalNoAd, $pagesCountNoAd, $entitiesNoAd] = $this->queryAdMovies($index, $offsetNoAd, 0, $search);

      $renderedEntitiesHasAd = [];
      $renderedEntitiesNoAd = [];

      foreach ($entitiesHasAd as $entity) {
        $view_builder = $this->entityTypeManager->getViewBuilder('node');
        $renderedEntitiesHasAd[] = $view_builder->view($entity, 'teaser');
      }

      $paginationHasAd = NULL;
      if ($pagesCountHasAd > 1) {
        $paginationHasAd = $this->buildPagination($pageHasAd, $pageNoAd, $pagesCountHasAd, $search, TRUE);
      }

      $pageSizeHasAd = ($pagesCountHasAd > 1) ? self::PAGE_SIZE : $totalHasAd;

      foreach ($entitiesNoAd as $entity) {
        $view_builder = $this->entityTypeManager->getViewBuilder('node');
        $renderedEntitiesNoAd[] = $view_builder->view($entity, 'teaser');
      }

      $paginationNoAd = NULL;
      if ($pagesCountNoAd > 1) {
        $paginationNoAd = $this->buildPagination($pageHasAd, $pageNoAd, $pagesCountNoAd, $search, FALSE);
      }

      $pageSizeNoAd = ($pagesCountNoAd > 1) ? self::PAGE_SIZE : $totalNoAd;

      $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\MovieSearchForm');

      return [
        '#theme' => 'movie_search',
        '#has_ad' => [
          'total' => $totalHasAd,
          'pagesCount' => $pagesCountHasAd,
          'items' => $renderedEntitiesHasAd,
          'pagination' => $paginationHasAd,
          'page' => $pageHasAd,
          'pageSize' => $pageSizeHasAd,
        ],
        '#no_ad' => [
          'total' => $totalNoAd,
          'pagesCount' => $pagesCountNoAd,
          'items' => $renderedEntitiesNoAd,
          'pagination' => $paginationNoAd,
          'page' => $pageNoAd,
          'pageSize' => $pageSizeNoAd,
        ],
        '#form' => $form,
        '#cache' => [
          'max-age' => 0,  // Pas de mise en cache.
        ]
      ];
    }
    else {
      return new JsonResponse(['error' => 'Index not found'], 404);
    }
  }

  /**
   * Query movies.
   */
  public function queryAdMovies(Index $index, int $offset, bool $hasAd, ?string $search) :array {
    $query = $index->query();
    $query->addCondition('field_has_ad', (int) $hasAd);

    if (!is_null($search)) {
      $query->keys($search);
    }

    $query->range($offset, self::PAGE_SIZE);

    $results = $query->execute();

    $total = $results->getResultCount();
    $pagesCount = ceil($total / self::PAGE_SIZE);

    $ids = [];

    foreach ($results->getResultItems() as $item) {
      // Par exemple, pour les nœuds, cela donne 'node/1'.
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
   * Build pagination.
   */
  private function buildPagination(int $pageHasAd, int $pageNoAd, int $pagesCount, string $search, bool $hasAd) {
    if ($hasAd) {
      $page = $pageHasAd;

      $first = ($page == 1) ? FALSE : $this->buildUrl(1, $pageNoAd, $search);
      $prev = ($page == 1) ? FALSE : $this->buildUrl(($page - 1), $pageNoAd, $search);

      $next = ($page == $pagesCount) ? FALSE : $this->buildUrl(($page + 1), $pageNoAd, $search);
      $last = ($page == $pagesCount) ? FALSE : $this->buildUrl($pagesCount, $pageNoAd, $search);
    }
    else {
      $page = $pageNoAd;

      $first = ($page == 1) ? FALSE : $this->buildUrl($pageHasAd, 1, $search);
      $prev = ($page == 1) ? FALSE : $this->buildUrl($pageHasAd, ($page - 1), $search);

      $next = ($page == $pagesCount) ? FALSE : $this->buildUrl($pageNoAd, ($page + 1), $search);
      $last = ($page == $pagesCount) ? FALSE : $this->buildUrl($pageNoAd, $pagesCount, $search);
    }

    $befores = [];
    $afters = [];

    for ($i = 1; $i <= self::PAGINATION_SIZE; $i++) {
      $indexBefore = $page - $i;
      $indexAfter = $page + $i;

      if ($indexBefore > 0) {
        $befores[] = $indexBefore;
      }

      if ($indexAfter < $pagesCount) {
        $afters[] = $indexAfter;
      }
    }

    $pages = [];
    foreach ($befores as $before) {
      $pages[] = [
        'title' => $before,
        'url' => ($hasAd) ? $this->buildUrl($before, $pageNoAd, $search) : $this->buildUrl($pageHasAd, $before, $search),
      ];
    }

    $pages[] = [
      'title' => $page,
    ];
    foreach ($afters as $after) {
      $pages[] = [
        'title' => $after,
        'url' => ($hasAd) ? $this->buildUrl($after, $pageNoAd, $search) : $this->buildUrl($pageHasAd, $after, $search),
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
  private function buildUrl(int $pageHasAd, int $pageNoAd, string $search) {
    $parameters = [
      'page_has_ad' => $pageHasAd,
      'page_no_ad' => $pageNoAd,
    ];

    if (!empty($search)) {
      $parameters['search_api_fulltext'] = $search;
    }

    $url = Url::fromRoute('audiodescription.movie_search', $parameters);

    return $url->toString();
  }

}
