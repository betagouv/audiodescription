<?php

namespace App\Importer\Movie;

use App\Entity\Patrimony\Movie;
use App\Entity\Patrimony\Offer;
use App\Entity\Patrimony\Partner;
use App\Entity\Source\SourceMovie;
use App\EntityManager\MovieManager;
use App\EntityManager\SolutionManager;
use App\EntityManager\SourceMovieManager;
use App\Enum\OfferCode;
use App\Enum\PartnerCode;
use App\Repository\MovieRepository;
use App\Repository\SolutionRepository;
use App\Repository\SourceMovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports movies from LaCinetek API.
 */
class LaCinetekApiImporter implements MovieImporterInterface
{
    const NB_PER_PAGE = 200;

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
        $partner = $partnerRepository->findOneBy(['code' => PartnerCode::LACINETEK->value]);

        if (is_null($partner)) {
            $partner = new Partner();
            $partner->setCode(PartnerCode::LACINETEK->value);
            $partner->setName(PartnerCode::LACINETEK->value);
            $this->entityManager->persist($partner);
        }

        // Create offer STREAMING if not exists.
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        $offer = $offerRepository->findOneBy(['code' => OfferCode::STREAMING->value]);

        if (is_null($offer)) {
            $offer = new Offer();
            $offer->setCode(OfferCode::STREAMING->value);
            $offer->setName(ucfirst(OfferCode::STREAMING->value));
            $this->entityManager->persist($offer);
        }

        $this->movieRepository->updatedAllMovieWithPartner($partner);
        $this->solutionRepository->deleteAllByPartner($partner);
        $this->sourceMovieRepository->deleteAllByPartner($partner);

        $this->entityManager->flush();

        for ($i = 1; $i <= $totalPage ; $i++) {
          $response = $this->httpClient->request('GET', $urlWithParams . '&page='. $i, $headers);
          $data = $response->toArray();
          $movies = $data['data'];

          foreach ($movies as $movie) {
            $title = $movie['localized_title'][0]['value'];

            if (isset($movie['audioDescription'])) {
              dump($title);

              $partner = $partnerRepository->findOneBy(['code' => PartnerCode::LACINETEK->value]);
              $offer = $offerRepository->findOneBy(['code' => OfferCode::STREAMING->value]);

              $internalPartnerId = $movie['id'];
              $repository = $this->entityManager->getRepository(SourceMovie::class);

              $sourceMovie = $repository->findOneBy([
                'internalPartnerId' => $internalPartnerId,
                'partner' => $partner,
              ]);

              $productionYear = $movie['year'];

              $ids = [];
              $ids['laCinetekId'] = $internalPartnerId;

              if (!is_null($movie['url']['alloCine'])) {
                preg_match('/cfilm=([0-9]+)\.html/', $movie['url']['alloCine'], $matches);

                if (!empty($matches[1])) {
                  $ids['allocineId'] = $matches[1];
                }
              }

              if (!is_null($movie['url']['imbd'])) {
                dd($movie['url']['imbd']);
              }

              if (is_null($sourceMovie)) {
                $sourceMovie = $this->sourceMovieManager->findOrcreate(
                  $title,
                  $internalPartnerId,
                  $productionYear,
                  $partner,
                  TRUE
                );
              }

              $actors = $movie['actors'];
              $casting = [];
              foreach ($actors as $actor) {
                $casting[] = [
                  'fullname' => $actor['name'],
                  'role' => $actor['role'],
                ];
              }

              $sourceMovie->setCasting($casting);

              $durationValue = $movie['duration'];
              list($hours, $minutes) = explode('h', $durationValue);
              $duration = ((int)$hours * 60) + (int)$minutes;
              $sourceMovie->setDuration($duration);

              // External ids.
              if (!isset($ids['allocineId']) && !empty($ids['allocineId'])) {
                $externalIds = [
                  'allocine' => $ids['allocineId'],
                ];
                $sourceMovie->setExternalIds($externalIds);
              }

              $this->entityManager->persist($sourceMovie);

              $solution = $this->solutionManager->createOrUpdate(
                $internalPartnerId,
                $partner,
                $sourceMovie,
                $offer,
                $movie['audioDescription'],
                null,
                null,
              );

              $this->entityManager->persist($solution);

              $movie = null;
              if ($createMoviesOption) {
                $movie = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());

                if (is_null($movie)) {
                  $movie = $this->movieManager->create($sourceMovie);
                  $this->entityManager->persist($movie);
                }

                $sourceMovie->setMovie($movie);
                $solution->setMovie($movie);

              }

              $this->entityManager->flush();
              $this->entityManager->clear();

            }
          }
        }
    }
}
