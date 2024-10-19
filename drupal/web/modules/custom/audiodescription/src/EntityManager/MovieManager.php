<?php

namespace Drupal\audiodescription\EntityManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Class responsible for managing movie-related operations.
 */
class MovieManager {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {

  }

  /**
   * Function to create movie or update if it exists.
   *
   * @return \Drupal\node\Entity\Node
   *   Movie created or updated.
   */
  public function createOrUpdate(array $data): Node {
    $cncNumber = $data['cnc_number'];

    $movies = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'field_cnc_number' => $cncNumber,
      'type' => 'movie',
    ]);

    $movie = NULL;
    if (count($movies) !== 0) {
      $movie = array_shift($movies);

      if (!empty($data['title'])) {
        $movie->set('title', $data['title']);
      }

      $movie->set('field_has_ad', $data['has_ad']);

      if (!empty($data['visa_number'])) {
        $movie->set('field_visa_number', $data['visa_number']);
      }

      if (!empty($data['directors'])) {
        $directorsData = array_map(function ($director) {
          return ['target_id' => $director];
        }, $data['directors']);
        $movie->set('field_directors', $directorsData);
      }

      if (!empty($data['public'])) {
        $movie->set('field_public', ['target_id' => $data['public']->tid->value]);
      }

      if (!empty($data['genre'])) {
        $movie->set('field_genres', ['target_id' => $data['genre']->tid->value]);
      }

      if (!empty($data['nationalities'])) {
        $nationalityData = array_map(function ($nationality) {
          return ['target_id' => $nationality];
        }, $data['nationalities']);
        $movie->set('field_nationalities', $nationalityData);

      }

      $movie->save();
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

      if (!is_null($data['directors'])) {
        $properties['field_directors'] = $data['directors'];
      }

      if (!is_null($data['public'])) {
        $properties['field_public'] = ['target_id' => $data['public']->tid->value];
      }

      if (!is_null($data['genre'])) {
        $properties['field_genres'] = [
          ['target_id' => $data['genre']->tid->value],
        ];
      }

      if (!is_null($data['nationalities'])) {
        $properties['field_nationalities'] = $data['nationalities'];
      }

      $movie = Node::create($properties);
      $movie->save();

    }

    return $movie;
  }

}
