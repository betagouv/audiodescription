<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing director-related operations.
 */
class DirectorManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Function to create director or update if it exists.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Director created or updated.
   */
  public function createOrUpdate(array $data): ?Term {
    $directorName = trim($data['fullname']);
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
        'field_taxo_code' => $directorCode,
        'vid' => Taxonomy::DIRECTOR->value,
      ]);
    }

    $director->setName($directorName);
    if (isset($data['firstname']) && !is_null($data['firstname'])) {
      $director->set('field_taxo_firstname', $data['firstname']);
    }

    if (isset($data['lastname']) && !is_null($data['lastname'])) {
      $director->set('field_taxo_lastname', $data['lastname']);
    }

    $director->save();

    return $director;
  }

  /**
   * Function to generate code for director.
   *
   * @return string
   *   Generated code for director.
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
