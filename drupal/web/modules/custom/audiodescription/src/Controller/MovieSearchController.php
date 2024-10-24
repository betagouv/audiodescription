<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\audiodescription\Manager\MovieSearchManager;
use Drupal\audiodescription\Popo\MovieSearchParametersBag;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for building the search page V2 content.
 */
class MovieSearchController extends ControllerBase {
  private const PAGE_SIZE = 12;

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
   * The movie search manager service.
   *
   * @var \Drupal\audiodescription\Manager\MovieSearchManager
   */
  protected $movieSearchManager;

  /**
   * Constructs a new MovieSearchController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param Drupal\audiodescription\Manager\MovieSearchManager $movieSearchManager
   *   The movie search manager service.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entityTypeManager,
    MovieSearchManager $movieSearchManager,
  ) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entityTypeManager;
    $this->movieSearchManager = $movieSearchManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('audiodescription.manager.movie_search'),
    );
  }

  /**
   * Provides the render array for the search page v2 (POC).
   *
   * @return array|JsonResponse
   *   A render array representing the content of the search page v2 (POC).
   */
  public function search(Request $request) {

    $params = MovieSearchParametersBag::createFromRequest($request);

    $offset = ($params->page - 1) * self::PAGE_SIZE;

    [$total, $pagesCount, $entities] = $this->movieSearchManager->queryMovies($offset, self::PAGE_SIZE, $params->search);

    $totalWithAd = $this->movieSearchManager->countAdMovies($params->search);

    $renderedEntities = [];

    foreach ($entities as $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $renderedEntities[] = $view_builder->view($entity, 'teaser');
    }

    $pagination = NULL;
    if ($pagesCount > 1) {
      $pagination = $this->buildPagination($params, $pagesCount);
    }

    $pageSize = ($pagesCount > 1) ? self::PAGE_SIZE : $total;

    $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\SimpleMovieSearchForm', 'lg');
    $filters = $this->formBuilder->getForm('Drupal\audiodescription\Form\FiltersMovieSearchForm');

    return [
      '#theme' => 'movie_search',
      '#movies' => [
        'total' => $total,
        'pagesCount' => $pagesCount,
        'items' => $renderedEntities,
        'pagination' => $pagination,
        'page' => $params->page,
        'pageSize' => $pageSize,
        'totalWithAd' => $totalWithAd,
      ],
      '#form' => $form,
      '#filters' => $filters,
      '#cache' => [
        // Pas de mise en cache.
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Build pagination.
   */
  private function buildPagination(MovieSearchParametersBag $params, int $pagesCount) {
    $parameters = [
      'page' => 1,
      'search' => $params->search,
    ];
    $first = ($params->page == 1) ? FALSE : $this->buildUrl($parameters);

    $parameters = [
      'page' => $params->page - 1,
      'search' => $params->search,
    ];
    $prev = ($params->page == 1) ? FALSE : $this->buildUrl($parameters);

    $parameters = [
      'page' => $params->page + 1,
      'search' => $params->search,
    ];
    $next = ($params->page == $pagesCount) ? FALSE : $this->buildUrl($parameters);

    $parameters = [
      'page' => $pagesCount,
      'search' => $params->search,
    ];
    $last = ($params->page == $pagesCount) ? FALSE : $this->buildUrl($parameters);

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
      $parameters = [
        'page' => $before,
        'search' => $params->search,
      ];
      $pages[] = [
        'title' => $before,
        'url' => $this->buildUrl($parameters),
      ];
    }

    $pages[] = [
      'title' => $params->page,
    ];

    foreach ($afters as $after) {
      $parameters = [
        'page' => $after,
        'search' => $params->search,
      ];
      $pages[] = [
        'title' => $after,
        'url' => $this->buildUrl($parameters),
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
