<?php

namespace Drupal\audiodescription\Command;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to update the page <title> without a full reload.
 */
class SearchAjaxUpdateTitleCommand implements CommandInterface {

  public function __construct(private readonly string $title) {}

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'searchAjaxUpdateTitle',
      'title' => $this->title,
    ];
  }

}
