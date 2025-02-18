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
  public function createOrUpdate(array $data): ?Term {
    $code = trim($data['code']);

    $properties = [
      'field_taxo_code' => $code,
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
      $properties['name'] = $data['name'] ?? $data['code'];
      $partner = Term::create($properties);
    }

    if (isset($data['name']) && !is_null($data['name'])) {
      $partner->setName($data['name']);
    }

    if (isset($data['pronunciation']) && !empty($data['pronunciation'])) {
      $partner->set('field_taxo_pronunciation', $data['pronunciation']);
    }

    if (isset($data['displayOrder']) && !empty($data['displayOrder'])) {
      $partner->set('field_taxo_order', $data['displayOrder']);
    }

    $partner->save();

    return $partner;
  }
}
