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
  id: "hp_new_free_movies_block",
  admin_label: new TranslatableMarkup("Derniers films gratuits ajoutés"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HpNewFreeMoviesBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $title = $homepage->get('field_new_movies_title')->value;


    $moviesIds = $this->newMoviesWithFreeSolution();
    $movies = $this->entityTypeManager->getStorage('node')->loadMultiple($moviesIds);

    $moviesRendered = [];
    foreach ($movies as $movie) {
      $moviesRendered[] = $this->entityTypeManager
        ->getViewBuilder('node')
        ->view($movie, 'card');
    }

    return [
      '#theme' => 'hp_new_free_movies_block',
      '#title' => $title,
      '#movies' => $moviesRendered,
    ];
  }

  private function newMoviesWithFreeSolution():array {
    $connection = Database::getConnection();
    $limit = 4;

    $sql = <<<SQL
      WITH base AS (
          SELECT DISTINCT
              m.nid                                         AS movie_id,
              sr.field_pg_start_rights_value::date          AS start_rights,
              ord.field_taxo_order_value::int               AS partner_order,
              partner_term.name                             AS partner_name,
              lk.field_pg_link_uri                          AS link_uri
          FROM node_field_data              AS m
                   JOIN node__field_offers           AS nfo  ON nfo.entity_id = m.nid
                   JOIN paragraphs_item              AS p_offer ON p_offer.id = nfo.field_offers_target_id

              -- Offre → taxonomie offre
                   JOIN paragraph__field_pg_offer    AS pfo  ON pfo.entity_id = p_offer.id
                   JOIN taxonomy_term_field_data     AS offer_term ON offer_term.tid = pfo.field_pg_offer_target_id
                   JOIN taxonomy_term__field_taxo_code AS offer_code ON offer_code.entity_id = offer_term.tid

              -- Offre → partenaires
                   JOIN paragraph__field_pg_partners AS pfp  ON pfp.entity_id = p_offer.id
                   JOIN paragraphs_item              AS partner_paragraph
                        ON partner_paragraph.id = pfp.field_pg_partners_target_id

              -- Champs partenaires
                   LEFT JOIN paragraph__field_pg_start_rights AS sr ON sr.entity_id = partner_paragraph.id
                   LEFT JOIN paragraph__field_pg_link         AS lk ON lk.entity_id = partner_paragraph.id

              -- Partner → taxonomie (ordre)
                   LEFT JOIN paragraph__field_pg_partner      AS ptn_ref      ON ptn_ref.entity_id = partner_paragraph.id
                   LEFT JOIN taxonomy_term_field_data         AS partner_term ON partner_term.tid = ptn_ref.field_pg_partner_target_id
                   LEFT JOIN taxonomy_term__field_taxo_order  AS ord          ON ord.entity_id      = partner_term.tid

          WHERE m.type = 'movie'
            AND p_offer.type = 'pg_offer'
            AND partner_paragraph.type = 'pg_partner'
            AND offer_term.vid = 'offer'
            AND offer_code.field_taxo_code_value = 'FREE_ACCESS'
            AND lk.field_pg_link_uri IS NOT NULL
            AND lk.field_pg_link_uri <> ''
            AND sr.field_pg_start_rights_value IS NOT NULL
      ),
           ranked AS (
               SELECT
                   b.movie_id,
                   b.start_rights,
                   ROW_NUMBER() OVER (
                       PARTITION BY b.movie_id
                       ORDER BY
                           b.partner_order ASC NULLS LAST,
                           b.start_rights ASC NULLS LAST,
                           b.partner_name,
                           b.link_uri
                       ) AS rn
               FROM base b
           )
      SELECT movie_id
      FROM ranked
      WHERE rn = 1
      ORDER BY start_rights DESC, movie_id
      LIMIT :limit
      SQL;


    try {
      $result = $connection->query($sql, [
        ':limit' => $limit,
      ]);

      $ids = array_map('intval', $result->fetchCol());
    }
    catch (\Throwable $e) {
      // En prod, logguez l’erreur et retournez un message discret.
      \Drupal::logger('your_module')->error('HpNewFreeMoviesBlock SQL error: @msg', ['@msg' => $e->getMessage()]);
      return [
        '#markup' => $this->t('Unable to load data at the moment.'),
        '#cache' => [
          'max-age' => 60,
        ],
      ];
    }

    return $ids;
  }

}
