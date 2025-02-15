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
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import movies from patrimony.
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
    private ConfigPagesLoaderServiceInterface $configPagesLoader
  ) {
  }

  /**
   * Import genres data from patrimony.
   */
  public function import(): void {
    // Import movies.
    $client = \Drupal::httpClient();

    try {
      $next = 1;

      do {
        dump($next);
        $response = $client->request('GET', $this->buildUrl($next), [
          'headers' => [
            'Accept' => 'application/ld+json',
          ],
        ]);

        $data = json_decode($response->getBody()->getContents(), TRUE);

        foreach ($data['hydra:member'] as $movie) {
          $title = $movie['title'];

          dump($title);

          $code = $movie['code'];
          $arteId = $movie['arteId'] ?? null;
          $canalVodId = $movie['canalVodId'] ?? null;
          $allocineId = $movie['allocineId'] ?? null;
          $orangeVodId = $movie['orangeVodId'] ?? null;
          $laCinetekId = $movie['laCinetekId'] ?? null;

          $hasAd = $movie['hasAd'];
          $productionYear = $movie['productionYear'] ?? null;
          $synopsis = $movie['synopsis'] ?? null;
          //$nationalities = [];
          $genres = [];
          $directors = [];
          $solutions = [];
          $public = null;
          $poster = $movie['poster'] ?? null;

          if (!empty($movie['public']['code'])) {
            $public = $this->publicManager->createOrUpdate($movie['public']['code']);
          }

          /**foreach ($movie['nationalities'] as $nationality) {
            $nationalities[] = $this->nationalityManager->createOrUpdate($nationality['name']);
          }**/

          foreach ($movie['genres'] as $genre) {
            if (isset($genre['mainGenre'])) {
              if (is_array($genre['mainGenre'])) {
                $genres[] = $this->genreManager->createOrUpdate($genre['mainGenre']['name']);
              } else {
                if ($genre['@id'] == $genre['mainGenre']) {
                  $genres[] = $this->genreManager->createOrUpdate($genre['name']);
                }
              }
            }
          }

          foreach ($movie['directors'] as $director) {
            $directors[] = $this->directorManager->createOrUpdate($director);
          }

          foreach($movie['solutions'] as $solution) {
            $partner = $this->partnerManager->createOrUpdate(
              $solution['partner']['code']
            );

            switch ($solution['partner']['code']) {
              case 'ARTE':
              case 'LACINETEK':
                $offerCode = 'STREAMING';
                break;
              case 'CANAL_VOD':
              case 'ORANGE_VOD':
                $offerCode = 'TVOD';
                break;
            }

            $link = $solution['link'];
            $startRights = $solution['startRights'] ?? null;
            $endRights = $solution['endRights'] ?? null;

            $solutions[$offerCode][] = [
              'partner' => $partner,
              'link' => $link,
              'startRights' => $startRights,
              'endRights' => $endRights
            ];
          }

          $this->movieManager->createOrUpdate(
            $title,
            $allocineId,
            $arteId,
            $canalVodId,
            $laCinetekId,
            $orangeVodId,
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
          $next = null;
        }

        // Clear entities cache.
        $this->entityTypeManager->clearCachedDefinitions();
      //} while (false);
      } while (!is_null($next));
    } catch( RequestException $e) {
      $this->logger->info('Error fetching movies');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

  private function buildUrl($page) {
    $config_pages = $this->configPagesLoader;
    $patrimony = $config_pages->load('patrimony');
    $last_import_date = $patrimony->get('field_patrimony_last_import_date')->value;
    $url = $patrimony->get('field_patrimony_url')->value;

    $baseUrl = $url . '/movies?updatedAt[after]=' . $last_import_date;

    return sprintf(
      '%s&page=%s&itemsPerPage=%s',
      $baseUrl,
      $page,
      self::ITEMS_PER_PAGE
    );
  }

}
