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
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Imports movies from France TV CSV File.
 */
class FranceTvCsvImporter implements MovieImporterInterface
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
            $this->parameterBag->get('france_tv.filename')
        );

        $lines = $this->csvParser->parseCsv($file, ';');

        // Create partner France TV if not exists.
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['code' => PartnerCode::FRANCE_TV->value]);

        if (is_null($partner)) {
            $partner = new Partner();
            $partner->setCode(PartnerCode::FRANCE_TV->value);
            $partner->setName(PartnerCode::FRANCE_TV->value);
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

        foreach ($lines as $line) {
          $internalPartnerId = $line['con_id'];

          $partner = $partnerRepository->findOneBy(['code' => PartnerCode::FRANCE_TV->value]);
          $offer = $offerRepository->findOneBy(['code' => OfferCode::STREAMING->value]);

          $repository = $this->entityManager->getRepository(SourceMovie::class);
          $sourceMovie = $repository->findOneBy([
            'internalPartnerId' => $internalPartnerId,
            'partner' => $partner,
          ]);

          $title = $line['titre'];
          dump($title);

          $productionYear = (int) $line['date_production'];

          if (is_null($sourceMovie)) {
            $sourceMovie = $this->sourceMovieManager->findOrcreate(
              $title,
              $internalPartnerId,
              $productionYear,
              $partner,
              TRUE
            );
          }

          if (!empty($line['directeur'])) {
            $directors = [
              [
                'fullname' => $line['directeur'],
              ]
            ];

            $sourceMovie->setDirectors($directors);
          }

          $casting = [];

          $actors = explode(', ', $line['csting']);
          foreach ($actors as $actor) {
            $casting[] = [
              'fullname' => $actor,
            ];
          }

          $sourceMovie->setCasting($casting);

          $this->entityManager->persist($sourceMovie);

          $startRights = DateTime::createFromFormat("d/m/y", $line['date_debut_droits']);
          $endRights = DateTime::createFromFormat("d/m/y", $line['date_fin_droits']);

          $solution = $this->solutionManager->createOrUpdate(
            $internalPartnerId,
            $partner,
            $sourceMovie,
            $offer,
            $line['content_url'],
            $startRights,
            $endRights,
          );

          $this->entityManager->persist($solution);

          $ids = [];
          $movie = null;
          if ($createMoviesOption) {
            $ids['franceTvId'] = $line['con_id'];
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
