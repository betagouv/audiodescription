<?php

namespace Drupal\audiodescription\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 *
 */
class DirectorManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   *
   */
  public function provide(string $directorName): ?Term {
    $directorCode = $this->computeCode($directorName);

    $directors = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => $directorCode,
      'vid' => Taxonomy::DIRECTOR->value,
    ]);

    $director = NULL;
    if (count($directors) !== 0) {
      $director = array_shift($directors);
    }

    if (is_null($director)) {
      $director = Term::create([
        'name' => $directorName,
        'field_taxo_code' => $directorCode,
        'vid' => Taxonomy::DIRECTOR->value,
      ]);

      $director->save();
    }

    return $director;
  }

  /**
   *
   */
  public function computeCode(string $name): string {
    $transliterator = \Transliterator::createFromRules(
      ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Upper(); :: NFC;',
      \Transliterator::FORWARD
    );
    $normalized = $transliterator->transliterate($name);

    return str_replace(' ', '_', $normalized);
  }

}
