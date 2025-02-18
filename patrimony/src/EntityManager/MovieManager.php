<?php

namespace App\EntityManager;

use App\Entity\Patrimony\ActorMovie;
use App\Entity\Patrimony\Movie;
use App\Entity\Source\SourceMovie;
use App\Enum\PartnerCode;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for managing movie-related operations.
 */
class MovieManager
{

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private NationalityManager $nationalityManager,
        private GenreManager $genreManager,
        private DirectorManager $directorManager,
        private PublicManager $publicManager,
        private ActorManager $actorManager,
    )
    {

    }

    public function create(SourceMovie $sourceMovie) : Movie {
        $this->logger->info('Create movie : ' . $sourceMovie->getTitle());

        $partner = $sourceMovie->getPartner()->getCode();

        switch($partner) {
            case PartnerCode::ARTE->value:
                $idName = 'arteId';
                break;
            case PartnerCode::CANAL_VOD->value:
                $idName = 'canalVodId';
                break;
            case PartnerCode::ORANGE_VOD->value:
                $idName = 'orangeVodId';
                break;
            case PartnerCode::LACINETEK_SVOD->value:
            case PartnerCode::LACINETEK_TVOD->value:
              $idName = 'laCinetekId';
              break;
            case PartnerCode::FRANCE_TV->value:
                $idName = 'franceTvId';
                break;
            case PartnerCode::CNC->value:
            default:
                $idName = 'cncId';
        }

        // Find movie with internal partner id.
        $repository = $this->entityManager->getRepository(Movie::class);
        $movie = $repository->findOneBy([$idName => $sourceMovie->getInternalPartnerId()]);

        if (is_null($movie)) {
            $movie = new Movie();
            $movie->setTitle($sourceMovie->getTitle());
            $movie->setCode($sourceMovie->getCode());

            switch($partner) {
                case PartnerCode::CNC->value:
                    $movie->setCncId($sourceMovie->getInternalPartnerId());
                    break;
                case PartnerCode::ARTE->value:
                    $movie->setArteId($sourceMovie->getInternalPartnerId());
                    break;
                case PartnerCode::CANAL_VOD->value:
                    $movie->setCanalVodId($sourceMovie->getInternalPartnerId());
                    break;
                case PartnerCode::ORANGE_VOD->value:
                    $movie->setOrangeVodId($sourceMovie->getInternalPartnerId());
                    break;
                case PartnerCode::LACINETEK_TVOD->value:
                case PartnerCode::LACINETEK_SVOD->value:
                  $movie->setLaCinetekId($sourceMovie->getInternalPartnerId());
                  break;
                case PartnerCode::FRANCE_TV->value:
                    $movie->setFranceTvId($sourceMovie->getInternalPartnerId());
                    break;
                default:
                    break;
            }

            $this->entityManager->persist($movie);
        }

        // Set external ids.
        $externalIds = $sourceMovie->getExternalIds();
        if (isset($externalIds['allocine']) && !empty($externalIds['allocine'])) {
            $movie->setAllocineId($externalIds['allocine']);
        }

        if (isset($externalIds['isan']) && !empty($externalIds['isan'])) {
            $movie->setIsanId($externalIds['isan']);
        }

        $movie->setHasAd($sourceMovie->isHasAd());
        $movie->setPoster($sourceMovie->getPoster());
        $movie->setSynopsis($sourceMovie->getSynopsis());
        $movie->setDuration($sourceMovie->getDuration());
        $movie->setProductionYear($sourceMovie->getProductionYear());

        $this->entityManager->flush();

        // Find or create $nationalities.
        $sourceNationalities = $sourceMovie->getNationalities();
        $nationalities = [];

        foreach ($sourceNationalities as $sourceNationality) {
            $nationalities[] = $this->nationalityManager->provide($sourceNationality);
        }

        $movie->setNationalities(new ArrayCollection($nationalities));

        // Find or create $genres.
        $sourceGenres = $sourceMovie->getGenres();
        $genres = [];

        foreach ($sourceGenres as $sourceGenre) {
            $genres[] = $this->genreManager->provide($sourceGenre);
        }
        $movie->setGenres(new ArrayCollection($genres));

        // Find or create $directors.
        $sourceDirectors = $sourceMovie->getDirectors();
        $directors = [];

        foreach ($sourceDirectors as $sourceDirector) {
            $directors[] = $this->directorManager->findOrCreate($sourceDirector);
        }
        $movie->setDirectors(new ArrayCollection($directors));

        // Find or create $actors..
        $sourceActors = $sourceMovie->getCasting();

        foreach ($sourceActors as $sourceActor) {
            $actor = $this->actorManager->findOrCreate($sourceActor);
            $this->entityManager->persist($actor);

            $this->entityManager->flush();

            $actorMovie = new ActorMovie();
            $actorMovie->setActor($actor);
            $actorMovie->setMovie($movie);

            if (isset($sourceActor['role']) && !empty($sourceActor['role'])) {
                $actorMovie->setRole($sourceActor['role']);
            }

            $this->entityManager->persist($actorMovie);
        }

        // Find or create $public.
        $sourcePublic = $sourceMovie->getPublic();
        if (!is_null($sourcePublic)) {
            $public = $this->publicManager->createOrUpdate($sourcePublic);
            $movie->setPublic($public);
        }

        return $movie;
    }
}
