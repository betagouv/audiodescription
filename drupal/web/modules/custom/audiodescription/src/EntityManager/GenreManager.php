<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing genre-related operations.
 */
class GenreManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Function to create genre or update if it exists.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Genre created or updated.
   */
  public function provide(string $genreName): ?Term {
    $genreCode = $this->computeCode($genreName);

    $genres = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => $genreCode,
      'vid' => Taxonomy::GENRE->value,
    ]);

    $genre = NULL;
    if (count($genres) !== 0) {
      $genre = array_shift($genres);
    }

    if (is_null($genre)) {
      $genre = Term::create([
        'name' => $genreName,
        'field_taxo_code' => $genreCode,
        'vid' => Taxonomy::GENRE->value,
      ]);

      $genre->save();
    }

    return $genre;
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
