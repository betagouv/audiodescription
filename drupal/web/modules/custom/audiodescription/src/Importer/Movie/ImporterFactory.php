<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\audiodescription\Enum\ImportSourceType;
use Drupal\audiodescription\Importer\ImportException;
use Drupal\views\Plugin\views\field\Boolean;

/**
 * Factory class for creating movie importer instances.
 */
class ImporterFactory {
  use AutowireTrait;

  public function __construct(
    private CncCsvImporter $cncCsvImporter,
  ) {

  }

  /**
   * Creates an importer based on the specified import source type.
   *
   * @param \Drupal\audiodescription\Enum\ImportSourceType $importSourceType
   *   The type of import source to create the importer for.
   *
   * @return MovieImporterInterface
   *   An instance of a class that implements the MovieImporterInterface.
   */
  public function createImporter(ImportSourceType $importSourceType): MovieImporterInterface|Boolean {
    switch ($importSourceType) {
      case ImportSourceType::CNC_CSV:
        return $this->cncCsvImporter;

      default:
        throw new ImportException();
    }
  }

}
