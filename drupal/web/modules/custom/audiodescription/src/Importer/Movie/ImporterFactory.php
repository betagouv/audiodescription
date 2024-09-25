<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\audiodescription\Enum\ImportSourceType;

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
  public function createImporter(ImportSourceType $importSourceType): MovieImporterInterface {
    switch ($importSourceType) {
      case ImportSourceType::CNC_CSV:
        return $this->cncCsvImporter;
    }

    return FALSE;
  }

}
