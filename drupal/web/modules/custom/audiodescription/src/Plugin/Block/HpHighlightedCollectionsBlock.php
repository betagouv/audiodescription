<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a highlight collections block on homepage.
 */
#[Block(
  id: "highlighted_collections_block",
  admin_label: new TranslatableMarkup("Collections mises en avant"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HpHighlightedCollectionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery()
      ->condition('field_taxo_is_highlighted', TRUE)
      ->accessCheck(FALSE);

    $tids = $query->execute();

    $collections = [];

    foreach ($tids as $tid) {
      $term = Term::load($tid);
      $view_builder = $this->entityTypeManager->getViewBuilder('taxonomy_term');
      $render_array = $view_builder->view($term, 'highlighted');

      $collections[] = $render_array;
    }

    return [
      '#theme' => 'hp_highlighted_collections_block',
      '#collections' => $collections,
    ];
  }

}
