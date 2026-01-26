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
use App\Service\MovieFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports movies from a Canal VOD API.
 */
class CanalReplayApiImporter implements MovieImporterInterface
{

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

        $url = $this->parameterBag->get('canal_replay.api.url');
        $authToken = $this->parameterBag->get('canal_vod.api.auth_token');
        $headers = [
            'headers' => [
                'Authorization' => 'Basic ' . $authToken,
            ],
        ];
        $response = $this->httpClient->request('GET', $url, $headers);

        $programUrls = $response->toArray()['locations'];

        // Create partner CANAL REPLAY if not exists.
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['code' => PartnerCode::CANAL_REPLAY->value]);

        if (is_null($partner)) {
            $partner = new Partner();
            $partner->setCode(PartnerCode::CANAL_REPLAY->value);
            $partner->setName(PartnerCode::CANAL_REPLAY->value);
            $this->entityManager->persist($partner);
        }

        // Create offer SVOD if not exists.
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        $offer = $offerRepository->findOneBy(['code' => OfferCode::SVOD->value]);

        if (is_null($offer)) {
            $offer = new Offer();
            $offer->setCode(OfferCode::SVOD->value);
            $offer->setName(ucfirst(OfferCode::SVOD->value));
            $this->entityManager->persist($offer);
        }

        $this->movieRepository->updatedAllMovieWithPartner($partner);
        $this->solutionRepository->deleteAllByPartner($partner);
        $this->sourceMovieRepository->deleteAllByPartner($partner);

        $this->entityManager->flush();

        foreach ($programUrls as $programUrl) {
            $partner = $partnerRepository->findOneBy(['code' => PartnerCode::CANAL_REPLAY->value]);
            $offer = $offerRepository->findOneBy(['code' => OfferCode::SVOD->value]);

            $response = $this->httpClient->request('GET', $programUrl, $headers);
            $program = $response->toArray();

            if ($program['contentType'] == 'film') {
                if (in_array('fr', $program['language']['qad'])) {

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

                    $directors = [];
                    $casting = [];

                    if (in_array('castings', array_keys($program))) {
                        foreach ($program['castings'] as $programCasting) {
                            if ($programCasting['job']['type'] == 'Réalisateur') {
                                foreach ($programCasting['persons'] as $person) {

                                    $firstname = $person['firstName'] ?? '';
                                    $lastname = $person['lastName'] ?? '';

                                    if (!empty($firstname) && !empty($lastname)) {
                                        $fullname = $firstname . ' ' . $lastname;
                                    } else if (!empty($firstname)) {
                                        $fullname = $firstname;
                                    } else if (!empty($lastname)) {
                                        $fullname = $lastname;
                                    } else {
                                        $fullname = '';
                                    }

                                    $directors[] = [
                                        'fullname' => $fullname,
                                        'firstname' => $firstname,
                                        'lastname' => $lastname,
                                    ];
                                }
                            }
                        }

                        foreach ($program['castings'] as $programCasting) {
                            if ($programCasting['job']['type'] == 'Acteur') {
                                foreach ($programCasting['persons'] as $person) {
                                    $firstname = $person['firstName'] ?? '';
                                    $lastname = $person['lastName'] ?? '';

                                    if (!empty($firstname) && !empty($lastname)) {
                                        $fullname = $firstname . ' ' . $lastname;
                                    } else if (!empty($firstname)) {
                                        $fullname = $firstname;
                                    } else if (!empty($lastname)) {
                                        $fullname = $lastname;
                                    } else {
                                        $fullname = '';
                                    }

                                    $casting[] = [
                                        'fullname' => $fullname,
                                        'firstname' => $firstname,
                                        'lastname' => $lastname,
                                        'role' => $person['role'],
                                    ];
                                }
                            }
                        }
                    }

                    $sourceMovie->setDirectors($directors);
                    $sourceMovie->setCasting($casting);

                    $parts = explode('-', $program['genre']['secondary'], 2);
                    $genre = $parts[1] ?? $parts[0];
                    $sourceMovie->setGenres([$genre]);

                    $synopsis = $program['synopsis']['large'];
                    $sourceMovie->setSynopsis($synopsis);

                    $duration = $program['duration'];
                    $sourceMovie->setDuration($duration);

                    $ids = [];
                    $ids['canalVodId'] = $program['id'];
                    // External ids.
                    if (!empty($program['externalIds']['ALLOCINE'])) {
                        $externalIds = [
                            'allocine' => $program['externalIds']['ALLOCINE'][0]
                        ];
                        $ids['allocineId'] = $program['externalIds']['ALLOCINE'][0];
                        $sourceMovie->setExternalIds($externalIds);
                    }

                    // Public.
                    if (isset($program['parentalRatings'][0]['value'])) {
                        switch ($program['parentalRatings'][0]['value']) {
                            case '1':
                                // Tous publics.
                                $sourceMovie->setPublic('TP');
                                break;
                            case '2':
                                // Non recommandé avant 10 ans.
                                $sourceMovie->setPublic('10');
                                break;
                            case '3':
                                // Non recommandé avant 12 ans.
                                $sourceMovie->setPublic('12');
                                break;
                            case '4':
                                // Non recommandé avant 16 ans.
                                $sourceMovie->setPublic('16');
                                break;
                            case '5':
                                // Non recommandé avant 18 ans.
                                $sourceMovie->setPublic('18');
                                break;
                            default:
                                break;
                        }
                    }

                    // Poster.
                    foreach ($program['pictures'] as $picture) {
                        if (in_array('JAQCANAL', array_keys($picture))) {
                            $poster = $picture['JAQCANAL'];
                            $sourceMovie->setPoster($poster);
                        }
                    }

                    // Nationalities.
                    $programNationalities = $program['productionNationalities'];
                    $nationalities = [];

                    foreach($programNationalities as $nationality) {
                      $nationalities[] = $nationality['name'];
                    }

                    $sourceMovie->setNationalities($nationalities);

                    $this->entityManager->persist($sourceMovie);

                    $solution = $this->solutionManager->createOrUpdate(
                        $program['id'],
                        $partner,
                        $sourceMovie,
                        $offer,
                        $program['deeplink'][0]['url'],
                        $program['availability']['startDate'],
                        $program['availability']['endDate'],
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
    }
}
