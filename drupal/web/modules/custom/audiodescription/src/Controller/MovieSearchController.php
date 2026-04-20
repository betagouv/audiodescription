<?php

namespace Drupal\audiodescription\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\audiodescription\Manager\MovieSearchManager;
use Drupal\audiodescription\Popo\MovieSearchParametersBag;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new MovieSearchController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param Drupal\audiodescription\Manager\MovieSearchManager $movieSearchManager
   *   The movie search manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entityTypeManager,
    MovieSearchManager $movieSearchManager,
    RequestStack $requestStack,
  ) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entityTypeManager;
    $this->movieSearchManager = $movieSearchManager;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('audiodescription.manager.movie_search'),
      $container->get('request_stack'),
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
      $pagination = $this->movieSearchManager->buildPagination($params, $pagesCount);
    }

    $pageSize = ($pagesCount > 1) ? self::PAGE_SIZE : $total;

    $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\SimpleMovieSearchForm', 'lg', 'Effectuer une nouvelle recherche', TRUE);
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
    // Current page number.
        'page' => $params->page,
        'pageSize' => $pageSize,
        'totalWithAd' => $totalWithAd,
      ],
      '#form' => $form,
      '#filtersForm' => $filtersForm,
      '#filters' => [
      // keyword.
        'search' => $params->search,
        'genres' => $this->getCurrentFilters($params->genre),
        'plateformes' => $this->getCurrentFilters($params->partner),
        'films gratuit uniquement' => $params->isFree ? 'oui' : NULL,
      ],
      '#blockContact' => $blockContact,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Returns the display names of currently selected filter entities.
   */
  private function getCurrentFilters(array $ids) {
    $values = [];
    foreach ($ids as $id) {
      $entity = $this
        ->entityTypeManager
        ->getStorage('taxonomy_term')
        ->load($id);

      if (!is_null($entity)) {
        $values[] = $entity->getName();
      }

    }

    return !empty($values) ? $values : NULL;
  }

  /**
   * Returns the page title for the movie search results page.
   */
  public function getTitle() {
    $request = $this->requestStack->getCurrentRequest();
    $params = MovieSearchParametersBag::createFromRequest($request);

    if ($params->search && $params->page !== 1) {
      return $this->t('Résultats de la recherche pour "@search", page @page', [
        '@search' => $params->search,
        '@page' => $params->page,
      ]);
    }
    if ($params->search) {
      return $this->t('Résultats de la recherche pour "@search"', [
        '@search' => $params->search,
      ]);
    }
    if ($params->page !== 1) {
      return $this->t('Résultats de la recherche, page @page', [
        '@page' => $params->page,
      ]);
    }
    return $this->t('Résultats de la recherche');
  }

}
