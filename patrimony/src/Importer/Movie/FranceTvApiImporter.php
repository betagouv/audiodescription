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
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports movies from France TV CSV File.
 */
class FranceTvApiImporter implements MovieImporterInterface
{

  private string $dataDir;
  private EntityRepository $partnerRepository;
  private EntityRepository $offerRepository;

  public function __construct(
    private EntityManagerInterface $entityManager,
    private ParameterBagInterface $parameterBag,
    private HttpClientInterface $httpClient,
    private MovieRepository $movieRepository,
    private SourceMovieRepository $sourceMovieRepository,
    private SolutionRepository $solutionRepository,
    private SourceMovieManager $sourceMovieManager,
    private SolutionManager $solutionManager,
    private MovieManager $movieManager,
    private MovieFetcher $movieFetcher,
  )
  {
    $this->dataDir = sprintf('%s/data/', $this->parameterBag->get('kernel.project_dir'));
    $this->partnerRepository = $this->entityManager->getRepository(Partner::class);
    $this->offerRepository = $this->entityManager->getRepository(Offer::class);
  }

  /**
   * Imports movie data from a source.
   */
  public function import(?array $options = []): void
  {
    // Manage options.
    $createMoviesOption = $options['create-movies'] ?? false;

    // Create partner France TV if not exists.
    $partner = $this->partnerRepository->findOneBy(['code' => PartnerCode::FRANCE_TV->value]);

    if (is_null($partner)) {
      $partner = new Partner();
      $partner->setCode(PartnerCode::FRANCE_TV->value);
      $partner->setName(PartnerCode::FRANCE_TV->value);
      $this->entityManager->persist($partner);
    }

    // Create offer FREE_ACCESS if not exists.
    $offer = $this->offerRepository->findOneBy(['code' => OfferCode::FREE_ACCESS->value]);

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

    $url = $this->parameterBag->get('france_tv_100_num.api.url');
    $this->requestAndImport($url, $createMoviesOption);

    $url = $this->parameterBag->get('france_tv_premium.api.url');
    $this->requestAndImport($url, $createMoviesOption);
  }

  private function importProgram($program, $createMoviesOption) {
    $isTelefilm = 0;

    // Genres.
    $genres = [];
    foreach ($program['tags'] as $tag) {
      if ($tag['type'] == 'genre') {
        $genres[] = $tag['label'];
      }

      if ($tag['label'] == "téléfilms") {
        $isTelefilm = TRUE;
      }
    }

    $startRights = null;
    $endRights = null;

    if(!empty($program['platforms']['ftv']['exploitation_windows'])) {
      $startRights = $program['platforms']['ftv']['exploitation_windows'][0]['start'] ?? null;
      $endRights = $program['platforms']['ftv']['exploitation_windows'][0]['end']?? null;
    }

    if (!is_null($startRights) && !is_null($endRights)) {
      $dateStartRights = new DateTime($startRights);
      $dateEndRights = new DateTime($endRights);
      $today = new DateTime();

      if (
        !$isTelefilm
        && ($dateStartRights <= $today)
        && ($dateEndRights > $today)
        && (!empty($program['deep_links_v2'] || !empty($program['ftv_raw_url'])))
      ) {

        //@TODO : catch Throwable + Write a log + send Mattermost notif
        $partner = $this->partnerRepository->findOneBy(['code' => PartnerCode::FRANCE_TV->value]);
        $offer = $this->offerRepository->findOneBy(['code' => OfferCode::FREE_ACCESS->value]);

        $internalPartnerId = $program['program_id'];

        $repository = $this->entityManager->getRepository(SourceMovie::class);
        $sourceMovie = $repository->findOneBy([
          'internalPartnerId' => $internalPartnerId,
          'partner' => $partner,
        ]);

        $title = $program['title'];
        dump($title);

        $productionYear = (int) $program['produced_at'];

        if (is_null($sourceMovie)) {
          $sourceMovie = $this->sourceMovieManager->findOrcreate(
            $title,
            $internalPartnerId,
            $productionYear,
            $partner,
            TRUE
          );
        }

        $directors = [];
        $casting = [];
        foreach ($program['credits'] as $credit) {
          switch ($credit['role']['id']) {
            case 'realisateur':
              if (isset($credit['first_name']) && !is_null($credit['first_name'])) {
                $directors[] = [
                  'fullname' => $credit['first_name'] . ' ' . $credit['last_name'],
                ];
              } else {
                $directors[] = [
                  'fullname' => $credit['last_name'],
                ];
              }
              break;
            case 'acteur':
              if (isset($credit['first_name']) && !is_null($credit['first_name'])) {
                $casting[] = [
                  'fullname' => $credit['first_name'] . ' ' . $credit['last_name'],
                ];
              } else {
                $casting[] = [
                  'fullname' => $credit['last_name'],
                ];
              }
              break;
            default:
              break;
          }
        }

        $sourceMovie->setDirectors($directors);
        $sourceMovie->setCasting($casting);

        $synopsis = html_entity_decode(strip_tags($program['description']));
        $sourceMovie->setSynopsis($synopsis);

        // Public.
        if (isset($program['rating']) && !is_null($program['rating'])) {
          switch ($program['rating']) {
            case 'deconseille-aux-10-ans':
              // Non recommandé avant 10 ans.
              $sourceMovie->setPublic('10');
              break;
            case 'deconseille-aux-12-ans':
              // Non recommandé avant 12 ans.
              $sourceMovie->setPublic('12');
              break;
            case 'deconseille-aux-16-ans':
              // Non recommandé avant 16 ans.
              $sourceMovie->setPublic('16');
              break;
            case 'deconseille-aux-18-ans':
              // Non recommandé avant 18 ans.
              $sourceMovie->setPublic('18');
              break;
            default:
              break;
          }
        }

        if (isset($program['duration']) && !is_null($program['duration'])) {
          $duration = $program['duration'];
        } else {
          $duration = $program['expected_duration'];
        }
        $interval = new DateInterval($duration);
        $totalMinutes = ($interval->h * 60) + $interval->i + ($interval->s / 60);
        $sourceMovie->setDuration((int) $totalMinutes);

        $poster = '';
        foreach ($program['images'] as $image) {
          if ($image['ratio'] == '3:4') {
            $poster = $image['url'];
          }
        }
        $sourceMovie->setPoster($poster);

        $sourceMovie->setGenres($genres);

        $this->entityManager->persist($sourceMovie);

        // ExternalIds

        $ids = [];
        $ids['franceTvId'] = $internalPartnerId;

        if (isset($program['imdb']['episode_id']) && !is_null($program['imdb']['episode_id'])) {
          $ids['imdbId'] = $program['imdb']['episode_id'];
        }

        if (isset($program['plurimedia_broadcast_id']) && !is_null($program['plurimedia_broadcast_id'])) {
          $ids['plurimediaId'] = $program['plurimedia_broadcast_id'];
        }

        if (isset($program['allocine']['movie_id']) && !is_null($program['allocine']['movie_id'])) {
          $ids['allocineId'] = $program['allocine']['movie_id'];
        }

        $link = '';

        if (isset($program['ftv_raw_url']) && !empty($program['ftv_raw_url'])) {
          $link = $program['ftv_raw_url'];
        } else {
          foreach ($program['deep_links_v2'] as $deepLink) {
            if ($deepLink['type'] == 'Google') {
              foreach ($deepLink['targets'] as $target) {
                if ($target['offer'] == 'ftv') {
                  if (in_array('androidTV', $target['platforms'])) {
                    $link = $target['url'];
                  }
                }
              }
            }
          }
        }

        $solution = $this->solutionManager->createOrUpdate(
          $internalPartnerId,
          $partner,
          $sourceMovie,
          $offer,
          $link,
          $startRights,
          $endRights,
        );

        $this->entityManager->persist($solution);

        if ($createMoviesOption) {
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

  private function requestAndImport($url, $createMoviesOption) {
    $response = $this->httpClient->request('GET', $url);
    $programs = $response->toArray();
    foreach ($programs as $program) {
      $this->importProgram($program, $createMoviesOption);
    }
  }
}
