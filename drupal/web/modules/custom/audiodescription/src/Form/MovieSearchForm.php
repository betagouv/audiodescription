<?php

namespace Drupal\audiodescription\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a form for searching movies.
 */
class MovieSearchForm extends FormBase {

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
    return 'movie_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();
    $search_api_fulltext = $request->query->get('search_api_fulltext');

    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rechercher un film'),
      '#size' => 30,
      '#maxlength' => 128,
      '#value' => $search_api_fulltext,
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

    $form['#action'] = '/poc/recherche';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = $form_state->getUserInput()['search_api_fulltext'];

    $parameters = [
      'search_api_fulltext' => $search,
    ];

    $request = $this->requestStack->getCurrentRequest();
    $pageHasAd = $request->query->get('page_has_ad', NULL);

    if (!is_null($pageHasAd)) {
      $parameters['page_has_ad'] = $pageHasAd;
    }

    $pageNoAd = $request->query->get('page_no_ad', NULL);

    if (!is_null($pageNoAd)) {
      $parameters['page_no_ad'] = $pageNoAd;
    }

    $form_state->setRedirect(
      'audiodescription.poc.search',
      [],
      ['query' => $parameters]
    );
  }

}
