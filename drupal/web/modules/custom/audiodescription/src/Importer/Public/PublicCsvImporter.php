<?php

namespace Drupal\audiodescription\Importer\Public;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\audiodescription\Manager\DirectorManager;
use Drupal\audiodescription\Manager\GenreManager;
use Drupal\audiodescription\Manager\MovieManager;
use Drupal\audiodescription\Manager\NationalityManager;
use Drupal\audiodescription\Manager\PublicManager;
use Drupal\audiodescription\Parser\CsvParser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Import publics from a CSV file.
 */
class PublicCsvImporter implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private CsvParser $csvParser,
    private PublicManager $publicManager,
    private readonly string $cncPublicsFile,
  ) {
  }

  /**
   * Import publics data from a source.
   */
  public function import(): void
  {
    // Import publics.
    $lines = $this->csvParser->parseCsv($this->cncPublicsFile);

    foreach ($lines as $line) {
      $name = trim($line['name']);
      $code = trim($line['code']);
      $this->logger->info(sprintf('Import public with code %s and name %s', $code, $name));
      $this->publicManager->createOrUpdate($code, $name);
    }

    // Clear entities cache.
    $this->entityTypeManager->clearCachedDefinitions();
  }
}
