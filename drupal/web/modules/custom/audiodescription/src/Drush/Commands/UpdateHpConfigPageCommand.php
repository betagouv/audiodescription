<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\audiodescription\Importer\Director\DirectorPatrimonyImporter;
use Drupal\audiodescription\Importer\Genre\GenrePatrimonyImporter;
use Drupal\audiodescription\Importer\Movie\MoviePatrimonyImporter;
use Drupal\audiodescription\Importer\Nationality\NationalityPatrimonyImporter;
use Drupal\audiodescription\Importer\Offer\OfferPatrimonyImporter;
use Drupal\audiodescription\Importer\Partner\PartnerPatrimonyImporter;
use Drupal\audiodescription\Importer\Public\PublicPatrimonyImporter;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Entity\Index;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class UpdateHpConfigPageCommand extends DrushCommands {

  /**
   * Constructs an AudiodescriptionCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Update config page "homepage".
   */
  #[CLI\Command(name: 'ad:update:hp', aliases: ['aduhp'])]
  #[CLI\Usage(name: 'ad:update:hp', description: 'Programatically update homepage.')]
  public function import(): void {

    $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => 'FREE_ACCESS',
      'vid' => Taxonomy::OFFER->value,
    ]);

    $tid = array_shift($term)->id();

    $connection = Database::getConnection();

    $sql = "
      SELECT
        m.nid
      FROM
        node_field_data m
      LEFT JOIN node__field_offers fo ON fo.entity_id = m.nid
      LEFT JOIN paragraphs_item offer_paragraph ON offer_paragraph.id = fo.field_offers_target_id
      LEFT JOIN paragraph__field_pg_offer taxo_ref ON taxo_ref.entity_id = offer_paragraph.id
      left join paragraph__field_pg_partners ps on ps.entity_id = taxo_ref.field_pg_offer_target_id
        left join paragraphs_item s on s.id = ps.field_pg_partners_target_id
        left join paragraph__field_pg_start_rights sr on s.id = sr.entity_id
        left join paragraph__field_pg_end_rights er on s.id = er.entity_id
      WHERE
        m.type = 'movie'
        and taxo_ref.field_pg_offer_target_id = " . $tid ."
        AND m.status = 1
      and (
            to_date(sr.field_pg_start_rights_value, 'YYYY-MM-DD') < NOW()
            or sr.field_pg_start_rights_value is null
            and to_date(er.field_pg_end_rights_value, 'YYYY-MM-DD') > NOW()
            or er.field_pg_end_rights_value is null
            )
      ORDER BY RAND()
      LIMIT 4;
    ";

    $results = $connection->query($sql)->fetchAll();

    $movie_ids = [];
    foreach($results as $result) {
      $movie_ids[] = $result->nid;
    }

    $storage = $this->entityTypeManager->getStorage('config_pages');
    $homepage = $storage->load('homepage');

    if (!$homepage) {
      $this->logger()->error('Impossible de charger la config page "homepage".');
      return;
    }

    $values = array_map(fn($nid) => ['target_id' => $nid], $movie_ids);
    $homepage->set('field_free_movies_movies', $values);

    try {
      $homepage->save();
      $this->logger()->success('Le champ field_free_movies_movies a été mis à jour avec succès.');
    }
    catch (EntityStorageException $e) {
      $this->logger()->error('Erreur lors de l\'enregistrement : ' . $e->getMessage());
    }
  }

}
