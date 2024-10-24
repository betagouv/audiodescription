<?php

namespace Drupal\audiodescription\Form;

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
    $withAd = $request->query->get('with_ad');

    $form['ad'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Audiodescription'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['ad']['with_ad'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films audiodécrits uniquement'),
      '#default_value' => 0,
      "#value" => $withAd
    ];

    /**$form['ad']['marius'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films primés par le prix Marius'),
      '#default_value' => 0,
    ];**/

    $form['infos'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Informations sur le film'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['infos']['fields'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fr-grid-row', 'fr-grid-row--gutters']],
    ];

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('genre');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['infos']['fields']['genre'] = [
      '#type' => 'select',
      '#title' => $this->t('Genre'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
    ];

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('nationality');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['infos']['fields']['nationality'] = [
      '#type' => 'select',
      '#title' => $this->t('Nationalité'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
    ];

    /**$options = [];

    $form['infos']['fields']['production_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Année de production'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
    ];**/

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('public');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['infos']['fields']['public'] = [
      '#type' => 'select',
      '#title' => $this->t('Public'),
      '#options' => $options,
      // Mettre à TRUE si vous voulez un select multiple.
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#prefix' => '<div class="fr-col fr-col-12 fr-col-md-3">',
      '#suffix' => '</div>',
    ];

    /**$form['viewing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Visionnage'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];**/

    /**$terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('platform');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['viewing']['platform'] = [
      '#type' => 'select',
      '#title' => $this->t('Plateforme'),
      '#options' => $options,
      // Mettre à TRUE si vous voulez un select multiple.
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];**/

    /**$form['viewing']['free'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films gratuits uniquement'),
      '#default_value' => 0,
    ];**/

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

    $form['#action'] = '/recherche';

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
