<?php

namespace Drupal\audiodescription\Importer\Movie;

use Drupal\audiodescription\Manager\DirectorManager;
use Drupal\audiodescription\Manager\GenreManager;
use Drupal\audiodescription\Manager\MovieManager;
use Drupal\audiodescription\Manager\NationalityManager;
use Drupal\audiodescription\Manager\PublicManager;
use Drupal\audiodescription\Parser\CsvParser;
use Drupal\Core\Entity\EntityTypeManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CncCsvImporter implements MovieImporterInterface, LoggerAwareInterface
{
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
    private readonly string $cncPublicsFile
  )
  {
  }

  public function import(): void
  {
    // Import publics.
    $lines = $this->csvParser->parseCSV($this->cncPublicsFile);

    foreach ($lines as $line) {
      $public = $this->publicManager->provide($line['code'], $line['name']);
    }

    // Import movies.
    $lines = $this->csvParser->parseCSV($this->cncMoviesFile);

    $i = 1;
    foreach ($lines as $line) {
      if (!is_null($line['TITRE']) && !empty($line['TITRE'])) {
        $data = [
          'title' => $line['TITRE'],
          'cnc_number' => $line['N°CNC'],
          'visa_number' => null,
          'has_ad' => false,
          'director' => null,
          'public' => null,
          'genre' => null,
          'nationality' => null
        ];

        $directorName = $line['Nom_Realisateur'];
        if (!empty($directorName)) {
          $director = $this->directorManager->provide($directorName);

          $data['director'] = $director;
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

        $nationalityName = $line['PAYS'];
        if (!empty($nationalityName)) {
          $nationality = $this->nationalityManager->provide($nationalityName);
          $data['nationality'] = $nationality;
        }

        // Complete data.
        if (!is_null($line['AudioDecrit']) && !empty($line['AudioDecrit'])) {
          if ($line['AudioDecrit'] == 'OUI') {
            $data['has_ad'] = true;
          }
        }

        if (!is_null($line['VISA']) && !empty($line['VISA'])) {
          $data['visa_number'] = $line['VISA'];
        }

        // Create or update movie.
        $movie = $this->movieManager->createOrUpdate($data);

        dump($i);
        $i++;

        /**if ($i > 100) {
        break;
        }**/

        // Clear entities cache.
        $this->entityTypeManager->clearCachedDefinitions();
      }
    }

  }
}
