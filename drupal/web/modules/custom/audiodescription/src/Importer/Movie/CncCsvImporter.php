<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\audiodescription\Importer\ImportException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CncCsvImporter implements MovieImporterInterface, LoggerAwareInterface
{
  use LoggerAwareTrait;

  public function __construct()
  {
  }

  public function import(): void
  {
    // TODO: Implement import() method.
    //$this->logger->info('START CNC CSV Import');

    throw new ImportException('CPT');

    /** TODO : FAIRE LE TAF */
  }
}
