<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a contact block on search page.
 */
#[Block(
  id: "search_contact_block",
  admin_label: new TranslatableMarkup("Block contact sur les pages de recherche"),
  category: new TranslatableMarkup("Audiodescription")
)]
class SearchContactBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  private $configPagesLoader;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigPagesLoaderServiceInterface $configPagesLoader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config_pages.loader')
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
    $config = $config_pages->load('wordings');

    $title = $config->get('field_search_bk_contact_title')->value;
    $description = $config->get('field_search_bk_contact_desc')->value;
    $cta_data = $config->get('field_search_bk_contact_cta')->referencedEntities()[0];

    $target = $cta_data->field_pg_is_external->value ? '_blank' : '_self';

    $cta = [
      'external' => $cta_data->field_pg_is_external->value,
      'target' => $target,
      'url' => $cta_data->field_pg_link[0]->uri,
      'text' => $cta_data->field_pg_link[0]->title,
      'style' => $cta_data->field_pg_style->value,
    ];

    return [
      '#theme' => 'search_contact_block',
      '#title' => $title,
      '#description' => $description,
      '#cta' => $cta,
    ];
  }

}
