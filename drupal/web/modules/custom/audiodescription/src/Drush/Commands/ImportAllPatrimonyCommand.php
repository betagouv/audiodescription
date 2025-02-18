<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\audiodescription\Importer\Director\DirectorPatrimonyImporter;
use Drupal\audiodescription\Importer\Genre\GenrePatrimonyImporter;
use Drupal\audiodescription\Importer\Movie\MoviePatrimonyImporter;
use Drupal\audiodescription\Importer\Nationality\NationalityPatrimonyImporter;
use Drupal\audiodescription\Importer\Offer\OfferPatrimonyImporter;
use Drupal\audiodescription\Importer\Partner\PartnerPatrimonyImporter;
use Drupal\audiodescription\Importer\Public\PublicPatrimonyImporter;
use Drupal\search_api\Entity\Index;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ImportAllPatrimonyCommand extends DrushCommands {

  /**
   * Constructs an AudiodescriptionCommands object.
   */
  public function __construct(
    private readonly GenrePatrimonyImporter $genrePatrimonyImporter,
    private readonly PublicPatrimonyImporter $publicPatrimonyImporter,
    private readonly NationalityPatrimonyImporter $nationalityPatrimonyImporter,
    private readonly DirectorPatrimonyImporter $directorPatrimonyImporter,
    private readonly MoviePatrimonyImporter $moviePatrimonyImporter,
    private readonly PartnerPatrimonyImporter $partnerPatrimonyImporter,
    private readonly OfferPatrimonyImporter $offerPatrimonyImporter,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('audiodescription.genre_patrimony_importer'),
      $container->get('audiodescription.public_patrimony_importer'),
      $container->get('audiodescription.nationality_patrimony_importer'),
      $container->get('audiodescription.director_patrimony_importer'),
      $container->get('audiodescription.movie_patrimony_importer'),
      $container->get('audiodescription.partner_patrimony_importer'),
      $container->get('audiodescription.offer_patrimony_importer'),
    );
  }

  /**
   * Import genres from patrimony.
   */
  #[CLI\Command(name: 'ad:import:all', aliases: ['adia'])]
  #[CLI\Usage(name: 'ad:import:all', description: 'Import all from patrimony.')]
  public function import(): void {
    try {
      $this->genrePatrimonyImporter->import();
      $this->logger()->success('Import des genres terminé');

      //$this->publicPatrimonyImporter->import();
      //$this->logger()->success('Import des publics terminé');

      //$this->nationalityPatrimonyImporter->import();
      //$this->logger()->success('Import des nationalités terminé');

      //$this->directorPatrimonyImporter->import();
      //$this->logger()->success('Import des réalisateurs terminé');

      $this->partnerPatrimonyImporter->import();
      $this->logger()->success('Import des partenaires terminé');

      //$this->offerPatrimonyImporter->import();
      //$this->logger()->success('Import des offres terminé');

      //$this->moviePatrimonyImporter->import();
      //$this->logger()->success('Import des films terminé');

      $index = Index::load('movies');

      if ($index) {
        $this->output()->writeln('Indexation des films en cours...');
        $index->indexItems();
      } else {
        $this->output()->writeln('Index non trouvé.');
      }
    }
    catch (\Throwable $t) {
      $this->logger()->error('Erreur fatale : ' . $t->getMessage());
    };

    $this->logger()->success(dt('Achievement unlocked.'));
  }

}
