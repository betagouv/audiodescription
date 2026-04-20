<?php

namespace Drupal\audiodescription\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig filter to remove numeric prefixes from strings.
 */
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
   * Removes a numeric prefix from a string.
   *
   * @param string $text
   *   The text to process.
   *
   * @return string
   *   The text with any numeric prefix removed.
   */
  public function removePrefixNumber(string $text) {
    if (!is_null($text)) {
      return preg_replace('/^\d+\.\s+/', '', $text);
    }

    return $text;
  }

}
