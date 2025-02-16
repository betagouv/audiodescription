<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Actor;
use App\Entity\Patrimony\ActorMovie;
use App\Entity\Patrimony\Movie;
use App\Util\EntityCodeService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class responsible for managing director-related operations.
 */
class ActorManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityCodeService      $codeService,
    )
    {

    }

    /**
     * Function to create actor or update if it exists.
     *
     * @return Actor
     *   Actor created or updated.
     */
    public function findOrCreate(array $actorData): ?Actor {
        $actorCode = $this->codeService->computeCode($actorData['fullname']);

        $repository = $this->entityManager->getRepository(Actor::class);
        $actor = $repository->findOneBy(['code' => $actorCode]);

        if (is_null($actor)) {
            $actor = new Actor();
            $actor->setFullname($actorData['fullname']);
            $actor->setCode($actorCode);
            if (isset($actorData['firstname']) && !empty($actorData['firstname'])) {
                $actor->setFirstname($actorData['firstname']);
            }

            if (isset($actorData['lastname']) && !empty($actorData['lastname'])) {
                $actor->setLastname($actorData['lastname']);
            }
        }

        return $actor;
    }

}
