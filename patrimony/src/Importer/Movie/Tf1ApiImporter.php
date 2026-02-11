<?php

namespace App\Importer\Movie;

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
use App\Service\MovieFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports movies from TF1 API.
 */
class Tf1ApiImporter implements MovieImporterInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceMovieManager $sourceMovieManager,
        private SolutionManager $solutionManager,
        private MovieManager $movieManager,
        private ParameterBagInterface $parameterBag,
        private HttpClientInterface $httpClient,
        private MovieRepository $movieRepository,
        private SolutionRepository $solutionRepository,
        private SourceMovieRepository $sourceMovieRepository,
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

        $url = $this->parameterBag->get('tf1.api.url');
        $user = $this->parameterBag->get('tf1.api.user');
        $password = $this->parameterBag->get('tf1.api.password');

        $response = $this->httpClient->request('GET', $url, [
            'auth_basic' => [$user, $password],
            'max_redirects' => 5,
        ]);

        $programs = $response->toArray();

        // Create partner ARTE if not exists.
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['code' => PartnerCode::TF1->value]);

        if (is_null($partner)) {
            $partner = new Partner();
            $partner->setCode(PartnerCode::TF1->value);
            $partner->setName(PartnerCode::TF1->value);
            $this->entityManager->persist($partner);
        }

        // Create offer FREE_ACCESS if not exists.
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        $offer = $offerRepository->findOneBy(['code' => OfferCode::FREE_ACCESS->value]);

        if (is_null($offer)) {
            $offer = new Offer();
            $offer->setCode(OfferCode::FREE_ACCESS->value);
            $offer->setName(ucfirst(OfferCode::FREE_ACCESS->value));
            $this->entityManager->persist($offer);
        }

        $this->movieRepository->updatedAllMovieWithPartner($partner);
        $this->solutionRepository->deleteAllByPartner($partner);
        $this->sourceMovieRepository->deleteAllByPartner($partner);

        $this->entityManager->flush();

        foreach ($programs as $program) {
            $partner = $partnerRepository->findOneBy(['code' => PartnerCode::TF1->value]);
            $offer = $offerRepository->findOneBy(['code' => OfferCode::FREE_ACCESS->value]);

            $repository = $this->entityManager->getRepository(SourceMovie::class);
            $sourceMovie = $repository->findOneBy([
                'internalPartnerId' => $program['id'],
                'partner' => $partner,
            ]);

            $title = $program['title'];
            dump($title);

            if (is_null($sourceMovie)) {
                $sourceMovie = $this->sourceMovieManager->findOrcreate(
                    $title,
                    $program['id'],
                    $program['productionYear'],
                    $partner,
                    TRUE
                );
            }

            if (isset($program['castings'])) {
                $directors = [];
                $casting = [];

                foreach ($program['castings'] as $artwork) {
                    switch ($artwork['job']['type']) {
                        case 'Réalisateur':
                            foreach ($artwork['persons'] as $person) {
                                if (isset($person['firstName']) && !empty($person['firstName'])) {
                                    $directors[] = [
                                        'fullname' => $person['firstName'] . ' ' . $person['lastName']
                                    ];
                                } else {
                                    $directors[] = [
                                        'fullname' => $person['lastName']
                                    ];
                                }
                            }
                            break;
                        case 'Acteur':
                            foreach ($artwork['persons'] as $person) {

                                if (isset($person['firstName']) && !empty($person['firstName'])) {
                                    $casting[] = [
                                        'fullname' => $person['firstName'] . ' ' . $person['lastName'],
                                        'role' => $person['role'],
                                    ];
                                } else {
                                    $casting[] = [
                                        'fullname' => $person['lastName'],
                                        'role' => $person['role'],
                                    ];
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }

                if (!empty($directors)) {
                    $sourceMovie->setDirectors($directors);
                }

                if (!empty($casting)) {
                    $sourceMovie->setCasting($casting);
                }
            }

            // @TODO : check how genres is used.
            if (!empty($genres)) {
                $sourceMovie->setGenres($program['genres']);
            }

            $synopsis = $program['synopsis'];
            $sourceMovie->setSynopsis($synopsis);

            $duration = $program['duration'];
            $sourceMovie->setDuration($duration);

            $poster = $program['poster'];
            $sourceMovie->setPoster($poster);

            $this->entityManager->persist($sourceMovie);

            $ids = [];
            $ids['tf1Id'] = $program['id'];

            if (isset($program['externalIds']['imdb']) && !empty($program['externalIds']['imdb'])) {
                $ids['imdbId'] = $program['externalIds']['imdb'];
            }

            if (isset($program['externalIds']['plurimedia']) && !empty($program['externalIds']['plurimedia'])) {
                $ids['plurimediaId'] = $program['externalIds']['plurimedia'];
            }

            $solution = $this->solutionManager->createOrUpdate(
                $program['id'],
                $partner,
                $sourceMovie,
                $offer,
                $program['link'],
                $program['offers'][0]['startDate'],
                $program['offers'][0]['endDate'],
            );

            $this->entityManager->persist($solution);

            $movie = null;
            if ($createMoviesOption) {
                //$movie = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());
                $movie = $this->movieFetcher->fetchByIds($ids, $sourceMovie);

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
