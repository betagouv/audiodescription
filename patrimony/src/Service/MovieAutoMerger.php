<?php

namespace App\Service;

use App\Entity\Patrimony\Movie;
use App\Entity\Source\SourceMovie;
use App\Enum\PartnerCode;
use Doctrine\ORM\EntityManagerInterface;

class MovieAutoMerger
{
  public function __construct(
    private EntityManagerInterface $entityManager
  )
  {
  }

  /**
   * @param array<Movie> $movies
   * @param SourceMovie $sourceMovie
   * @return Movie
   */
  public function autoMerge(array $ids, array $movies, SourceMovie $sourceMovie) {
    $selectedMovie = NULL;
    foreach ($movies as $movie) {
      if($movie->getCode() == $sourceMovie->getCode()) {
        $selectedMovie = $movie;
      }
    }

    if (is_null($selectedMovie)) {
      /** @var Movie $selectedMovie */
      $selectedMovie = $movies[0];
    }

    // allocineId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'allocineId');

    // arteId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'arteId', PartnerCode::ARTE);

    // canalVodId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'canalVodId', PartnerCode::CANAL_VOD);
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'canalVodId', PartnerCode::CANAL_REPLAY);

    // cncId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'cncId');

    // franceTvId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'franceTvId', PartnerCode::FRANCE_TV);

    // imdbId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'imdbId');

    // isanId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'isanId');

    // laCinetekId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'laCinetekId', PartnerCode::LACINETEK_SVOD);
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'laCinetekId', PartnerCode::LACINETEK_TVOD);

    // orangeVodId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'orangeVodId', PartnerCode::ORANGE_VOD);

    // plurimediaId
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'plurimediaId');

    // tf1Id
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'tf1Id', PartnerCode::TF1);

    // visa
    $this->updateXId($selectedMovie, $movies, $sourceMovie, $ids, 'visa');

    // hasAd
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'hasAd', TRUE);

    // poster
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'poster');

    // synopsis
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'synopsis');

    // duration
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'duration');

    // productionYear
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'productionYear');

    // nationalities
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'nationalities');

    // genres
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'genres');

    // directors
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'directors');

    // public
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'public');

    // actorMovies
    $this->updateXField($selectedMovie, $movies, $sourceMovie, 'actorMovies');

    foreach ($movies as $movie) {
      if ($movie->getId() !== $selectedMovie->getId()) {
        $solutions = $movie->getSolutions();
        foreach ($solutions as $solution) {
          $solution->setMovie($selectedMovie);
        }

        $sourceMovies = $movie->getSourceMovies();
        foreach ($sourceMovies as $sourceMovie) {
          $sourceMovie->setMovie($selectedMovie);
        }

        $actorMovies = $movie->getActorMovies();
        foreach ($actorMovies as $actorMovie) {
          $actorMovie->setMovie($selectedMovie);
        }

        $this->entityManager->remove($movie);
      }
    }

    $this->entityManager->flush();
    return $selectedMovie;
  }

  private function updateXId($selectedMovie, $movies, $sourceMovie, $ids, $name, $partner = NULL)
  {
    $getFunc = "get". ucwords($name);
    $setFunc = "set". ucwords($name);

    if (is_null($selectedMovie->$getFunc())) {
      $id = NULL;

      if (isset($ids[$name])) {
        $id = $ids[$name];
      }

      foreach ($movies as $movie) {
        if (!is_null($movie->$getFunc())) {
          $id = $movie->$getFunc();
        }
      }

      if (is_null($id)) {
        if (!is_null($partner) && $sourceMovie->getPartner()->getCode() == $partner) {
          $id = $sourceMovie->getInternalPartnerId();
        }
      }

      if (!is_null($id)) {
        $selectedMovie->$setFunc($id);
      }
    }
  }

  private function updateXField($selectedMovie, $movies, $sourceMovie, $field, $bool = FALSE) {
    $getFunc = "get". ucwords($field);
    if ($bool) {
      $getFunc = "is". ucwords($field);
    }

    $setFunc = "set". ucwords($field);

    if (is_null($selectedMovie->$getFunc())) {
      $value = NULL;

      foreach ($movies as $movie) {
        if (!is_null($movie->$getFunc())) {
          $value = $movie->$getFunc();
        }
      }

      if (is_null($value)) {
        if (!is_null($sourceMovie->$getFunc())) {
          $value = $sourceMovie->$getFunc();
        }
      }

      if (!is_null($value)) {
        $selectedMovie->$setFunc($value);
      }
    }
  }
}