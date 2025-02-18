<?php

namespace Drupal\audiodescription\Importer\Genre;

use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import genres from patrimony.
 */
class GenrePatrimonyImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private GenreManager $genreManager,
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
    $url = $patrimony->get('field_patrimony_url')->value;
    $token = $patrimony->get('field_patrimony_token')->value;

    try {
      $response = $client->request('GET', $url . '/api/v1/genres/main', [
        'headers' => [
          'Accept' => 'application/ld+json',
          'Authorization' => 'Bearer ' . $token,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $genre) {
        $name = trim($genre['name']);
        $this->genreManager->createOrUpdate($name);
      }
    } catch( RequestException $e) {
      $this->logger->info('Error fetching genres');
      dump($e->getMessage());
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
