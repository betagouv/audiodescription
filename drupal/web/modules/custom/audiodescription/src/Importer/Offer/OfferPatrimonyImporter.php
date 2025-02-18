<?php

namespace Drupal\audiodescription\Importer\Offer;

use Drupal\audiodescription\EntityManager\OfferManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
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
    private ConfigPagesLoaderServiceInterface $configPagesLoader,
  ) {
  }

  /**
   * Import offers data from patrimony.
   */
  public function import(): void {
    // Import offers.
    $client = \Drupal::httpClient();

    $config_pages = $this->configPagesLoader;
    $patrimony = $config_pages->load('patrimony');
    $url = $patrimony->get('field_patrimony_url')->value;
    $token = $patrimony->get('field_patrimony_token')->value;

    try {
      $response = $client->request('GET', $url . '/api/v1/offers', [
        'headers' => [
          'Accept' => 'application/ld+json',
          'Authorization' => 'Bearer ' . $token,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $offer) {

        $data = [
          'name' => $offer['name'],
          'code' => $offer['code'],
          'order' => $offer['displayOrder']
        ];

        $this->offerManager->createOrUpdate($data);
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching partners');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
