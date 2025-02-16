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
use App\Parser\CsvParser;
use App\Repository\MovieRepository;
use App\Repository\SolutionRepository;
use App\Repository\SourceMovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Imports movies from Orange VOD File.
 */
class OrangeVodCsvImporter implements MovieImporterInterface
{

    private string $dataDir;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag,
        private CsvParser $csvParser,
        private MovieRepository $movieRepository,
        private SourceMovieRepository $sourceMovieRepository,
        private SolutionRepository $solutionRepository,
        private SourceMovieManager $sourceMovieManager,
        private SolutionManager $solutionManager,
        private MovieManager $movieManager,

    )
    {
        $this->dataDir = sprintf('%s/data/', $this->parameterBag->get('kernel.project_dir'));
    }

    /**
     * Imports movie data from a source.
     */
    public function import(?array $options = []): void
    {
        // Manage options.
        $createMoviesOption = $options['create-movies'];

        $file = sprintf(
            '%s/%s',
            $this->dataDir,
            $this->parameterBag->get('orange_vod.filename')
        );

        $lines = $this->csvParser->parseCsv($file, ';');

        // Create partner Orange if not exists.
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['code' => PartnerCode::ORANGE_VOD->value]);

        if (is_null($partner)) {
            $partner = new Partner();
            $partner->setCode(PartnerCode::ORANGE_VOD->value);
            $partner->setName(PartnerCode::ORANGE_VOD->value);
            $this->entityManager->persist($partner);
        }

        // Create offer TVOD if not exists.
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        $offer = $offerRepository->findOneBy(['code' => OfferCode::TVOD->value]);

        if (is_null($offer)) {
            $offer = new Offer();
            $offer->setCode(OfferCode::TVOD->value);
            $offer->setName(ucfirst(OfferCode::TVOD->value));
            $this->entityManager->persist($offer);
        }

        $this->movieRepository->updatedAllMovieWithPartner($partner);
        $this->solutionRepository->deleteAllByPartner($partner);
        $this->sourceMovieRepository->deleteAllByPartner($partner);

        $this->entityManager->flush();

        foreach ($lines as $line) {
            $type = $line['Category'];
            $internalPartnerId = $line['Product code'];

            if ($type == 'film') {
                $partner = $partnerRepository->findOneBy(['code' => PartnerCode::ORANGE_VOD->value]);
                $offer = $offerRepository->findOneBy(['code' => OfferCode::TVOD->value]);

                $repository = $this->entityManager->getRepository(SourceMovie::class);
                $sourceMovie = $repository->findOneBy([
                    'internalPartnerId' => $internalPartnerId,
                    'partner' => $partner,
                ]);

                $title = $line['Title'];
                dump($title);

                $productionYear = $line['Production year'];
                $ids['orangeVodId'] = $internalPartnerId;
                $ids['isanId'] = $line['Isan'];

                if (is_null($sourceMovie)) {
                    $sourceMovie = $this->sourceMovieManager->findOrcreate(
                        $title,
                        $internalPartnerId,
                        $productionYear,
                        $partner,
                        TRUE
                    );
                }

                if (!empty($line['Réalisateur'])) {
                    $directors = [
                        [
                            'fullname' => $line['Réalisateur'],
                        ]
                    ];

                    $sourceMovie->setDirectors($directors);
                }

                $casting = [];
                if (!empty($line['Actor 1'])) {
                    $casting[] = [
                        'fullname' => $line['Actor 1'],
                        'role' => $line['Actor 1 role'],
                    ];
                }

                if (!empty($line['Actor 2'])) {
                    $casting[] = [
                        'fullname' => $line['Actor 2'],
                        'role' => $line['Actor 2 role'],
                    ];
                }

                if (!empty($line['Actor 3'])) {
                    $casting[] = [
                        'fullname' => $line['Actor 3'],
                        'role' => $line['Actor 3 role'],
                    ];
                }

                $sourceMovie->setCasting($casting);

                $genre = $line['Genre'];
                $sourceMovie->setGenres([$genre]);

                $durationValue = $line['Product duration'];
                list($hours, $minutes) = explode(':', $durationValue);
                $duration = ((int)$hours * 60) + (int)$minutes;
                $sourceMovie->setDuration($duration);

                // External ids.
                if (!empty($ids)) {
                    $externalIds = [
                        'isan' => $ids['isanId'],
                    ];
                    $sourceMovie->setExternalIds($externalIds);
                }

                // Public.
                if (isset($line['Parental rating'])) {
                    switch ($line['Parental rating']) {
                        case 'tous publics':
                            // Tous publics.
                            $sourceMovie->setPublic('TP');
                            break;
                        default:
                            break;
                    }
                }

                $this->entityManager->persist($sourceMovie);

                $solution = $this->solutionManager->createOrUpdate(
                    $internalPartnerId,
                    $partner,
                    $sourceMovie,
                    $offer,
                    $line['URL'],
                    null,
                    null,
                );

                $this->entityManager->persist($solution);

                $movie = null;
                if ($createMoviesOption) {
                  if ($title == "Des hommes") {
                    dump($ids);
                    dump($sourceMovie->getCode());
                  }

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
