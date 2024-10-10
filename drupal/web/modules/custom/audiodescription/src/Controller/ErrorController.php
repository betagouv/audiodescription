<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for building the error pages content.
 */
class ErrorController extends ControllerBase {

  /**
   * Provides the render array for the error 403 page.
   *
   * @return array
   *   A render array representing the content of the error 403 page.
   */
  public function error403() {

    return [
      '#theme' => 'error_403',
    ];
  }

  /**
   * Provides the render array for the error 404 page.
   *
   * @return array
   *   A render array representing the content of the error 404 page.
   */
  public function error404() {

    return [
      '#theme' => 'error_404',
    ];
  }

}
