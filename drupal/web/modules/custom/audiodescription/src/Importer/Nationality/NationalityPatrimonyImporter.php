<?php

namespace Drupal\audiodescription\Importer\Nationality;

use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\audiodescription\EntityManager\NationalityManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
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
    $last_import_date = $patrimony->get('field_patrimony_last_import_date')->value;
    $url = $patrimony->get('field_patrimony_url')->value;
    $token = $patrimony->get('field_patrimony_token')->value;

    try {
      $response = $client->request('GET', $url . '/api/v1/nationalities?updatedAt%5Bafter%5D=' . $last_import_date, [
        'headers' => [
          'Accept' => 'application/ld+json',
          'Authorization' => 'Bearer ' . $token,
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
