<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Partner;
use App\Entity\Source\SourceMovie;
use App\Util\EntityCodeService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class responsible for managing movie-related operations.
 */
class SourceMovieManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityCodeService      $entityCodeService,
    )
    {

    }

    /**
     * Function to find or create source movie.
     *
     * @return SourceMovie
     *   Source movie created.
     */
    public function findOrcreate(
        string $title,
        string $internalPartnerId,
        string $productionYear,
        Partner $partner,
        bool $hasAd
    ): SourceMovie
    {
        $repository = $this->entityManager->getRepository(SourceMovie::class);
        $sourceMovie = $repository->findOneBy([
            'partner' => $partner,
            'internalPartnerId' => $internalPartnerId,
        ]);

        if (is_null($sourceMovie)) {
            $sourceMovie = new SourceMovie();

            $sourceMovie->setTitle($title);
            $code = $this->entityCodeService->computeCode($title . '__' . $productionYear);
            $sourceMovie->setCode($code);

            $sourceMovie->setInternalPartnerId($internalPartnerId);
            $sourceMovie->setPartner($partner);
            $sourceMovie->setProductionYear($productionYear);

            $sourceMovie->setHasAd($hasAd);
        }
        return $sourceMovie;
    }
}
