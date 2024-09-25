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
  id: "hp_collections_block",
  admin_label: new TranslatableMarkup("Collections sur la page d'accueil"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HpCollectionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    $field_collections_with_genres = $homepage->get('field_collections_with_genres');

    $collection_genres = [
      'is_displayed' => FALSE,
      'data' => [],
    ];

    if ($field_collections_with_genres) {
      $collection_genres['is_displayed'] = TRUE;

      $view = View::load('genres');
      if ($view) {
        $view_display = $view->getDisplay('page');

        // Get view title.
        $title = $view_display['display_title'];
        $collection_genres['data']['title'] = $title;

        // Get view url.
        if (!empty($view_display['display_options']['path'])) {
          $path = $view_display['display_options']['path'];

          $url = Url::fromUserInput('/' . $path)->toString();
          $collection_genres['data']['url'] = $url;
        }
      }
    }

    $collections = [];
    $terms = $homepage->get('field_collections_collections')->referencedEntities();
    foreach ($terms as $term) {
      $view_builder = $this->entityTypeManager->getViewBuilder('taxonomy_term');
      $render_array = $view_builder->view($term, 'tile');

      $collections[] = $render_array;
    }

    return [
      '#theme' => 'hp_collections_block',
      '#title' => $homepage->get('field_collections_title')->value,
      '#collections' => $collections,
      '#collection_genres' => $collection_genres,
    ];
  }

}
