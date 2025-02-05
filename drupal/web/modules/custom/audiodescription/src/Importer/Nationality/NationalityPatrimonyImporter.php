<?php

namespace Drupal\audiodescription\Importer\Nationality;

use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\audiodescription\EntityManager\NationalityManager;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import nationalities from patrimony.
 */
class NationalityPatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private NationalityManager $nationalityManager,
  ) {
  }

  /**
   * Import genres data from patrimony.
   */
  public function import(): void {
    // Import genres.
    $client = \Drupal::httpClient();

    // @Todo : set date dynamically.
    try {
      $response = $client->request('GET', 'https://patrimoine.corfm.at/nationalities?updatedAt%5Bafter%5D=2025-01-04', [
        'headers' => [
          'Accept' => 'application/ld+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $nationality) {
        $name = trim($nationality['name']);
        $this->nationalityManager->createOrUpdate($name);
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching nationalities');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
