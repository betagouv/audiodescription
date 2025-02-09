<?php

namespace Drupal\audiodescription\Importer\Director;

use Drupal\audiodescription\EntityManager\DirectorManager;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
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

    try {
      $response = $client->request('GET', $url . '/directors?updatedAt%5Bafter%5D=' . $last_import_date, [
        'headers' => [
          'Accept' => 'application/ld+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Output the result.
      foreach ($data['hydra:member'] as $director) {
        $name = trim($director['fullname']);
        $this->directorManager->createOrUpdate($director);
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
