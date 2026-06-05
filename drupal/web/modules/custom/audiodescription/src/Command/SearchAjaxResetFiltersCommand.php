<?php

namespace Drupal\audiodescription\Command;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to reset all filter checkboxes in the filter form.
 */
class SearchAjaxResetFiltersCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return ['command' => 'searchAjaxResetFilters'];
  }

}
