<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\audiodescription\Enum\ImportSourceType;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImporterFactory
{
  use AutowireTrait;

  public function __construct(
    private CncCsvImporter $cncCsvImporter
  )
  {

  }

  public function createImporter(ImportSourceType $importSourceType): MovieImporterInterface {
    switch ($importSourceType) {
      case ImportSourceType::CNC_CSV:
        return $this->cncCsvImporter;
        break;
    }

    return false;
  }

}
