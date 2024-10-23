<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing actor-related operations.
 */
class ActorManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Function to create actor or update if it exists.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Actor created or updated.
   */
  public function provide(string $name, string $role): ?Term {
    $actorCode = $this->computeCode($name);

    $actors = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => $actorCode,
      'vid' => Taxonomy::ACTOR->value,
    ]);

    $actor = NULL;
    if (count($actors) !== 0) {
      $actor = array_shift($actors);
    }

    if (is_null($actor)) {
      $actor = Term::create([
        'name' => $name,
        'field_taxo_code' => $actorCode,
        'field_taxo_role' => $role,
        'vid' => Taxonomy::ACTOR->value,
      ]);

      $actor->save();
    }

    return $actor;
  }

  /**
   * Function to generate code for genre.
   *
   * @return string
   *   Generated code for genre.
   */
  public function computeCode(string $genre): string {
    $transliterator = \Transliterator::createFromRules(
      ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Upper(); :: NFC;',
      \Transliterator::FORWARD
    );
    $normalized = $transliterator->transliterate($genre);

    return str_replace(' ', '_', $normalized);
  }

}
