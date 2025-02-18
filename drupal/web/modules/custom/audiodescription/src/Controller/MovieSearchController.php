<?php

namespace Drupal\audiodescription\Controller;

use Drupal\block\Entity\Block;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
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
  private const PAGE_SIZE = 20;

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

    [$total, $pagesCount, $entities] = $this->movieSearchManager->queryMovies($offset, self::PAGE_SIZE, $params);

    $totalWithAd = $this->movieSearchManager->countAdMovies($params);

    $renderedEntities = [];

    foreach ($entities as $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $renderedEntities[] = $view_builder->view($entity, 'card_result');
    }

    $pagination = NULL;
    if ($pagesCount > 1) {
      $pagination = $this->buildPagination($params, $pagesCount);
    }

    $pageSize = ($pagesCount > 1) ? self::PAGE_SIZE : $total;

    $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\SimpleMovieSearchForm', 'lg');
    $filtersForm = $this->formBuilder->getForm('Drupal\audiodescription\Form\FiltersMovieSearchForm');

    $block = Block::load('ad_search_contact_block');

    $blockContact = [];
    if ($block) {
      $blockContact = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }

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
      '#filtersForm' => $filtersForm,
      '#filters' => [
        'search' => $params->search,
        'genres' => $this->getCurrentFilters($params->genre),
        'nationalities' => $this->getCurrentFilters($params->nationality),
        'publics' => $this->getCurrentFilters($params->public),
        'partenaires' => $this->getCurrentFilters($params->partner),
      ],
      '#filtersExpanded' => !$params->isEmptyParametersBag(),
      '#blockContact' => $blockContact,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  private function getCurrentFilters(array $ids) {
    foreach ($ids as $id) {
      $entity = $this
        ->entityTypeManager
        ->getStorage('taxonomy_term')
        ->load($id);

      $values[] = $entity->getName();
    }

    return $values ?? NULL;
  }

  /**
   * Build pagination.
   */
  private function buildPagination(MovieSearchParametersBag $params, int $pagesCount) {
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
