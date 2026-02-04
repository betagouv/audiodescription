<?php

namespace Drupal\audiodescription\Form;

use Drupal\audiodescription\Command\SearchAjaxUrlUpdateCommand;
use Drupal\audiodescription\Manager\MovieSearchManager;
use Drupal\audiodescription\Popo\MovieSearchParametersBag;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
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
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();
    $parametersBag = MovieSearchParametersBag::createFromRequest($request);
    $isEmptyParametersBag = $parametersBag->isEmptyParametersBag();

    //$selectedWithAd = $request->query->get('with_ad');
    $selectedGenres = $request->query->getIterator()['genre'] ?? [];
    $selectedPartners = $request->query->getIterator()['partner'] ?? [];
    $selectedIsFree = $request->query->get('is_free');

    $form['infos']['fields'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fr-grid-row']],
    ];

    $form['infos']['fields']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => "Réinitialiser les filtres",
      '#prefix' => '<div class="fr-btns-group fr-btns-group--icon-left fr-col-12 fr-mb-2w">',
      '#suffix' => '</div>',
      '#attributes' => [
        'href' => '/recherche',
        'class' => [
          'fr-btn',
          'fr-btn--secondary',
          'fr-btn--icon-left',
          'fr-icon-close-circle-line',
        ],
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
        'wrapper' => 'ajax', // This element is updated with this AJAX callback.
        'progress' => [],
      ]
    ];

    if (!is_null($this->entityTypeManager)) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('partner');
      $options = [];

      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }

      /**$form['infos']['fields']['partner'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Mes abonnements'),
        '#options' => $options,
        // Mettre à TRUE si vous voulez un select multiple.
        '#multiple' => TRUE,
        '#required' => FALSE,
        '#prefix' => '<div class="fr-col-12 fr-mb-3w">',
        '#suffix' => '</div>',
        '#default_value' => $selectedPartners,
        '#singular_title' => 'plateforme',
        '#plural_title' => 'plateformes',
        '#ad_plugin_type' => 'partners-select',
        '#is_female' => TRUE,
        '#is_rich_select' => TRUE,
        '#ajax' => [
          'callback' => '::searchMovies',
          'disable-refocus' => FALSE,
          'event' => 'change',
          'wrapper' => 'ajax', // This element is updated with this AJAX callback.
          'progress' => [],
        ]
      ];**/

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
          'wrapper' => 'ajax', // This element is updated with this AJAX callback.
          'progress' => [],
        ]
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
          'wrapper' => 'ajax', // This element is updated with this AJAX callback.
          'progress' => [],
        ]
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

  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('<current>'); // recharge la page sans les valeurs
  }

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

    return $response;
  }
}
