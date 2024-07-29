<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\audiodescription\Enum\ImportSourceType;
use Drupal\audiodescription\Importer\ImportException;
use Drupal\audiodescription\Importer\Movie\ImporterFactory;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ImportMoviesCommand extends DrushCommands {

  /**
   * Constructs an AudiodescriptionCommands object.
   */
  public function __construct(
    private readonly ImporterFactory $importerFactory
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('audiodescription.importer_factory')
    );
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'ad:import', aliases: ['adim'])]
  #[CLI\Argument(name: 'sourceArg', description: 'Import source.')]
  #[CLI\Usage(name: 'ad:import', description: 'List implementations of hook_cron().')]
  public function import($sourceArg) {
    $source = ImportSourceType::from($sourceArg);
    $importer = $this->importerFactory->createImporter($source);

    try {
      $importer->import();
      $this->logger()->success('Import terminé');
    } catch (ImportException $e) {
        $this->logger()->error($e->getMessage());
    } catch (\Throwable $t) {
        $this->logger()->error('Erreur fatale : ' . $t->getMessage());
    };


    $this->logger()->success(dt('Achievement unlocked.'));
  }
}
