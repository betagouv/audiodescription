<?php

namespace Drupal\audiodescription\Importer\Movie;

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
 *
 */
class CncCsvImporter implements MovieImporterInterface, LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    private EntityTypeManager $entityTypeManager,
    private CsvParser $csvParser,
    private DirectorManager $directorManager,
    private GenreManager $genreManager,
    private MovieManager $movieManager,
    private NationalityManager $nationalityManager,
    private PublicManager $publicManager,
    private readonly string $cncMoviesFile,
    private readonly string $cncPublicsFile,
  ) {
  }

  /**
   *
   */
  public function import(): void {
    // @todo Move this code in PubicCsvImporter.php
    // Import publics.
    $lines = $this->csvParser->parseCSV($this->cncPublicsFile);

    foreach ($lines as $line) {
      // @todo create function "create" instead of use provide function.
      $this->publicManager->provide($line['code'], $line['name']);
    }

    // Import movies.
    $lines = $this->csvParser->parseCSV($this->cncMoviesFile);

    foreach ($lines as $line) {
      if (!is_null($line['TITRE']) && !empty($line['TITRE'])) {
        $data = [
          'title' => $line['TITRE'],
          'cnc_number' => $line['N°CNC'],
          'visa_number' => NULL,
          'has_ad' => FALSE,
          'directors' => NULL,
          'public' => NULL,
          'genre' => NULL,
          'nationalities' => NULL,
        ];

        $directors = json_decode($line['Nom_Realisateur'], TRUE);

        if (!empty($directors)) {
          $data['directors'] = [];

          foreach ($directors as $director) {
            $director = $this->directorManager->provide($director);

            $data['directors'][] = $director->tid->value;
          }
        }

        $publicCode = $line['INTERDICTION'];
        if (!empty($publicCode)) {
          $public = $this->publicManager->provide($publicCode);

          $data['public'] = $public;
        }

        $genreName = $line['GENRE'];
        if (!empty($genreName)) {
          $genre = $this->genreManager->provide($genreName);

          $data['genre'] = $genre;
        }

        $nationalities = json_decode($line['PAYS'], TRUE);

        if (!empty($nationalities)) {
          $data['nationalities'] = [];

          foreach ($nationalities as $nationality) {
            $nationality = $this->nationalityManager->provide($nationality);
            $data['nationalities'][] = $nationality->tid->value;
          }
        }

        // Complete data.
        if (!is_null($line['AudioDecrit']) && !empty($line['AudioDecrit'])) {
          if ($line['AudioDecrit'] == 'OUI') {
            $data['has_ad'] = TRUE;
          }
        }

        if (!is_null($line['VISA']) && !empty($line['VISA'])) {
          $data['visa_number'] = $line['VISA'];
        }

        // Create or update movie.
        $this->movieManager->createOrUpdate($data);

        /**if ($i > 100) {
         * break;
         * }**/

        // Clear entities cache.
        $this->entityTypeManager->clearCachedDefinitions();
      }
    }

  }

}
