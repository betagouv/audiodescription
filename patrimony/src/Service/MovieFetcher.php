<?php

namespace App\Service;

use App\Entity\Patrimony\Movie;
use App\Entity\Source\SourceMovie;
use App\Repository\MovieRepository;

class MovieFetcher
{
    public function __construct(
        private MovieRepository $movieRepository,
        private MovieAutoMerger $movieAutoMerger,
    ) {
    }

    /**
     * @param array<string, mixed> $ids
     */
    public function fetchByIds(array $ids, SourceMovie $sourceMovie): ?Movie
    {
        $movies = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());

        if (empty($movies)) {
            return null;
        }

        if (count($movies) === 1) {
            return $movies[0];
        }

        return $this->movieAutoMerger->autoMerge($ids, $movies, $sourceMovie);
    }
}
