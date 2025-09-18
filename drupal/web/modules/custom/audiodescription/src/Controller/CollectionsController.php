<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\block\Entity\Block;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the collection pages content.
 */
class CollectionsController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Homepage.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides the render array for the homepage.
   *
   * @return array
   *   A render array representing the content of the collections list page.
   */
  public function list() {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery()
      ->condition('field_taxo_is_highlighted', TRUE)
      ->condition('vid', 'collection')
      ->accessCheck(FALSE);

    $tidsCollection = $query->execute();

    $query = $term_storage->getQuery()
      ->condition('vid', 'genre')
      ->accessCheck(FALSE);

    $tidsGenre = $query->execute();

    $tids = array_merge($tidsCollection, $tidsGenre);

    $collections = [];

    foreach ($tids as $tid) {
      $term = Term::load($tid);
      $view_builder = $this->entityTypeManager->getViewBuilder('taxonomy_term');
      $render_array = $view_builder->view($term, 'tile');

      $collections[] = $render_array;
    }


    $build = [
      '#theme' => 'collections_list',
      '#collections' => $collections,
      '#cache' => [
        'tags' => ['taxonomy_term_list'],
      ],
    ];

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(['taxonomy_term_list']);
    $cache_metadata->applyTo($build);

    return $build;
  }
}
