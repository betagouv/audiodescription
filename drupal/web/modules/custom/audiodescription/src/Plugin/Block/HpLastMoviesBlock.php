<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
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
  id: "hp_last_movies_block",
  admin_label: new TranslatableMarkup("Derniers films sur la plateforme sur la page d'accueil"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HpLastMoviesBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    $cta = [];
    $entity = $homepage->get('field_last_movies_cta')->referencedEntities()[0];
    if (!is_null($entity)) {
      $cta = [
        'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
        'text' => $entity->get('field_pg_link')->first()->title,
        'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
        'external' => ($entity->get('field_pg_is_external')->value == TRUE),
        'style' => $entity->get('field_pg_style')->value,
      ];
    }

    $entityQuery = $this->entityTypeManager->getStorage('node')->getQuery();
    $query = $entityQuery
      ->condition('type', 'movie')
      ->condition('field_has_ad', TRUE)
      ->range(0, 3)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    $movies = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $moviesRendered = [];
    foreach($movies as $movie) {
      $moviesRendered[] = $this->entityTypeManager
        ->getViewBuilder('node')
        ->view($movie, 'card');
    }

    return [
      '#theme' => 'hp_last_movies_block',
      '#title' => $homepage->get('field_last_movies_title')->value,
      '#description' => $homepage->get('field_last_movies_description')->value,
      '#cta' => $cta,
      '#movies' => $moviesRendered,
    ];
  }

}
