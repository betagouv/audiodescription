<?php

namespace Drupal\audiodescription\Manager;

use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Transliterator;

class GenreManager
{
  public function __construct(private EntityTypeManagerInterface $entityTypeManager)
  {

  }

  public function provide(string $genreName): ?Term
  {
    $genreCode = $this->computeCode($genreName);

    $genres = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => $genreCode,
      'vid' => Taxonomy::GENRE->value
    ]);

    $genre = null;
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

  public function computeCode(string $genre): string {
    $transliterator = Transliterator::createFromRules(
      ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Upper(); :: NFC;',
      Transliterator::FORWARD
    );
    $normalized = $transliterator->transliterate($genre);

    return str_replace(' ', '_', $normalized);
  }
}
