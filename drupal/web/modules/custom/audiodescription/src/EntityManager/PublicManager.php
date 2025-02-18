<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing public-related operations.
 */
class PublicManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Create or update public taxonomy term.
   */
  public function createOrUpdate(string $publicCode, ?string $publicName = NULL): ?Term {
    $properties = [
      'field_taxo_code' => $publicCode,
      'vid' => Taxonomy::PUBLIC->value,
    ];

    $publics = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);

    $public = NULL;
    if (count($publics) !== 0) {
      $public = array_shift($publics);
    }

    if (is_null($public)) {
      $public = Term::create($properties);
      if (is_null($publicName)) $publicName = $publicCode;
      $public->setName($publicName);

      $public->save();
    }



    return $public;
  }
}
