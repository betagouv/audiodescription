<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\audiodescription\Enum\Taxonomy;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a collections block on homepage.
 */
#[Block(
  id: "hp_free_movies_block",
  admin_label: new TranslatableMarkup("Films audiodécrits disponibles gratuitement"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HpFreeMoviesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  private $configPagesLoader;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigPagesLoaderServiceInterface $configPagesLoader,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configPagesLoader = $configPagesLoader;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config_pages.loader'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the render array for the block.
   *
   * @return array
   *   A render array representing the block's content.
   */
  public function build() {
    $config_pages = $this->configPagesLoader;

    /** @var \Drupal\config_pages\Entity\ConfigPages $homepage */
    $homepage = $config_pages->load('homepage');

    $movies = $homepage->get('field_free_movies_movies')->referencedEntities();

    $moviesRendered = [];
    foreach ($movies as $movie) {
      $moviesRendered[] = $this->entityTypeManager
        ->getViewBuilder('node')
        ->view($movie, 'card');
    }

    $number = $this->countMoviesWithFreeSolution();

    return [
      '#theme' => 'hp_free_movies_block',
      '#title' => $homepage->get('field_free_movies_title')->value,
      '#number' => $number,
      '#movies' => $moviesRendered,
    ];
  }

  private function countMoviesWithFreeSolution():int {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'field_taxo_code' => 'FREE_ACCESS',
      'vid' => Taxonomy::OFFER->value,
    ]);

    $tid = array_shift($term)->id();

    $connection = Database::getConnection();

    $sql = "
    SELECT COUNT(m_sub.nid) as cnt
    FROM node_field_data m_sub
    WHERE m_sub.nid IN (
      SELECT DISTINCT m.nid AS nid
      FROM node_field_data m
      LEFT JOIN node__field_offers fo ON fo.entity_id = m.nid
      LEFT JOIN paragraphs_item offer_paragraph ON offer_paragraph.id = fo.field_offers_target_id
      LEFT JOIN paragraph__field_pg_offer taxo_ref ON taxo_ref.entity_id = offer_paragraph.id
      LEFT JOIN paragraph__field_pg_partners ps ON ps.entity_id = taxo_ref.field_pg_offer_target_id
      LEFT JOIN paragraphs_item s ON s.id = ps.field_pg_partners_target_id
      LEFT JOIN paragraph__field_pg_start_rights sr ON s.id = sr.entity_id
      LEFT JOIN paragraph__field_pg_end_rights er ON s.id = er.entity_id
      WHERE m.type = 'movie'
      AND taxo_ref.field_pg_offer_target_id = " . $tid ."
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
