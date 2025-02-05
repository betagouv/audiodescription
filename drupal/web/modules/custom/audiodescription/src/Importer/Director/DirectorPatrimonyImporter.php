<?php

namespace Drupal\audiodescription\Importer\Director;

use Drupal\audiodescription\EntityManager\DirectorManager;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import directors from patrimony.
 */
class DirectorPatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private DirectorManager $directorManager,
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
      $response = $client->request('GET', 'https://patrimoine.corfm.at/directors?updatedAt%5Bafter%5D=2025-01-04', [
        'headers' => [
          'Accept' => 'application/ld+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $director) {
        $name = trim($director['fullname']);
        $this->directorManager->createOrUpdate($name);
        $this->entityTypeManager->clearCachedDefinitions();
        dump(sprintf("Created / Updated %s director.", $name));
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching genres');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
