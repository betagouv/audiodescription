<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\audiodescription\EntityManager\DirectorManager;
use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\audiodescription\EntityManager\MovieManager;
use Drupal\audiodescription\EntityManager\NationalityManager;
use Drupal\audiodescription\EntityManager\OfferManager;
use Drupal\audiodescription\EntityManager\PartnerManager;
use Drupal\audiodescription\EntityManager\PublicManager;
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
  // @Todo : set date dynamically.
  const BASE_URL = 'http://172.17.0.1:8083/movies?updatedAt[after]=2025-01-21';

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private MovieManager $movieManager,
    private NationalityManager $nationalityManager,
    private GenreManager $genreManager,
    private DirectorManager $directorManager,
    private PublicManager $publicManager,
    private PartnerManager $partnerManager,
    private OfferManager $offerManager,
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
          $cncId = $movie['cncId'] ?? null;
          $visa = $movie['visa'] ?? null;
          $arteId = $movie['arteId'] ?? null;
          $hasAd = $movie['hasAd'];
          $productionYear = $movie['productionYear'] > 1800 ? $movie['productionYear'] : null;
          $nationalities = [];
          $genres = [];
          $directors = [];
          $solutions = [];
          $public = null;

          if (!empty($movie['public']['code'])) {
            $public = $this->publicManager->createOrUpdate($movie['public']['code']);
          }

          foreach ($movie['nationalities'] as $nationality) {
            $nationalities[] = $this->nationalityManager->createOrUpdate($nationality['name']);
          }

          foreach ($movie['genres'] as $genre) {
            $genres[] = $this->genreManager->createOrUpdate($genre['name']);
          }

          foreach ($movie['directors'] as $director) {
            $directors[] = $this->directorManager->createOrUpdate($director['name']);
          }

          // @TODO : manage solutions
          foreach($movie['solutions'] as $solution) {

            $partner = $this->partnerManager->createOrUpdate(
              $solution['partner']['code'],
              $solution['partner']['name']
            );

            switch ($solution['partner']['code']) {
              case 'ARTE':
                $offerCode = 'STREAMING';
            }

            $link = $solution['link'];
            $startRights = $solution['startRights'];
            $endRights = $solution['endRights'];

            $solutions[$offerCode][] = [
              'partner' => $partner,
              'link' => $link,
              'startRights' => $startRights,
              'endRights' => $endRights
            ];
          }

          $this->movieManager->createOrUpdate(
            $title,
            $cncId,
            $visa,
            $arteId,
            $hasAd,
            $productionYear,
            $public,
            $genres,
            $nationalities,
            $directors,
            $solutions,
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
    return sprintf(
      '%s&page=%s&itemsPerPage=%s',
      self::BASE_URL,
      $page,
      self::ITEMS_PER_PAGE
    );
  }

}
