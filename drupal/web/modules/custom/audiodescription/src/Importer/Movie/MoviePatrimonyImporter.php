<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\audiodescription\EntityManager\DirectorManager;
use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\audiodescription\EntityManager\MovieManager;
use Drupal\audiodescription\EntityManager\NationalityManager;
use Drupal\audiodescription\EntityManager\OfferManager;
use Drupal\audiodescription\EntityManager\PartnerManager;
use Drupal\audiodescription\EntityManager\PublicManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import movies from patrimony.
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class MoviePatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  const ITEMS_PER_PAGE = 200;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private MovieManager $movieManager,
    private NationalityManager $nationalityManager,
    private GenreManager $genreManager,
    private DirectorManager $directorManager,
    private PublicManager $publicManager,
    private PartnerManager $partnerManager,
    private OfferManager $offerManager,
    private ConfigPagesLoaderServiceInterface $configPagesLoader,
    private ClientInterface $httpClient,
  ) {
  }

  /**
   * Import genres data from patrimony.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function import(): void {
    // Import movies.
    $client = $this->httpClient;

    $config_pages = $this->configPagesLoader;
    $patrimony = $config_pages->load('patrimony');
    $token = $patrimony->get('field_patrimony_token')->value;

    try {
      $next = 1;

      do {
        dump($next);

        $response = $client->request('GET', $this->buildUrl($patrimony, $next), [
          'headers' => [
            'Accept' => 'application/ld+json',
            'Authorization' => 'Bearer ' . $token,
          ],
        ]);

        $data = json_decode($response->getBody()->getContents(), TRUE);

        foreach ($data['hydra:member'] as $movie) {
          $title = $movie['title'];
          dump($title);

          $code = $movie['code'];
          $arteId = $movie['arteId'] ?? NULL;
          $canalVodId = $movie['canalVodId'] ?? NULL;
          $allocineId = $movie['allocineId'] ?? NULL;
          $orangeVodId = $movie['orangeVodId'] ?? NULL;
          $laCinetekId = $movie['laCinetekId'] ?? NULL;
          $franceTvId = $movie['franceTvId'] ?? NULL;

          $hasAd = $movie['hasAd'];
          $productionYear = $movie['productionYear'] ?? NULL;
          $synopsis = html_entity_decode(strip_tags($movie['synopsis'])) ?? NULL;
          // $nationalities = [];
          $genres = [];
          $directors = [];
          $solutions = [];
          $public = NULL;
          $poster = $movie['poster'] ?? NULL;

          if (!empty($movie['public']['code'])) {
            $public = $this->publicManager->createOrUpdate($movie['public']['code']);
          }

          /*
           * foreach ($movie['nationalities'] as $nationality) {
           *   $nationalities[] = $this->nationalityManager
           *     ->createOrUpdate($nationality['name']);
           * }
           */

          foreach ($movie['genres'] as $genre) {
            if (isset($genre['mainGenre'])) {
              if (is_array($genre['mainGenre'])) {
                $genres[] = $this->genreManager->createOrUpdate($genre['mainGenre']['name'], $genre['mainGenre']['code']);
              }
              else {
                if ($genre['@id'] == $genre['mainGenre']) {
                  $genres[] = $this->genreManager->createOrUpdate($genre['name'], $genre['code']);
                }
              }
            }
          }

          foreach ($movie['directors'] as $director) {
            $directors[] = $this->directorManager->createOrUpdate($director);
          }

          foreach ($movie['solutions'] as $solution) {
            $partner = $this->partnerManager->createOrUpdate(
              [
                'code' => $solution['partner']['code'],
              ]
            );

            $offerCode = NULL;
            switch ($solution['partner']['code']) {
              case 'ARTE':
              case 'FRANCE_TV':
              case 'TF1':
                $offerCode = 'FREE_ACCESS';
                break;

              case 'ORANGE_VOD':
              case 'LACINETEK_TVOD':
              case 'CANAL_VOD':
                $offerCode = 'TVOD';
                break;

              case 'LACINETEK_SVOD':
              case 'CANAL_REPLAY':
                $offerCode = 'SVOD';
                break;
            }

            $link = $solution['link'];
            $startRights = $solution['startRights'] ?? NULL;
            $endRights = $solution['endRights'] ?? NULL;

            $solutions[$offerCode][] = [
              'partner' => $partner,
              'link' => $link,
              'startRights' => $startRights,
              'endRights' => $endRights,
            ];
          }

          $this->movieManager->createOrUpdate(
            $title,
            $allocineId,
            $arteId,
            $canalVodId,
            $laCinetekId,
            $orangeVodId,
            $franceTvId,
            $code,
            $hasAd,
            $productionYear,
            $public,
            $genres,
            $directors,
            $solutions,
            $synopsis,
            $poster,
          );
        }

        $next++;
        if (!isset($data['hydra:view']['hydra:next'])) {
          $next = NULL;
        }

        // Clear entities cache.
        $this->entityTypeManager->clearCachedDefinitions();
        // } while (false);
      } while (!is_null($next));
    }
    catch (RequestException $e) {
      $this->logger->info('Error fetching movies');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

  /**
   * Builds the API URL for a given page.
   */
  private function buildUrl($configPage, $page) {
    $url = $configPage->get('field_patrimony_url')->value;

    $baseUrl = $url . '/api/v1/movies?';

    return sprintf(
      '%s&page=%s&itemsPerPage=%s',
      $baseUrl,
      $page,
      self::ITEMS_PER_PAGE
    );
  }

}
