<?php

namespace App\Importer\Movie;

/**
 * Interface for importing movies from various sources.
 */
interface MovieImporterInterface
{
  /**
   * Imports movie data from a specified source.
   */
    /** @param array<string, mixed>|null $options */
    public function import(?array $options = []): void;
}
