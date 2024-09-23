<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\audiodescription\Enum\ImportSourceType;

/**
 *
 */
class ImporterFactory {
  use AutowireTrait;

  public function __construct(
    private CncCsvImporter $cncCsvImporter,
  ) {

  }

  /**
   *
   */
  public function createImporter(ImportSourceType $importSourceType): MovieImporterInterface {
    switch ($importSourceType) {
      case ImportSourceType::CNC_CSV:
        return $this->cncCsvImporter;

      break;
    }

    return FALSE;
  }

}
