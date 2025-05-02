<?php

namespace Drupal\audiodescription\Command;

use Drupal\Core\Ajax\CommandInterface;

class SearchAjaxUrlUpdateCommand implements CommandInterface {
  public function render() {
    return [
      'command' => 'searchAjaxUpdateUrl',
    ];
  }
}
