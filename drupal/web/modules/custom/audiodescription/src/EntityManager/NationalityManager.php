<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing nationality-related operations.
 */
class NationalityManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Function to create nationality or update if it exists.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Nationality created or updated.
   */
  public function provide(string $nationalityName): ?Term {
    $nationalityCode = $this->computeCode($nationalityName);

    $nationalities = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => $nationalityCode,
      'vid' => Taxonomy::NATIONALITY->value,
    ]);

    $nationality = NULL;
    if (count($nationalities) !== 0) {
      $nationality = array_shift($nationalities);
    }

    if (is_null($nationality)) {
      $nationality = Term::create([
        'name' => $nationalityName,
        'field_taxo_code' => $nationalityCode,
        'vid' => Taxonomy::NATIONALITY->value,
      ]);

      $nationality->save();
    }

    return $nationality;
  }

  /**
   * Function to generate code for nationality.
   *
   * @return string
   *   Generated code for nationality.
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
