<?php

namespace Drupal\audiodescription\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 *
 */
class PublicManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   *
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
