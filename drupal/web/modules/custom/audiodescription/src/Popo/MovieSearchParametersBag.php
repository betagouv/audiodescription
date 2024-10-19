<?php

namespace Drupal\audiodescription\Popo;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class used to manage parameters bag of movie search.
 */
class MovieSearchParametersBag {

  public function __construct(
    public readonly string $search,
    public readonly int $page,
  ) {
  }

  /**
   * Create instance of MovieSearchParametersBag from Request.
   */
  public static function createFromRequest(Request $request) {
    $params = $request->query;
    $search = $params->get('search', '');

    $page = $params->get('page', 1);
    $page = empty($page) ? 1 : $page;

    return new self($search, $page);
  }

}
