<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\audiodescription\EntityManager\DirectorManager;
use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\audiodescription\EntityManager\MovieManager;
use Drupal\audiodescription\EntityManager\NationalityManager;
use Drupal\audiodescription\EntityManager\PublicManager;
use Drupal\audiodescription\Parser\CsvParser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Imports movies from a CNC CSV file.
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
  ) {
  }

  /**
   * Imports movie data from a source.
   */
  public function import(): void {
    // Import movies.
    $lines = $this->csvParser->parseCsv($this->cncMoviesFile);

    foreach ($lines as $line) {
      if (!is_null($line['TITRE']) && !empty($line['TITRE'])) {
        $data = [
          'title' => trim($line['TITRE']),
          'cnc_number' => trim($line['N°CNC']),
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
            $director = $this->directorManager->createOrUpdate(trim($director));

            $data['directors'][] = $director->tid->value;
          }
        }

        $publicCode = $line['INTERDICTION'];
        if (!empty($publicCode)) {
          $public = $this->publicManager->createOrUpdate(trim($publicCode));

          $data['public'] = $public;
        }

        $genreName = $line['GENRE'];
        if (!empty($genreName)) {
          $genre = $this->genreManager->createOrUpdate(trim($genreName));

          $data['genre'] = $genre;
        }

        $nationalities = json_decode($line['PAYS'], TRUE);

        if (!empty($nationalities)) {
          $data['nationalities'] = [];

          foreach ($nationalities as $nationality) {
            $nationality = $this->nationalityManager->createOrUpdate(trim($nationality));
            $data['nationalities'][] = $nationality->tid->value;
          }
        }

        if (!is_null($line['AudioDecrit']) && !empty($line['AudioDecrit'])) {
          if (trim($line['AudioDecrit']) == 'OUI') {
            $data['has_ad'] = TRUE;
          }
        }

        if (!is_null($line['VISA']) && !empty($line['VISA'])) {
          $data['visa_number'] = trim($line['VISA']);
        }

        // Create or update movie.
        $this->movieManager->provide($data);

        // Clear entities cache.
        $this->entityTypeManager->clearCachedDefinitions();
      }
    }

  }

}
