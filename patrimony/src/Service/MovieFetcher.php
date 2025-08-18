<?php

namespace App\Service;

use App\Entity\Source\SourceMovie;
use App\Repository\MovieRepository;

class MovieFetcher
{
  public function __construct(
    private MovieRepository $movieRepository,
    private MovieAutoMerger $movieAutoMerger,
  )
  {
  }

  public function fetchByIds($ids, SourceMovie $sourceMovie) {
    $movies = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());

    if (empty($movies)) {
      return NULL;
    }

    if (count($movies) == 1 ) {
      return $movies[0];
    }

    // Merge movies.
    if (count($movies) > 1) {
      return $this->movieAutoMerger->autoMerge($ids, $movies, $sourceMovie);
    }
  }

}