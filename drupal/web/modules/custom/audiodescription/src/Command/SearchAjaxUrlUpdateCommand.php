<?php

namespace Drupal\audiodescription\Command;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to update the search URL without page reload.
 */
class SearchAjaxUrlUpdateCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'searchAjaxUpdateUrl',
    ];
  }

}
