<?php

namespace Drupal\audiodescription\Manager;

use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

class MovieManager
{
  public function __construct(private EntityTypeManagerInterface $entityTypeManager)
  {

  }

  public function createOrUpdate(array $data): Node
  {
    $cncNumber = $data['cnc_number'];

    $movies = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'field_cnc_number' => $cncNumber,
      'type' => 'movie'
    ]);

    $movie = null;
    if (count($movies) !== 0) {
      // @TODO : Update.
      $movie = array_shift($movies);
    }

    if (is_null($movie)) {
      $properties = [
        'title' => $data['title'],
        'field_cnc_number' => $cncNumber,
        'field_has_ad' => $data['has_ad'],
        'type' => 'movie',
      ];

      if (!is_null($data['visa_number'])) {
        $properties['field_visa_number'] = $data['visa_number'];
      }

      if (!is_null($data['director'])) {
        $properties['field_directors'] = [
          ['target_id' => $data['director']->tid->value]
        ];
      }

      if (!is_null($data['public'])) {
        $properties['field_public'] = ['target_id' => $data['public']->tid->value];
      }

      if (!is_null($data['genre'])) {
        $properties['field_genres'] = [
          ['target_id' => $data['genre']->tid->value]
        ];
      }

      if (!is_null($data['nationality'])) {
        $properties['field_nationality'] = ['target_id' => $data['nationality']->tid->value];
      }

      $movie = Node::create($properties);

      $movie->save();
    }

    return $movie;
  }
}