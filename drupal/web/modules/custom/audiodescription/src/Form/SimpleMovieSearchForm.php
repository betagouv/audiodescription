<?php

namespace Drupal\audiodescription\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a form for searching movies.
 */
class SimpleMovieSearchForm extends AbstractMovieSearchForm {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Class constructor.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_movie_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $format = 'lg') {
    $request = $this->requestStack->getCurrentRequest();
    $search = $request->query->get('search');

    $prefixClasses = 'fr-search-bar';
    if ($format == 'lg') {
      $prefixClasses .= ' fr-search-bar--lg';
    }

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rechercher un film'),
      '#size' => 30,
      '#maxlength' => 128,
      '#value' => $search,
      '#prefix' => '<div class="'. $prefixClasses .'" role="search">'
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
      '#suffix' => '</div>'
    ];

    if ($format == 'md') {
      $form['submit']['#attributes']['title'] = $this->t('Search');
    }

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