<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Nationality;
use App\Util\EntityCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing nationality-related operations.
 */
class NationalityManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityCodeService      $entityCodeService,
    )
    {

    }

    /**
     * Function to create nationality or update if it exists.
     *
     * @return Nationality
     *   Nationality created or updated.
     */
    public function provide(string $nationalityName): ?Nationality
    {
        $nationalityCode = $this->entityCodeService->computeCode($nationalityName);

        $repository = $this->entityManager->getRepository(Nationality::class);
        $nationality = $repository->findOneBy(['code' => $nationalityCode]);


        if (is_null($nationality)) {
            $nationality = new Nationality();
            $nationality->setName($nationalityName);
            $nationality->setCode($nationalityCode);

            $this->entityManager->persist($nationality);
            $this->entityManager->flush();
        }

        return $nationality;
    }
}
