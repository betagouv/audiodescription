<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\audiodescription\Importer\Genre\GenrePatrimonyImporter;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ImportGenresPatrimonyCommand extends DrushCommands {

  /**
   * Constructs an AudiodescriptionCommands object.
   */
  public function __construct(
    private readonly GenrePatrimonyImporter $genrePatrimonyImporter,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('audiodescription.genre_patrimony_importer')
    );
  }

  /**
   * Import genres from patrimony.
   */
  #[CLI\Command(name: 'ad:import:genres', aliases: ['adig'])]
  #[CLI\Usage(name: 'ad:import:genres', description: 'Import genres from patrimony.')]
  public function import(): void {
    try {
      $this->genrePatrimonyImporter->import();
      $this->logger()->success('Import terminé');
    }
    catch (\Throwable $t) {
      $this->logger()->error('Erreur fatale : ' . $t->getMessage());
    };

    $this->logger()->success(dt('Achievement unlocked.'));
  }

}
