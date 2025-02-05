<?php

namespace Drupal\audiodescription\Importer\Partner;

use Drupal\audiodescription\EntityManager\PartnerManager;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import partner from patrimony.
 */
class PartnerPatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private PartnerManager $partnerManager,
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
      $response = $client->request('GET', 'https://patrimoine.corfm.at/partners?updatedAt%5Bafter%5D=2025-01-04', [
        'headers' => [
          'Accept' => 'application/ld+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $partner) {
        $name = trim($partner['name']);
        $this->partnerManager->createOrUpdate($name);
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching partners');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
