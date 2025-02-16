<?php

namespace Drupal\audiodescription\Importer\Public;

use Drupal\audiodescription\EntityManager\PublicManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
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
    private ConfigPagesLoaderServiceInterface $configPagesLoader
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
    $last_import_date = $patrimony->get('field_patrimony_last_import_date')->value;
    $url = $patrimony->get('field_patrimony_url')->value;
    $token = $patrimony->get('field_patrimony_token')->value;

    try {
      $response = $client->request('GET', $url . '/api/v1/public_restrictions?updatedAt%5Bafter%5D=' . $last_import_date, [
        'headers' => [
          'Accept' => 'application/ld+json',
          'Authorization' => 'Bearer ' . $token,
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
