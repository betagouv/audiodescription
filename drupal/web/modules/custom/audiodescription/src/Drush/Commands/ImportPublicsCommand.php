<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\audiodescription\Importer\Public\PublicCsvImporter;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ImportPublicsCommand extends DrushCommands {

  /**
   * Constructs an AudiodescriptionCommands object.
   */
  public function __construct(
    private readonly PublicCsvImporter $publicCsvImporter,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('audiodescription.public_csv_importer')
    );
  }

  /**
   * Import publics from CSV files.
   */
  #[CLI\Command(name: 'ad:import:publics', aliases: ['adip'])]
  #[CLI\Usage(name: 'ad:import:publics', description: 'Import publics from CSV files.')]
  public function import(): void {
    try {
      $this->publicCsvImporter->import();
      $this->logger()->success('Import terminé');
    }
    catch (\Throwable $t) {
      $this->logger()->error('Erreur fatale : ' . $t->getMessage());
    };

    $this->logger()->success(dt('Achievement unlocked.'));
  }

}
