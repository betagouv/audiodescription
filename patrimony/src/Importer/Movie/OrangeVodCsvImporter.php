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
use App\Util\EntityCodeService;
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
        private EntityCodeService $entityCodeService,

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

                if (is_null($sourceMovie)) {
                    $sourceMovie = $this->sourceMovieManager->findOrcreate(
                        $title,
                        $internalPartnerId,
                        $productionYear,
                        $partner,
                        TRUE
                    );
                }

                $ids = [];
                $externalIds = [];
                $ids['orangeVodId'] = $internalPartnerId;

                if (isset($line['Isan']) && !empty($line['Isan'])) {
                    $ids['isanId'] = $line['Isan'];
                    $externalIds['isan'] = $line['Isan'];
                }

                $sourceMovie->setExternalIds($externalIds);

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

                // Public.
                if (isset($line['Parental rating']) && !empty($line['Parental rating'])) {
                    dump($line['Parental rating']);
                    switch ($line['Parental rating']) {
                        case 'tous publics':
                            // Tous publics.
                            $sourceMovie->setPublic('TP');
                            break;
                        case "déc. -10":
                            // - 10 ans.
                            $sourceMovie->setPublic('10');
                            break;
                        case 'déc. -12':
                        case 'int. -12':
                            // - 12 ans.
                            $sourceMovie->setPublic('12');
                            break;
                        case 'déc. -16':
                        case 'int. -16':
                            // - 16 ans.
                            $sourceMovie->setPublic('16');
                            break;
                        default:
                            break;
                    }
                }

                $synopsis = 'Default punchline 3';
                $sourceMovie->setSynopsis($synopsis);

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
                    $movie = $this->movieRepository->findByIds($ids, $sourceMovie->getCode());

                    if (is_null($movie)) {
                        $yearAfter = $productionYear + 1;
                        $codeAfter = $this->entityCodeService->computeCode($title . '__' . $yearAfter);

                        $movie = $this->movieRepository->findByIds($ids, $codeAfter);
                    }

                    if (is_null($movie)) {
                        $yearBefore = $productionYear - 1;
                        $codeBefore = $this->entityCodeService->computeCode($title . '__' . $yearBefore);

                        $movie = $this->movieRepository->findByIds($ids, $codeBefore);
                    }

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
