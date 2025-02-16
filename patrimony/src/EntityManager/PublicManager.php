<?php

namespace App\EntityManager;

use App\Entity\Patrimony\PublicRestriction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class responsible for managing public-related operations.
 */
class PublicManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {

    }

    /**
     * Create or update public taxonomy term.
     */
    public function createOrUpdate(string $publicCode, ?string $publicName = NULL): ?PublicRestriction
    {
        $repository = $this->entityManager->getRepository(PublicRestriction::class);
        $public = $repository->findOneBy(['code' => $publicCode]);

        if (is_null($public)) {
            $public = new PublicRestriction();
            if (is_null($publicName)) $publicName = $publicCode;
            $public->setName($publicName);
            $public->setCode($publicCode);

            $this->entityManager->persist($public);
            $this->entityManager->flush();
        }

        return $public;
    }
}
