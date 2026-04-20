<?php

namespace App\Importer\Movie;

use App\Entity\Patrimony\Movie;
use App\Entity\Patrimony\Offer;
use App\Entity\Patrimony\Partner;
use App\Entity\Source\SourceMovie;
use App\EntityManager\DirectorManager;
use App\EntityManager\MovieManager;
use App\EntityManager\SolutionManager;
use App\EntityManager\SourceMovieManager;
use App\Enum\OfferCode;
use App\Enum\PartnerCode;
use App\Repository\MovieRepository;
use App\Repository\SolutionRepository;
use App\Repository\SourceMovieRepository;
use App\Service\MovieFetcher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports movies from LaCinetek API.
 */
class LaCinetekApiImporter implements MovieImporterInterface
{
    const NB_PER_PAGE = 200;
    const LACINETEK_ROOT_PATH = 'https://www.lacinetek.com';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceMovieManager $sourceMovieManager,
        private SolutionManager $solutionManager,
        private MovieManager $movieManager,
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
        private SolutionRepository $solutionRepository,
        private SourceMovieRepository $sourceMovieRepository,
        private MovieRepository $movieRepository,
        private DirectorManager $directorManager,
        private MovieFetcher $movieFetcher,
    )
    {
    }

    /**
     * Imports movie data from a source.
     */
    public function import(?array $options = []): void
    {
        // Manage options.
        $createMoviesOption = $options['create-movies'];

        $url = $this->parameterBag->get('lacinetek.api.url');
        $urlWithParams = $url . '?size=' . self::NB_PER_PAGE;
        $authToken = $this->parameterBag->get('lacinetek.api.auth_token');
        $headers = [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
            ],
        ];
        $response = $this->httpClient->request('GET', $urlWithParams, $headers);
        $data = $response->toArray();

        $totalPage = $data['total_page'];

        // Create partner LACINETEK if not exists.
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partnerSvod = $partnerRepository->findOneBy(['code' => PartnerCode::LACINETEK_SVOD->value]);

        if (is_null($partnerSvod)) {
            $partnerSvod = new Partner();
            $partnerSvod->setCode(PartnerCode::LACINETEK_SVOD->value);
            $partnerSvod->setName(PartnerCode::LACINETEK_SVOD->value);
            $this->entityManager->persist($partnerSvod);
        }

        // Create offer SVOD if not exists.
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        $offerSvod = $offerRepository->findOneBy(['code' => OfferCode::SVOD->value]);

        if (is_null($offerSvod)) {
            $offerSvod = new Offer();
            $offerSvod->setCode(OfferCode::SVOD->value);
            $offerSvod->setName(ucfirst(OfferCode::SVOD->value));
            $this->entityManager->persist($offerSvod);
        }

        $this->movieRepository->updatedAllMovieWithPartner($partnerSvod);
        $this->solutionRepository->deleteAllByPartner($partnerSvod);
        $this->sourceMovieRepository->deleteAllByPartner($partnerSvod);

        // Create partner LACINETEK if not exists.
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partnerTvod = $partnerRepository->findOneBy(['code' => PartnerCode::LACINETEK_TVOD->value]);

        if (is_null($partnerTvod)) {
            $partnerTvod = new Partner();
            $partnerTvod->setCode(PartnerCode::LACINETEK_TVOD->value);
            $partnerTvod->setName(PartnerCode::LACINETEK_TVOD->value);
            $this->entityManager->persist($partnerTvod);
        }

        // Create offer SVOD if not exists.
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        $offerTvod = $offerRepository->findOneBy(['code' => OfferCode::TVOD->value]);

        if (is_null($offerTvod)) {
            $offerTvod = new Offer();
            $offerTvod->setCode(OfferCode::TVOD->value);
            $offerTvod->setName(ucfirst(OfferCode::TVOD->value));
            $this->entityManager->persist($offerTvod);
        }

        $this->movieRepository->updatedAllMovieWithPartner($partnerTvod);
        $this->solutionRepository->deleteAllByPartner($partnerTvod);
        $this->sourceMovieRepository->deleteAllByPartner($partnerTvod);

        $this->entityManager->flush();

        for ($i = 1; $i <= $totalPage ; $i++) {
          $response = $this->httpClient->request('GET', $urlWithParams . '&page='. $i, $headers);
          $data = $response->toArray();
          $movies = $data['data'];

          foreach ($movies as $movie) {
            if ($movie['audioDescription']['fr']) {
                foreach ($movie['localizedTitle'] as $localizedTitle) {
                    if ($localizedTitle['language'] == 'fr') {
                        $title = $localizedTitle['value'];
                    }
                }

                dump($title);

                $internalPartnerId = $movie['id'];
                $ids = [];
                $ids['laCinetekId'] = $internalPartnerId;
                $ids['allocineId'] = $movie['ids']['alloCine'] ?? null;

                $externalIds = [];
                if (!is_null($ids['allocineId'])) {
                    $externalIds = [
                        'allocine' => $ids['allocineId'],
                    ];
                }

                $productionYear = $movie['year'];

                $actors = $movie['actors'];
                $casting = [];
                foreach ($actors as $actor) {
                    $casting[] = [
                        'fullname' => $actor['name'],
                        'role' => $actor['role'],
                    ];
                }

                $synopsis = '';
                foreach ($movie['description'] as $description) {
                    if ($description['language'] == 'fr') {
                        $synopsis = html_entity_decode(strip_tags($description['value']));
                    }
                }

                $durationValue = $movie['duration'];
                $duration = null;
                if ($durationValue != 'no-duration') {
                    list($hours, $minutes) = explode('h', $durationValue);
                    $duration = ((int)$hours * 60) + (int)$minutes;
                }

                $directors = [];
                foreach ($movie['directors'] as $director) {
                    $directors[] = [
                        'fullname' => $director
                    ];
                }

                // Nationalities.
                $nationalities = array_map('trim', explode(',', $movie['origin']));

                $isTvod = $movie['availability']['tvod']['fr'];
                $isSvod = $movie['availability']['svod']['fr'];


              $partnerTvod = $partnerRepository->findOneBy(['code' => PartnerCode::LACINETEK_TVOD->value]);
              $partnerSvod = $partnerRepository->findOneBy(['code' => PartnerCode::LACINETEK_SVOD->value]);
              $offerTvod = $offerRepository->findOneBy(['code' => OfferCode::TVOD->value]);
              $offerSvod = $offerRepository->findOneBy(['code' => OfferCode::SVOD->value]);

              $sourceMovieTvod = null;
              $solutionTvod = null;
              if ($isTvod) {
                  $entitiesTvod = $this->createOffer(
                      $internalPartnerId,
                      $externalIds,
                      $title,
                      $productionYear,
                      $casting,
                      $duration,
                      $synopsis,
                      $directors,
                      $nationalities,
                      $partnerTvod,
                      $offerTvod,
                      self::LACINETEK_ROOT_PATH . $movie['urls']['film']['fr'],
                  );

                  $sourceMovieTvod = $entitiesTvod['sourceMovie'];
                  $solutionTvod = $entitiesTvod['solution'];
              }

              $sourceMovieSvod = null;
              $solutionSvod = null;
                if ($isSvod) {
                    $entitiesSvod = $this->createOffer(
                        $internalPartnerId,
                        $externalIds,
                        $title,
                        $productionYear,
                        $casting,
                        $duration,
                        $synopsis,
                        $directors,
                        $nationalities,
                        $partnerSvod,
                        $offerSvod,
                        self::LACINETEK_ROOT_PATH . $movie['urls']['svod']['fr'],
                    );

                    $sourceMovieSvod = $entitiesSvod['sourceMovie'];
                    $solutionSvod = $entitiesSvod['solution'];
                }

                $movie = null;

                if ($createMoviesOption && ($isSvod || $isTvod)) {
                    if (!is_null($sourceMovieSvod)) {
                        $sourceMovie = $sourceMovieSvod;
                    } else {
                        $sourceMovie = $sourceMovieTvod;
                    }

                    //$movie = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());
                    $movie = $this->movieFetcher->fetchByIds($ids, $sourceMovie);

                  if (is_null($movie)) {
                        $movie = $this->movieManager->create($sourceMovie);
                        $this->entityManager->persist($movie);
                    } else {
                        $sourceDirectors = $sourceMovie->getDirectors();
                        $directors = [];

                        foreach ($sourceDirectors as $sourceDirector) {
                            $directors[] = $this->directorManager->findOrCreate($sourceDirector);
                        }
                        $movie->setDirectors(new ArrayCollection($directors));
                    }

                    if ($isSvod) {
                        $sourceMovieSvod->setMovie($movie);
                        $solutionSvod->setMovie($movie);
                    }

                    if ($isTvod) {
                        $sourceMovieTvod->setMovie($movie);
                        $solutionTvod->setMovie($movie);
                    }
                }

              $this->entityManager->flush();
              $this->entityManager->clear();

            }
          }
        }
    }

    public function createOffer(
        $internalPartnerId,
        $externalIds,
        $title,
        $productionYear,
        $casting,
        $duration,
        $synopsis,
        $directors,
        $nationalities,
        $partner,
        $offer,
        $url
    ) {
        $sourceMovieRepository = $this->entityManager->getRepository(SourceMovie::class);

        $sourceMovie = $sourceMovieRepository->findOneBy([
            'internalPartnerId' => $internalPartnerId,
            'partner' => $partner,
        ]);

        if (is_null($sourceMovie)) {
            $sourceMovie = $this->sourceMovieManager->findOrcreate(
                $title,
                $internalPartnerId,
                $productionYear,
                $partner,
                TRUE
            );
        }

        $sourceMovie->setCasting($casting);
        if (!is_null($duration)) {
            $sourceMovie->setDuration($duration);
        }
        $sourceMovie->setSynopsis($synopsis);

        $sourceMovie->setExternalIds($externalIds);

        $sourceMovie->setDirectors($directors);

        $sourceMovie->setNationalities($nationalities);

        $this->entityManager->persist($sourceMovie);

        $solution = $this->solutionManager->createOrUpdate(
            $internalPartnerId,
            $partner,
            $sourceMovie,
            $offer,
            $url,
            null,
            null,
        );

        $this->entityManager->persist($solution);

        return [
            'solution' => $solution,
            'sourceMovie' => $sourceMovie,
        ];
    }
}
