<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Offer;
use App\Entity\Patrimony\Partner;
use App\Entity\Patrimony\Solution;
use App\Entity\Source\SourceMovie;
use App\Util\EntityCodeService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class responsible for managing movie-related operations.
 */
class SolutionManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {

    }

    /**
     * Function to create solution.
     *
     * @return Solution
     *   Solution created.
     */
    public function createOrUpdate(
        string $internalPartnerId,
        Partner $partner,
        SourceMovie $sourceMovie,
        Offer $offer,
        string $link,
        ?string $startRights,
        ?string $endRights
    ): Solution
    {
        $repository = $this->entityManager->getRepository(Solution::class);
        $solution = $repository->findOneBy([
            'partner' => $partner,
            'internalPartnerId' => $internalPartnerId,
        ]);

        if (is_null($solution)) {
            $solution = new Solution();
            $solution->setInternalPartnerId($internalPartnerId);
            $solution->setPartner($partner);
        }

        $solution->setSourceMovie($sourceMovie);
        $solution->setOffer($offer);
        $solution->setLink($link);

        if (!is_null($startRights)) {
            $solution->setStartRights(new DateTime($startRights));
        }

        if (!is_null($endRights)) {
            $solution->setEndRights(new DateTime($endRights));
        }

        return $solution;
    }
}
