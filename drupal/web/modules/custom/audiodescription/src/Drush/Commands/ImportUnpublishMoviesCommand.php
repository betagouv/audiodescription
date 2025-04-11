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
    // Delete pg_partners.
    $this->deleteObsoletePgPartners();

    // Unpublish movies.
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
    }

    $index = Index::load('movies');

    if ($index) {
      $this->output()->writeln('Indexation des films en cours...');
      $index->indexItems();
    } else {
      $this->output()->writeln('Index non trouvé.');
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

  private function deleteObsoletePgPartners() {
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $query = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'pg_partner');

    $now = date('Y-m-d');

    $group = $query->orConditionGroup()
      ->condition('field_pg_start_rights.value', $now, '>')
      ->condition('field_pg_end_rights.value', $now, '<');

    $query->condition($group);
    $ids = $query->execute();

    if (empty($ids)) {
      $this->logger()->notice('Aucun paragraphe pg_partner obsolète trouvé.');
    } else {
      $paragraphs = $paragraph_storage->loadMultiple($ids);
      $paragraph_storage->delete($paragraphs);

      $this->logger()->success(count($paragraphs) . ' paragraphes pg_partner supprimés.');
    }

    // On charge tous les paragraphes pg_offer.
    $query = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'pg_offer');

    $pg_offer_ids = $query->execute();

    if (empty($pg_offer_ids)) {
      $this->logger()->notice('Aucun paragraphe pg_offer trouvé.');
    } else {
      $pg_offers = $paragraph_storage->loadMultiple($pg_offer_ids);

      $ids_to_delete = [];

      foreach ($pg_offers as $pg_offer) {
        /** @var \Drupal\paragraphs\Entity\Paragraph $pg_offer */
        if (!$pg_offer->hasField('field_pg_partners')) {
          continue;
        }

        $valid = false;
        foreach ($pg_offer->get('field_pg_partners')->referencedEntities() as $partner) {
          // Si au moins un partenaire référencé existe, on garde le pg_offer.
          if ($partner && $partner->bundle() === 'pg_partner') {
            $valid = true;
            break;
          }
        }

        if (!$valid) {
          $ids_to_delete[] = $pg_offer->id();
        }
      }

      if (empty($ids_to_delete)) {
        $this->logger()->notice('Aucun paragraphe pg_offer avec référence cassée trouvé.');
        return;
      }

      $to_delete = $paragraph_storage->loadMultiple($ids_to_delete);
      $paragraph_storage->delete($to_delete);

      $this->logger()->success(count($to_delete) . ' paragraphes pg_offer supprimés car référence(s) vers pg_partner invalide(s).');
    }


  }
}
