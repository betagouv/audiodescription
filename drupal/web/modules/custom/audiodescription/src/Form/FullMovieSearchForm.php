<?php

namespace Drupal\audiodescription\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a full form for searching movies with filters.
 */
class FullMovieSearchForm extends AbstractMovieSearchForm {

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
    return 'full_movie_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();
    $search = $request->query->get('search');

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mots-clés'),
      '#size' => 30,
      '#maxlength' => 128,
      '#value' => $search,
    ];

    $form['ad'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Audiodescription'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['ad']['only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films audiodécrits uniquement'),
      '#default_value' => 0,
    ];

    $form['ad']['marius'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Films primés par le prix Marius'),
      '#default_value' => 0,
    ];

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('genre');
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['genres'] = [
      '#type' => 'select',
      '#title' => $this->t('Genres'),
      '#options' => $options,
    // Mettre à TRUE si vous voulez un select multiple.
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
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
