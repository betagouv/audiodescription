<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Genre;
use App\Util\EntityCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for managing genre-related operations.
 */
class GenreManager
{

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private EntityCodeService      $entityCodeService,
    )
    {

    }

    /**
     * Function to create genre or update if it exists.
     *
     * @return Genre
     *   Genre created or updated.
     */
    public function provide(string $genreName): ?Genre
    {
        $genreCode = $this->entityCodeService->computeCode($genreName);

        $repository = $this->entityManager->getRepository(Genre::class);
        $genre = $repository->findOneBy(['code' => $genreCode]);

        if (is_null($genre)) {
            $genre = new Genre();
            $genre->setName($genreName);
            $genre->setCode($genreCode);

            $this->entityManager->persist($genre);
            $this->entityManager->flush();
        }
        
        return $genre;
    }
}
