<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing partner-related operations.
 */
class PartnerManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Create or update partner taxonomy term.
   */
  public function createOrUpdate(
    string $partnerCode,
    ?string $partnerName = NULL
  ): ?Term {
    $properties = [
      'field_taxo_code' => $partnerCode,
      'vid' => Taxonomy::PARTNER->value,
    ];

    $partners = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);

    $partner = NULL;
    if (count($partners) !== 0) {
      $partner = array_shift($partners);
    }

    if (is_null($partner)) {
      $partner = Term::create($properties);
    }

    if (is_null($partnerName)) $partnerName = $partnerCode;
    $partner->setName($partnerName);
    $partner->save();

    return $partner;
  }
}
