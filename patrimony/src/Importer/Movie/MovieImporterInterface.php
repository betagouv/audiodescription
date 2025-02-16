<?php

namespace App\Importer\Movie;

/**
 * Interface for importing movies from various sources.
 */
interface MovieImporterInterface {

  /**
   * Imports movie data from a specified source.
   */
  public function import(?array $options = []);

}
