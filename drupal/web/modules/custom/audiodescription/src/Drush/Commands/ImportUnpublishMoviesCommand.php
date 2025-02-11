<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\search_api\Entity\Index;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ImportUnpublishMoviesCommand extends DrushCommands {

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
   * Unpublish movies.
   */
  #[CLI\Command(name: 'ad:unpublish-movies', aliases: ['adum'])]
  #[CLI\Usage(name: 'ad:unpublish-movies', description: 'Unpublish movies with no active solution.')]
  public function unpublishMovies(): void {

    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'movie')
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $nodes = $storage->loadMultiple($nids);

    $current_date = new DrupalDateTime();
    $current_timestamp = $current_date->getTimestamp();

    foreach ($nodes as $movie) {
      if ($this->hasActiveSolution($movie, $current_timestamp)) {
        if (!$movie->isPublished()) {
          $movie->setPublished(TRUE);

          $movie->save();
          $this->logger()->success("Movie '{$movie->label()}' (ID: {$movie->id()}) publié.");
        }
      }
      else {
        if ($movie->isPublished()) {
          $movie->set('status', 0);
          $movie->save();
          $this->logger()->warning("Movie '{$movie->label()}' (ID: {$movie->id()}) dépublié.");
        }
      }

      $index = Index::load('movies');

      if ($index) {
        $this->output()->writeln('Indexation des films en cours...');
        $index->indexItems();
      } else {
        $this->output()->writeln('Index non trouvé.');
      }
    }

    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Vérifie si un film possède au moins une solution active.
   *
   * @param \Drupal\node\Entity\Node $movie
   *   Le nœud de type Movie.
   * @param int $current_timestamp
   *   La date actuelle en timestamp.
   *
   * @return bool
   *   TRUE si une solution est active, FALSE sinon.
   */
  protected function hasActiveSolution(Node $movie, int $current_timestamp): bool {

    if (!$movie->hasField('field_offers')) {
      return FALSE;
    }

    foreach ($movie->get('field_offers')->referencedEntities() as $pg_offer) {

      if (!$pg_offer->hasField('field_pg_partners')) {
        continue;
      }

      foreach ($pg_offer->get('field_pg_partners')->referencedEntities() as $pg_partner) {
        $start_date = $pg_partner->get('field_pg_start_rights')->value;
        $end_date = $pg_partner->get('field_pg_end_rights')->value;

        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);

        if (($start_timestamp <= $current_timestamp) && is_null($end_date)) {
          return TRUE;
        }

        if (is_null($start_date) && ($end_timestamp > $current_timestamp)) {
          return TRUE;
        }

        if ($start_date && $end_date) {
          if ($start_timestamp <= $current_timestamp && $end_timestamp > $current_timestamp) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

}
