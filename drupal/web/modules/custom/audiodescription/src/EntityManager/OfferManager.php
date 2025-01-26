<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\taxonomy\Entity\Term;

/**
 * Class responsible for managing offer-related operations.
 */
class OfferManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Create or update partner taxonomy term.
   */
  public function createOrUpdate(
    string $offerCode,
    ?string $offerName = NULL
  ): ?Term {
    $properties = [
      'field_taxo_code' => $offerCode,
      'vid' => Taxonomy::OFFER->value,
    ];

    $offers = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);

    $offer = NULL;
    if (count($offers) !== 0) {
      $offer = array_shift($offers);
    }

    if (is_null($offer)) {
      $offer = Term::create($properties);
    }

    if (is_null($offerName)) $offerName = $offer->get('field_taxo_code')->value ?? $offerCode;
    $offer->setName($offerName);
    $offer->save();

    return $offer;
  }
}
