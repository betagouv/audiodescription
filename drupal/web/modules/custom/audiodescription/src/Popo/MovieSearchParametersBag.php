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
    public readonly array $partner,
    public readonly bool $isFree,
  ) {
  }

  /**
   * Create instance of MovieSearchParametersBag from Request.
   */
  public static function createFromRequest(Request $request) {
    $params = $request->request;

    $search = $params->get('search', '');
    $withAD = $params->get('with_ad', 0);
    $isFree = $params->get('is_free', 0);

    $page = $params->get('page', 1);
    $page = empty($page) ? 1 : $page;

    $genre = $params->getIterator()['genre'] ?? [];
    $partner = $params->getIterator()['partner'] ?? [];

    return new self($search, $page, $withAD, $genre, $partner, $isFree);
  }

  public function filtersToArray()
  {
    $array = [];

    $array['search'] = $this->search;

    if ($this->withAd == true) {
      $array['with_ad'] = $this->withAd;
    }

    if (!empty($this->genre)){
      $array['genre'] = $this->genre;
    }

    if (!empty($this->partner)){
      $array['partner'] = $this->partner;
    }

    return $array;
  }

  public function isEmptyParametersBag() {
    if (
      $this->search == '' &&
      !$this->withAd &&
      empty($this->genre) &&
      empty($this->partner) &&
      !$this->isFree
    ) {
      return TRUE;
    }

    return FALSE;
  }

}
