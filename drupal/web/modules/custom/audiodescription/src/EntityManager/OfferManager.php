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
    array $data
  ): ?Term {
    $code = $data['code'];

    $properties = [
      'field_taxo_code' => $code,
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

    $name = $data['name'] ?? null;
    if (is_null($name)) {
      $name = !empty($offer->getName()) ? $offer->getName() : $code;
    }
    $offer->setName($name);

    if (isset($data['order']) && !is_null($data['order'])) {
      $offer->set('field_taxo_order', $data['order']);
    }

    $offer->save();

    return $offer;
  }
}
