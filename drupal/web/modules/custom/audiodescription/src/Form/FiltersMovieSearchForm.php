<?php

namespace Drupal\audiodescription\Form;

use Drupal\audiodescription\Popo\MovieSearchParametersBag;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a full form for searching movies with filters.
 */
class FiltersMovieSearchForm extends AbstractMovieSearchForm {

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
  private $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('request_stack'),
      $container->get('entity_type.manager')
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

    $search = $request->query->get('search');

    // Change label if search field is not empty.
    $searchLabel = $this->t('Rechercher un film');
    if (!empty($search)) {
      $searchLabel = $this->t("Effectuer une nouvelle recherche");
    }

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $searchLabel,
      '#size' => 30,
      '#maxlength' => 128,
      '#value' => $search,
      '#prefix' => '<div class="fr-mt-1w fr-mb-5w" role="search">',
      '#suffix' => '</div>',
    ];

    $form['infos']['fields'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fr-grid-row', 'fr-grid-row--gutters', 'fr-mb-3w']],
    ];

    $form['infos']['fields']['is_free'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films gratuits uniquement'),
      '#value' => $selectedIsFree,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
    ];

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('partner');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['infos']['fields']['partner'] = [
      '#type' => 'select',
      '#title' => $this->t('Filtrer par plateforme'),
      '#options' => $options,
      // Mettre à TRUE si vous voulez un select multiple.
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
      '#default_value' => $selectedPartners,
      '#singular_title' => 'plateforme',
      '#plural_title' => 'plateformes',
      '#is_female' => TRUE,
    ];

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('genre');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['infos']['fields']['genre'] = [
      '#type' => 'select',
      '#title' => $this->t('Filtrer par genre'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
      '#default_value' => $selectedGenres,
      '#singular_title' => 'genre',
      '#plural_title' => 'genres',
      '#is_female' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Appliquer les filtres'),
      '#attributes' => [
        'class' => [
          'fr-btn',
          'fr-btn--secondary',
        ],
      ],
    ];

    if (!$isEmptyParametersBag) {
      $form['reset'] = [
        '#type' => 'button',
        '#value' => "Réinitialiser les filtres",
        '#attributes' => [
          'class' => [
            'fr-btn',
            'fr-btn--icon-left',
            'fr-btn--tertiary-no-outline',
            'fr-icon-close-circle-line',
          ],
        ],
      ];
    }

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

}
