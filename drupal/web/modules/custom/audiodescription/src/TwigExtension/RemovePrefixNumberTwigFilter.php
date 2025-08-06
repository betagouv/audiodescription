<?php

namespace Drupal\audiodescription\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RemovePrefixNumberTwigFilter extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('remove_prefix_number', [$this, 'removePrefixNumber']),
    ];
  }

  /**
   * Function to add a class to <p> tags.
   *
   * @param string $html
   *   The HTML content to modify.
   *
   * @return string
   *   The modified HTML.
   */
  public function removePrefixNumber(string $text) {
    if (!is_null($text)) {
      return preg_replace('/^\d+\.\s+/', '', $text);
    }

    return $text;
  }

}
