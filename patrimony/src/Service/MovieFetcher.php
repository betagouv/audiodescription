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
    // Récupérer le MovieRepository.
    // findByIds
    // En fonction du résultat :
    // Je renvoie le film
    // Je renvoie null
    // Je merge les films et renvoie le movie

    $movies = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());

    if (empty($movies)) {
      dump('null');
      return NULL;
    }

    if (count($movies) == 1 ) {
      dump('1');
      return $movies[0];
    }

    // Merge movies.
    if (count($movies) > 1) {
      dump('multi');
      return $this->movieAutoMerger->autoMerge($ids, $movies, $sourceMovie);
    }
  }

}