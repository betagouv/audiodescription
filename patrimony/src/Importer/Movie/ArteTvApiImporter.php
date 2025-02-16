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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports movies from a CNC CSV file.
 */
class ArteTvApiImporter implements MovieImporterInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceMovieManager $sourceMovieManager,
        private SolutionManager $solutionManager,
        private MovieManager $movieManager,
        private ParameterBagInterface $parameterBag,
        private HttpClientInterface $httpClient,
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

        $url = $this->parameterBag->get('arte_tv.api.url');
        $response = $this->httpClient->request('GET', $url);

        $programs = $response->toArray()['programs'];

        // Create partner ARTE if not exists.
        $repository = $this->entityManager->getRepository(Partner::class);
        $partner = $repository->findOneBy(['name' => PartnerCode::ARTE->value]);

        if (is_null($partner)) {
            $partner = new Partner();
            $partner->setCode(PartnerCode::ARTE->value);
            $partner->setName(PartnerCode::ARTE->value);
            $this->entityManager->persist($partner);
        }

        // Create offer STREAMING if not exists.
        $repository = $this->entityManager->getRepository(Offer::class);
        $offer = $repository->findOneBy(['code' => OfferCode::STREAMING->value]);

        if (is_null($offer)) {
            $offer = new Offer();
            $offer->setCode(OfferCode::STREAMING->value);
            $offer->setName(ucfirst(OfferCode::STREAMING->value));
            $this->entityManager->persist($offer);
        }

        foreach ($programs as $program) {
            if ($program['genrePresse'] !== 'Téléfilm') {
                $repository = $this->entityManager->getRepository(SourceMovie::class);
                $sourceMovie = $repository->findOneBy([
                    'internalPartnerId' => $program['programId'],
                    'partner' => $partner,
                ]);

                $title = $program['title'];
                dump($title);

                if (is_null($sourceMovie)) {

                    $sourceMovie = $this->sourceMovieManager->findOrcreate(
                        $title,
                        $program['programId'],
                        $program['productionYear'],
                        $partner,
                        TRUE
                    );
                }

                $directors = [
                    [
                        'fullname' => $program['director'],
                    ],
                ];
                $sourceMovie->setDirectors($directors);

                $casting = [];
                foreach($program['casting'] as $member) {
                    $casting[] = [
                        'fullname' => $member['name'],
                        'role' => $member['characterName'],
                    ];
                }
                $sourceMovie->setCasting($casting);

                // Genres are not relevant in the data provided by Arte.
                //$genres = [$program['genre']['label']];
                //$sourceMovie->setGenres($genres);

                $synopsis = $program['shortDescription'];
                $sourceMovie->setSynopsis($synopsis);

                $duration = intdiv($program['durationSeconds'], 60);;
                $sourceMovie->setDuration($duration);

                $this->entityManager->persist($sourceMovie);

                $solution = $this->solutionManager->createOrUpdate(
                    $program['programId'],
                    $partner,
                    $sourceMovie,
                    $offer,
                    $program['url'],
                    $program['videoRightsBegin'],
                    $program['videoRightsEnd'],
                );

                $this->entityManager->persist($solution);

                if ($createMoviesOption) {
                    $repository = $this->entityManager->getRepository(Movie::class);
                    $movie = $repository->findOneBy([
                        'arteId' => $program['programId'],
                    ]);

                    if (empty($movie)) {
                        $movie = $this->movieManager->create($sourceMovie);
                        $this->entityManager->persist($movie);
                    }

                    $sourceMovie->setMovie($movie);
                    $solution->setMovie($movie);
                }

                $this->entityManager->flush();

                $this->entityManager->detach($sourceMovie);
                $this->entityManager->detach($solution);
            }
        }
    }
}
