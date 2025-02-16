<?php

namespace App\Importer\Public;

use App\EntityManager\PublicManager;
use App\Importer\ImportException;
use App\Parser\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
/**
 * Import publics from a CSV file.
 */
class PublicCsvImporter {

  public function __construct(
    private CsvParser $csvParser,
    private PublicManager $publicManager
  ) {
  }

  /**
   * Import publics data from a source.
   */
  public function import(?string $source, ?array $options = []): void {

      if (is_null($source)) {
          throw new ImportException('Missing CNC CSV filename.');
      }
      // Import movies.
      $lines = $this->csvParser->parseCsv($source, ',');

    foreach ($lines as $line) {
      $name = trim($line['name']);
      $code = trim($line['code']);

        if (!empty($code) && !empty($name)) {
            $this->publicManager->createOrUpdate($code, $name);
        }

    }
  }
}
