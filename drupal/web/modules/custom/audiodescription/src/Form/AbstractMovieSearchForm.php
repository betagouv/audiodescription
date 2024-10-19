<?php

namespace Drupal\audiodescription\Form;

use Drupal\Core\Form\FormBase;

/**
 * Movie search form common functions.
 */
abstract class AbstractMovieSearchForm extends FormBase {

  /**
   * Get base parameters (without filters) to build URL.
   */
  protected function getBaseParameters($form_state) {
    $search = $form_state->getUserInput()['search'];

    $parameters = [
      'search' => $search,
    ];

    $request = $this->requestStack->getCurrentRequest();
    $page = $request->query->get('pagep', NULL);

    if (!is_null($page)) {
      $parameters['page'] = $page;
    }

    return $parameters;
  }

}
