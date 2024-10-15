<?php

namespace Drupal\audiodescription\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing public-related operations.
 */
class PublicManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  public function createOrUpdate(string $publicCode, string $publicName): void {
    $properties = [
      'field_taxo_code' => $publicCode,
      'vid' => Taxonomy::PUBLIC->value,
    ];

    $publics = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);

    if (empty($publics)) {
      $public = Term::create($properties);
      $public->save();
      return;
    }

    $public = array_shift($publics);
    $public->setName($publicName);
    $public->save();
  }

  /**
   * Function to create public or update if it exists.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Public created or updated.
   */
  public function provide(string $publicCode, ?string $publicName = NULL): ?Term {

    $properties = [
      'field_taxo_code' => $publicCode,
      'vid' => Taxonomy::PUBLIC->value,
    ];

    if (isset($publicName) && !empty($publicName)) {
      $properties['name'] = $publicName;
    }

    $publics = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);

    $public = NULL;
    if (count($publics) !== 0) {
      $public = array_shift($publics);
    }

    if (is_null($public)) {
      $public = Term::create([
        'name' => $publicName,
        'field_taxo_code' => $publicCode,
        'vid' => Taxonomy::PUBLIC->value,
      ]);

      $public->save();
    }

    return $public;
  }

}
