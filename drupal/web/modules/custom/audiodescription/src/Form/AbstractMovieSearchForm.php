<?php

namespace Drupal\audiodescription\Form;

use Drupal\Core\Form\FormBase;

/**
 * Movie search form common functions.
 */
abstract class AbstractMovieSearchForm extends FormBase {

  /**
   * Get base parameters to build URL.
   */
  protected function getBaseParameters($form_state) {
    $parameters = [];
    $userInput = $form_state->getUserInput();

    if (array_key_exists('search', $userInput)) {
      $parameters['search'] = $userInput['search'];
    }

    /**if (array_key_exists('with_ad', $userInput) && !is_null($userInput['with_ad'])) {
      $parameters['with_ad'] = $userInput['with_ad'];
    }**/

    if (array_key_exists('is_free', $userInput) && !is_null($userInput['is_free'])) {
      $parameters['is_free'] = $userInput['is_free'];
    }

    if (array_key_exists('partner', $userInput) && !is_null($userInput['partner'])) {
      $parameters['partner'] = array_filter($userInput['partner']);
    }

    if (array_key_exists('genre', $userInput) && !is_null($userInput['genre'])) {
      $parameters['genre'] = array_filter($userInput['genre']);
    }

    $request = $this->requestStack->getCurrentRequest();
    $page = $request->query->get('page', NULL);

    if (!is_null($page)) {
      $parameters['page'] = $page;
    }

    return $parameters;
  }

}
