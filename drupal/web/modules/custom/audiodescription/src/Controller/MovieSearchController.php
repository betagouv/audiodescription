<?php

namespace Drupal\audiodescription\Controller;

use Drupal\block\Entity\Block;
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
        'page' => $params->page, // current page number.
        'pageSize' => $pageSize,
        'totalWithAd' => $totalWithAd,
      ],
      '#form' => $form,
      '#filtersForm' => $filtersForm,
      '#filters' => [
        'search' => $params->search, // keyword.
        'genres' => $this->getCurrentFilters($params->genre),
        'plateformes' => $this->getCurrentFilters($params->partner),
        'films gratuit uniquement' => $params->isFree ? 'oui' : null,
      ],
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

  public function getTitle() {
    $request = \Drupal::request();
    $params = MovieSearchParametersBag::createFromRequest($request);

    $title = "Résultats de la recherche";

    if ($params->search) {
      $title .= " pour \"" . $params->search . "\"";
    }

    if ($params->page !== 1) {
      $title .= ", page " . $params->page;
    }

    return $this->t($title);
  }
}
