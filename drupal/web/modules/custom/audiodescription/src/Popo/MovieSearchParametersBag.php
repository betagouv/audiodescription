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
    public readonly bool $withAd,
    public readonly array $genre,
    public readonly array $nationality,
    public readonly array $public,
  ) {
  }

  /**
   * Create instance of MovieSearchParametersBag from Request.
   */
  public static function createFromRequest(Request $request) {
    $params = $request->query;
    $search = $params->get('search', '');
    $withAD = $params->get('with_ad', 0);

    $page = $params->get('page', 1);
    $page = empty($page) ? 1 : $page;

    $genre = $params->getIterator()['genre'] ?? [];
    $nationality = $params->getIterator()['nationality'] ?? [];
    $public = $params->getIterator()['public'] ?? [];

    return new self($search, $page, $withAD, $genre, $nationality, $public);
  }

}
