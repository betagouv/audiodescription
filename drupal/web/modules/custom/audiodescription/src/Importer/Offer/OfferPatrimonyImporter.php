<?php

namespace Drupal\audiodescription\Importer\Offer;

use Drupal\audiodescription\EntityManager\OfferManager;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import offer from patrimony.
 */
class OfferPatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private OfferManager $offerManager,
  ) {
  }

  /**
   * Import offers data from patrimony.
   */
  public function import(): void {
    // Import offers.

    // @Todo : set date dynamically.
    try {
      $offers = [
        'DVD_BLURAY' => 'En DVD et Blu-Ray',
        'LIBRARY' => 'En emprunt à la médiathèque',
        'STREAMING' => 'En streaming',
        'VOD' => 'En vidéo à la demande',
      ];

      // Output the result.
      foreach ($offers as $code => $name) {
        $this->offerManager->createOrUpdate($code, $name);
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching partners');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
