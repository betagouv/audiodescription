<?php

namespace Drupal\audiodescription\Importer\Partner;

use Drupal\audiodescription\EntityManager\PartnerManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
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
    private ConfigPagesLoaderServiceInterface $configPagesLoader,
  ) {
  }

  /**
   * Import genres data from patrimony.
   */
  public function import(): void {
    // Import genres.
    $client = \Drupal::httpClient();

    $config_pages = $this->configPagesLoader;
    $patrimony = $config_pages->load('patrimony');
    $url = $patrimony->get('field_patrimony_url')->value;
    $token = $patrimony->get('field_patrimony_token')->value;

    try {
      $response = $client->request('GET', $url . '/api/v1/partners', [
        'headers' => [
          'Accept' => 'application/ld+json',
          'Authorization' => 'Bearer ' . $token,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $partner) {
        $this->partnerManager->createOrUpdate($partner);
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching partners');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
