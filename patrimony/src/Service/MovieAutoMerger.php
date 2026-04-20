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
    ) {
    }

  /**
   * @param array<string, mixed> $ids
   * @param array<Movie> $movies
   * @return Movie
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
    public function autoMerge(array $ids, array $movies, SourceMovie $sourceMovie): Movie
    {
        $selectedMovie = null;
        foreach ($movies as $movie) {
            if ($movie->getCode() == $sourceMovie->getCode()) {
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
        $this->updateXField($selectedMovie, $movies, $sourceMovie, 'hasAd', true);

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

    /**
     * @param array<Movie> $movies
     * @param array<string, mixed> $ids
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function updateXId(
        Movie $selectedMovie,
        array $movies,
        SourceMovie $sourceMovie,
        array $ids,
        string $name,
        ?PartnerCode $partner = null
    ): void {
        $getFunc = "get" . ucwords($name);
        $setFunc = "set" . ucwords($name);

        if (is_null($selectedMovie->$getFunc())) {
            $id = null;

            if (isset($ids[$name])) {
                $id = $ids[$name];
            }

            foreach ($movies as $movie) {
                if (!is_null($movie->$getFunc())) {
                    $id = $movie->$getFunc();
                }
            }

            if (is_null($id)) {
                $moviePartner = $sourceMovie->getPartner();
                if (!is_null($partner) && !is_null($moviePartner) && $moviePartner->getCode() == $partner->value) {
                    $id = $sourceMovie->getInternalPartnerId();
                }
            }

            if (!is_null($id)) {
                $selectedMovie->$setFunc($id);
            }
        }
    }

    /**
     * @param array<Movie> $movies
     */
    private function updateXField(
        Movie $selectedMovie,
        array $movies,
        SourceMovie $sourceMovie,
        string $field,
        bool $bool = false
    ): void {
        $getFunc = "get" . ucwords($field);
        if ($bool) {
            $getFunc = "is" . ucwords($field);
        }

        $setFunc = "set" . ucwords($field);

        if (is_null($selectedMovie->$getFunc())) {
            $value = null;

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
