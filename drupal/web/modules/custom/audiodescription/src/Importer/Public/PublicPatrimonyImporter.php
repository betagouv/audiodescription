<?php

namespace Drupal\audiodescription\Importer\Public;

use Drupal\audiodescription\EntityManager\PublicManager;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import genres from patrimony.
 */
class PublicPatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private PublicManager $publicManager,
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
      $response = $client->request('GET', 'https://patrimoine.corfm.at/public_restrictions?updatedAt%5Bafter%5D=2025-01-04', [
        'headers' => [
          'Accept' => 'application/ld+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $public) {
        $name = trim($public['name']);
        $code = trim($public['code']);

        if (!empty($code) && !empty($name)) {
          $this->publicManager->createOrUpdate($code, $name);
        }

      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching publics');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
