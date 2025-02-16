<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Director;
use App\Util\EntityCodeService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class responsible for managing director-related operations.
 */
class DirectorManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityCodeService      $codeService,
    )
    {

    }

    /**
     * Function to create director or update if it exists.
     *
     * @return Director
     *   Director created or updated.
     */
    public function findOrCreate(array $directorData): ?Director {
        $directorCode = $this->codeService->computeCode($directorData['fullname']);

        $repository = $this->entityManager->getRepository(Director::class);
        $director = $repository->findOneBy(['code' => $directorCode]);

        if (is_null($director)) {
            $director = new Director();
            $director->setFullname($directorData['fullname']);
            $director->setCode($directorCode);

            if (isset($directorData['firstname']) && !empty($directorData['firstname'])) {
                $director->setFirstname($directorData['firstname']);
            }

            if (isset($directorData['lastname']) && !empty($directorData['lastname'])) {
                $director->setLastname($directorData['lastname']);
            }

            $this->entityManager->persist($director);
            $this->entityManager->flush();
        }

        return $director;
    }

}
