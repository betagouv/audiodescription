<?php

namespace Drupal\audiodescription\Form;

use Drupal\audiodescription\Command\SearchAjaxResetFiltersCommand;
use Drupal\audiodescription\Command\SearchAjaxUpdateTitleCommand;
use Drupal\audiodescription\Command\SearchAjaxUrlUpdateCommand;
use Drupal\audiodescription\Manager\MovieSearchManager;
use Drupal\audiodescription\Popo\MovieSearchParametersBag;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a full form for searching movies with filters.
 */
class FiltersMovieSearchForm extends AbstractMovieSearchForm {
  private const PAGE_SIZE = 20;

  // Display 2 pages before current and 2 pages after current.
  private const PAGINATION_SIZE = 2;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
  protected MovieSearchManager $movieSearchManager;

  /**
   * Class constructor.
   */
  public function __construct(
    RequestStack $requestStack,
    EntityTypeManagerInterface $entityTypeManager,
    MovieSearchManager $movieSearchManager,
  ) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->movieSearchManager = $movieSearchManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('audiodescription.manager.movie_search')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filter_movie_search_form';
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();

    // $selectedWithAd = $request->query->get('with_ad');
    $selectedGenres = $request->query->getIterator()['genre'] ?? [];
    $selectedPartners = $request->query->getIterator()['partner'] ?? [];
    $selectedIsFree = $request->query->get('is_free');

    $form['infos']['fields'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fr-grid-row']],
    ];

    $form['infos']['fields']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Réinitialiser les filtres'),
      '#prefix' => '<div class="fr-btns-group fr-btns-group--icon-left fr-col-12 fr-mb-2w">',
      '#suffix' => '</div>',
      '#attributes' => [
        'type' => 'reset',
        'class' => [
          'fr-btn',
          'fr-btn--secondary',
          'fr-btn--icon-left',
          'fr-icon-close-circle-line',
        ],
      ],
      '#submit' => ['::resetFilters'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::resetFilters',
        'wrapper' => 'ajax',
        'progress' => [],
      ],
    ];

    $form['infos']['fields']['is_free'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films gratuits'),
      '#value' => $selectedIsFree,
      '#prefix' => '<div class="fr-col-12 fr-mb-3w">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::searchMovies',
        'disable-refocus' => FALSE,
        'event' => 'change',
    // This element is updated with this AJAX callback.
        'wrapper' => 'ajax',
        'progress' => [],
      ],
    ];

    if (!is_null($this->entityTypeManager)) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('partner');
      $options = [];

      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }

      /*
       * $form['infos']['fields']['partner'] = [
       *   '#type' => 'checkboxes',
       *   '#title' => $this->t('Mes abonnements'),
       *   '#options' => $options,
       *   '#multiple' => TRUE,
       *   '#required' => FALSE,
       *   '#prefix' => '<div class="fr-col-12 fr-mb-3w">',
       *   '#suffix' => '</div>',
       *   '#default_value' => $selectedPartners,
       *   '#singular_title' => 'plateforme',
       *   '#plural_title' => 'plateformes',
       *   '#ad_plugin_type' => 'partners-select',
       *   '#is_female' => TRUE,
       *   '#is_rich_select' => TRUE,
       *   '#ajax' => [
       *     'callback' => '::searchMovies',
       *     'disable-refocus' => FALSE,
       *     'event' => 'change',
       *     'wrapper' => 'ajax',
       *     'progress' => [],
       *   ]
       * ];
       */

      $form['infos']['fields']['partner'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Filtrer par plateforme'),
        '#options' => $options,
        '#multiple' => TRUE,
        '#required' => FALSE,
        '#prefix' => '<div class="fr-col-12 fr-mb-2w">',
        '#suffix' => '</div>',
        '#default_value' => $selectedPartners,
        '#singular_title' => 'plateforme',
        '#plural_title' => 'plateformes',
        '#ad_plugin_type' => 'rich-select',
        '#is_female' => TRUE,
        '#is_rich_select' => TRUE,
        '#ajax' => [
          'callback' => '::searchMovies',
          'disable-refocus' => FALSE,
          'event' => 'change',
      // This element is updated with this AJAX callback.
          'wrapper' => 'ajax',
          'progress' => [],
        ],
      ];

      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('genre');
      $options = [];

      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }

      $form['infos']['fields']['genre'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Filtrer par genre'),
        '#options' => $options,
        '#multiple' => TRUE,
        '#required' => FALSE,
        '#prefix' => '<div class="fr-col-12 fr-mb-2w">',
        '#suffix' => '</div>',
        '#default_value' => $selectedGenres,
        '#singular_title' => 'genre',
        '#plural_title' => 'genres',
        '#ad_plugin_type' => 'rich-select',
        '#is_female' => FALSE,
        '#is_rich_select' => TRUE,
        '#ajax' => [
          'callback' => '::searchMovies',
          'disable-refocus' => FALSE,
          'event' => 'change',
      // This element is updated with this AJAX callback.
          'wrapper' => 'ajax',
          'progress' => [],
        ],
      ];
    }

    $form['#attached']['library'][] = 'audiodescription/ajax_filter_update';
    $form['#attached']['library'][] = 'audiodescription/ajax_live_update';
    $form['#action'] = '/recherche#liste';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getBaseParameters($form_state);

    $form_state->setRedirect(
      'audiodescription.movie_search',
      [],
      ['query' => $parameters]
    );
  }

  /**
   * Resets the search filters by redirecting to the current page.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Recharge la page sans les valeurs.
    $form_state->setRedirect('<current>');
  }

  /**
   * Handles form submission and redirects with updated search parameters.
   */
  public function searchMovies(array &$form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();

    // Set params value from $form_state.
    $userInput = $form_state->getUserInput();

    $request->query->set('is_free', $userInput["is_free"] ?? 0);
    $request->query->set('genre', array_filter($userInput["genre"]) ?? []);
    $request->query->set('partner', array_filter($userInput["partner"]) ?? []);

    $params = MovieSearchParametersBag::createFromRequest($request);

    $offset = ($params->page - 1) * self::PAGE_SIZE;

    [$total, $pagesCount, $entities] = $this->movieSearchManager->queryMovies($offset, self::PAGE_SIZE, $params);

    $totalWithAd = $this->movieSearchManager->countAdMovies($params);

    $renderedEntities = [];

    foreach ($entities as $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $renderedEntities[] = $view_builder->view($entity, 'card_result');
    }

    $pagination = $this->movieSearchManager->buildPagination($params, $pagesCount);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#ajax',
      [
        '#theme' => 'movie_search_ajax',
        '#movies' => [
          'total' => $total,
          'pagesCount' => $pagesCount,
          'items' => $renderedEntities,
          'pagination' => $pagination,
          'page' => $params->page,
          'pageSize' => self::PAGE_SIZE,
          'totalWithAd' => $totalWithAd,
        ],
        '#cache' => [
          'max-age' => 0,
        ],
      ]
    ));

    $response->addCommand(new SearchAjaxUrlUpdateCommand());
    $response->addCommand(new SearchAjaxUpdateTitleCommand($this->buildPageTitle($params)));

    return $response;
  }

  /**
   * Resets all filters via AJAX, keeping only the search keyword.
   */
  public function resetFilters(array &$form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();

    $request->query->set('is_free', 0);
    $request->query->set('genre', []);
    $request->query->set('partner', []);

    $params = MovieSearchParametersBag::createFromRequest($request);

    $offset = ($params->page - 1) * self::PAGE_SIZE;
    [$total, $pagesCount, $entities] = $this->movieSearchManager->queryMovies($offset, self::PAGE_SIZE, $params);
    $totalWithAd = $this->movieSearchManager->countAdMovies($params);

    $renderedEntities = [];
    foreach ($entities as $entity) {
      $renderedEntities[] = $this->entityTypeManager->getViewBuilder('node')->view($entity, 'card_result');
    }

    $pagination = $this->movieSearchManager->buildPagination($params, $pagesCount);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#ajax', [
      '#theme' => 'movie_search_ajax',
      '#movies' => [
        'total' => $total,
        'pagesCount' => $pagesCount,
        'items' => $renderedEntities,
        'pagination' => $pagination,
        'page' => $params->page,
        'pageSize' => self::PAGE_SIZE,
        'totalWithAd' => $totalWithAd,
      ],
      '#cache' => ['max-age' => 0],
    ]));
    $response->addCommand(new SearchAjaxResetFiltersCommand());
    $response->addCommand(new SearchAjaxUrlUpdateCommand());
    $response->addCommand(new SearchAjaxUpdateTitleCommand($this->buildPageTitle($params)));

    return $response;
  }

  /**
   * Builds the page title string from search params (mirrors MovieSearchController::getTitle).
   */
  private function buildPageTitle(MovieSearchParametersBag $params): string {
    $terms = array_merge(
      $this->getTermNames($params->genre),
      $this->getTermNames($params->partner),
    );

    $qualifiers = [];
    if ($params->search) {
      $qualifiers[] = '"' . strip_tags($params->search) . '"';
    }
    foreach ($terms as $term) {
      $qualifiers[] = '"' . $term . '"';
    }

    $siteName = \Drupal::config('system.site')->get('name');

    if (!empty($qualifiers) && $params->page !== 1) {
      $title = $this->t('Résultats de la recherche pour @qualifiers, page @page', [
        '@qualifiers' => implode(', ', $qualifiers),
        '@page' => $params->page,
      ]);
    }
    elseif (!empty($qualifiers)) {
      $title = $this->t('Résultats de la recherche pour @qualifiers', [
        '@qualifiers' => implode(', ', $qualifiers),
      ]);
    }
    elseif ($params->page !== 1) {
      $title = $this->t('Résultats de la recherche, page @page', [
        '@page' => $params->page,
      ]);
    }
    else {
      $title = $this->t('Résultats de la recherche');
    }

    $fullTitle = html_entity_decode((string) $title . ' | ' . $siteName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (mb_strlen($fullTitle) > 90) {
      return mb_substr($fullTitle, 0, 87) . '...';
    }
    return $fullTitle;
  }

  /**
   * Returns taxonomy term names for a list of IDs.
   */
  private function getTermNames(array $ids): array {
    $names = [];
    foreach ($ids as $id) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($id);
      if ($term) {
        $names[] = $term->getName();
      }
    }
    return $names;
  }

}
