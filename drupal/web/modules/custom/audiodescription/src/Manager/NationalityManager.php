<?php

namespace Drupal\audiodescription\Manager;

use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Transliterator;

class NationalityManager
{
  public function __construct(private EntityTypeManagerInterface $entityTypeManager)
  {

  }

  public function provide(string $nationalityName): ?Term
  {
    $nationalityCode = $this->computeCode($nationalityName);

    $nationalities = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => $nationalityCode,
      'vid' => Taxonomy::NATIONALITY->value
    ]);

    $nationality = null;
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

  public function computeCode(string $genre): string {
    $transliterator = Transliterator::createFromRules(
      ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Upper(); :: NFC;',
      Transliterator::FORWARD
    );
    $normalized = $transliterator->transliterate($genre);

    return str_replace(' ', '_', $normalized);
  }
}
