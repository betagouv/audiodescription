<?php

namespace Drupal\audiodescription\EntityManager;

use DateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\ClientInterface;

/**
 * Class responsible for managing movie-related operations.
 */
class MovieManager {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private OfferManager $offerManager,
    private ClientInterface $httpClient,
    private FileRepositoryInterface $fileRepository,
  ) {

  }

  /**
   * Function to create movie or update if it exists.
   *
   * @return \Drupal\node\Entity\Node
   *   Movie created or updated.
   */
  public function provide(array $data): Node {
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

  public function createOrUpdate(
    string $title,
    ?string $allocineId,
    ?string $arteId,
    ?string $canalVodId,
    ?string $laCinetekId,
    ?string $orangeVodId,
    string $hasAd,
    ?string $productionYear,
    Term|null $public,
    array $genres,
    array $directors,
    array $solutions,
    ?string $synopsis,
    ?string $poster,
  ): Node {
    $movies = $this->findExistingMovies($allocineId, $arteId, $canalVodId);

    // BUG.
    if (count($movies) > 1) {
      foreach ($movies as $movie) {
        dump($movie->nid->value);
      }
      dump("----");
    }

    if (count($movies) == 0) {
      $movie = Node::create([
        'title' => $title,
        'type' => 'movie',
      ]);
    }

    if (count($movies) == 1) {
      $movie = array_shift($movies);
      $movie->set('title', $title);

      // Delete solutions before to import new ones.
      if ($movie->hasField('field_offers') && !$movie->get('field_offers')->isEmpty()) {
        $offers = $movie->get('field_offers')->referencedEntities();

        foreach ($offers as $offer) {
          if ($offer->hasField('field_pg_partners') && !$offer->get('field_pg_partners')->isEmpty()) {
            $partners = $offer->get('field_pg_partners')->referencedEntities();

            foreach ($partners as $partner) {
              $partner->delete();
            }
          }

          $offer->delete();
        }
      }
    }

    $movie->set('field_has_ad', $hasAd);
    $movie->set('field_production_year', $productionYear);

    if (!is_null($allocineId)) $movie->set('field_allocine_id', $allocineId);
    if (!is_null($arteId)) $movie->set('field_arte_id', $arteId);
    if (!is_null($canalVodId)) $movie->set('field_canal_vod_id', $canalVodId);
    if (!is_null($laCinetekId)) $movie->set('field_lacinetek_id', $laCinetekId);
    if (!is_null($orangeVodId)) $movie->set('field_orange_vod_id', $orangeVodId);

    if (!is_null($public)) {
      $movie->set('field_public', $public->tid->value);
    }

    if (!is_null($synopsis)) {
      $movie->set('field_synopsis', [
        'value' => $synopsis,
        'format' => 'basic_html',
      ]);
    }

    /**$ids = [];
    foreach ($nationalities as $nationality) {
      $ids[] = $nationality->tid->value;
    }
    $movie->set('field_nationalities', $ids);**/

    $ids = [];
    foreach ($genres as $genre) {
      $ids[] = $genre->tid->value;
    }
    $movie->set('field_genres', $ids);

    $ids = [];
    foreach ($directors as $director) {
      $ids[] = $director->tid->value;
    }
    $movie->set('field_directors', $ids);

    // Poster.
    if (!is_null($poster)) {
      $movie->set('field_poster_external', $poster);
    }

    $pg_offers = [];

    foreach($solutions as $offerCode => $data) {
      $pg_partners = [];
      foreach ($data as $solution)  {
        $start_rights = (new DateTime($solution['startRights']))->format('Y-m-d');
        $end_rights = (new DateTime($solution['endRights']))->format('Y-m-d');

        $pg_partner = Paragraph::create([
          'type' => 'pg_partner',
          'field_pg_link' => [
            'uri' => $solution['link'],
          ],
          'field_pg_partner' => [
            'target_id' => $solution['partner']->id(),
          ],
          'field_pg_start_rights' => $start_rights,
          'field_pg_end_rights' => $end_rights,
        ]);
        $pg_partner->save();

        $pg_partners[] = [
          'target_id' => $pg_partner->id(),
          'target_revision_id' => $pg_partner->getRevisionId(),
        ];
      }

      $offer = $this->offerManager->createOrUpdate($offerCode);

      $pg_offer = Paragraph::create([
        'type' => 'pg_offer',
        'field_pg_offer' => [
          'target_id' => $offer->tid->value,
        ],
        'field_pg_partners' => $pg_partners,
      ]);

      $pg_offer->save();

      $pg_offers[] = [
        'target_id' => $pg_offer->id(),
        'target_revision_id' => $pg_offer->getRevisionId(),
      ];
    }

    $movie->set('field_offers', $pg_offers);

    $movie->save();

    return $movie;
  }

  private function findExistingMovies($allocineId, $arteId, $canalVodId): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'movie')
      ->accessCheck(TRUE);

    $orGroup = $query->orConditionGroup()
      ->condition('field_allocine_id', $allocineId)
      ->condition('field_arte_id', $arteId)
      ->condition('field_canal_vod_id', $canalVodId);

    $query->condition($orGroup);

    // Exécute la requête pour récupérer les IDs des entités correspondantes.
    $nids = $query->execute();

    // Charge les entités correspondantes.
    $movies = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($nids);

    return $movies;
  }

}
