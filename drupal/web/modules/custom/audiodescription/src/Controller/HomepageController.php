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
 * Controller for building the homepage content.
 */
class HomepageController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new Homepage.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigPagesLoaderServiceInterface $configPagesLoader, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configPagesLoader = $configPagesLoader;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config_pages.loader'),
      $container->get('form_builder'),
    );
  }

  /**
   * Provides the render array for the homepage.
   *
   * @return array
   *   A render array representing the content of the homepage.
   */
  public function build() {
    $config_pages = $this->configPagesLoader;
    $homepage = $config_pages->load('homepage');

    $entity = $homepage->get('field_header_cta')->referencedEntities()[0];
    $cta = [
      'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
      'text' => $entity->get('field_pg_link')->first()->title,
      'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
      'external' => ($entity->get('field_pg_is_external')->value == TRUE),
      'style' => $entity->get('field_pg_style')->value,
    ];

    $header = [
      'title' => $homepage->get('field_header_title')->value,
      'chapo' => $homepage->get('field_header_chapo')->value,
      'has_search_bar' => $homepage->get('field_header_with_search_bar')->value,
      'cta' => $cta,
      'image' => $homepage->get('field_header_image')->entity->field_media_image->entity->uri->value
    ];

    $ctas = [];
    $entities_pg_cta = $homepage->get('field_infos_ctas')->referencedEntities();

    foreach ($entities_pg_cta as $entity) {
      $ctas[] = [
        'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
        'text' => $entity->get('field_pg_link')->first()->title,
        'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
        'external' => ($entity->get('field_pg_is_external')->value == TRUE),
        'style' => $entity->get('field_pg_style')->value,
      ];
    }

    $infos = [
      'title' => $homepage->get('field_infos_title')->value,
      'description' => $homepage->get('field_infos_description')->value,
      'image' => $homepage->get('field_infos_image')->entity->field_media_image->entity->uri->value,
      'ctas' => $ctas,
    ];

    $about = [
      'title' => $homepage->get('field_about_title')->value,
      'description' => $homepage->get('field_about_description')->value,
      'icon' => $homepage->get('field_about_icon')->entity->field_media_image->entity->uri->value,
      'pre_contact' => $homepage->get('field_about_pre_contact')->value,
      'email' => $homepage->get('field_about_email')->value,
    ];

    $block = Block::load('ad_hp_highlighted_collections_block');

    $highlightedCollections = [];
    if ($block) {
      $highlightedCollections = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }

    $search_form = $this->formBuilder->getForm('Drupal\audiodescription\Form\SimpleMovieSearchForm', 'lg');

    $block = Block::load('ad_hp_new_free_movies_block');
    $newFreeMovies = [];
    if ($block) {
      $newFreeMovies = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }

    $block = Block::load('ad_hp_near_end_free_movies_block');
    $nearEndFreeMovies = [];
    if ($block) {
      $nearEndFreeMovies = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }


    $entity = $homepage->get('field_newsletter_cta')->referencedEntities()[0];
    $newsletter = [
      'title' => $homepage->get('field_newsletter_title')->value,
      'description' => $homepage->get('field_newsletter_description')->value,
      'cta' => [
        'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
        'text' => $entity->get('field_pg_link')->first()->title,
        'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
        'external' => ($entity->get('field_pg_is_external')->value == TRUE),
        'style' => $entity->get('field_pg_style')->value,
      ],
    ];

    $configPagesTag = $homepage->getCacheTagsToInvalidate()[0];

    $build = [
      '#theme' => 'homepage',
      '#header' => $header,
      '#infos' => $infos,
      '#about' => $about,
      '#highlighted_collections' => $highlightedCollections,
      //'#collections' => $collections,
      '#newsletter' => $newsletter,
      '#new_free_movies' => $newFreeMovies,
      '#near_end_free_movies' => $nearEndFreeMovies,
      '#search_form' => $search_form,
      '#cache' => [
        'tags' => ['node_list', 'taxonomy_term_list', $configPagesTag],
      ],
    ];

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(['node_list', 'taxonomy_term_list', $configPagesTag]);
    $cache_metadata->applyTo($build);

    return $build;
  }

  private function countMoviesWithAtLeastOneSolution():int {
    $connection = Database::getConnection();

    $sql = "
    SELECT COUNT(m_sub.nid) as cnt
    FROM node_field_data m_sub
    WHERE m_sub.nid IN (
      SELECT DISTINCT m.nid AS nid
      FROM node_field_data m
      LEFT JOIN paragraph__field_pg_offer po ON po.entity_id = m.nid
      LEFT JOIN paragraph__field_pg_partners ps ON ps.entity_id = po.field_pg_offer_target_id
      LEFT JOIN paragraphs_item s ON s.id = ps.field_pg_partners_target_id
      LEFT JOIN paragraph__field_pg_start_rights sr ON s.id = sr.entity_id
      LEFT JOIN paragraph__field_pg_end_rights er ON s.id = er.entity_id
      WHERE m.type = 'movie'
      AND m.status = 1
      AND (
        to_date(sr.field_pg_start_rights_value, 'YYYY-MM-DD') < NOW()
        OR sr.field_pg_start_rights_value IS NULL
        AND to_date(er.field_pg_end_rights_value, 'YYYY-MM-DD') > NOW()
        OR er.field_pg_end_rights_value IS NULL
      )
    )";

    $result = $connection->query($sql)->fetchField();

    return $result;
  }
}
