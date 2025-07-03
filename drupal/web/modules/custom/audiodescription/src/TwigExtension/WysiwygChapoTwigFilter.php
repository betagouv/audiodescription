<?php

namespace Drupal\audiodescription\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class WysiwygChapoTwigFilter extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('class2wysiwygP', [$this, 'addClassToWysiwygParagraph'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Function to add a class to <p> tags.
   *
   * @param string $html
   *   The HTML content to modify.
   * @param string $class
   *   The class to add to <p> tags.
   *
   * @return string
   *   The modified HTML.
   */
  public function addClassToWysiwygParagraph($html, $class = 'fr-text--lead') {
    if (!is_null($html)) {
      return str_replace('<p>', '<p class="' . $class . '">', $html);
    }
    return $html;
  }

}
