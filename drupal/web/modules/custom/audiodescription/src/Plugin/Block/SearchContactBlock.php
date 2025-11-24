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

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private ConfigPagesLoaderServiceInterface $configPagesLoader
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $searchCp = $config_pages->load('search');

    return [
      '#theme' => 'search_contact_block',
      '#title' => $searchCp->get('field_contact_title')->value,
      '#description' => $searchCp->get('field_contact_description')->value,
      '#email' => $searchCp->get('field_contact_email')->value,
      '#button_text' => $searchCp->get('field_contact_button_text')->value,
      '#subbutton_text' => $searchCp->get('field_contact_subbutton_text')->value,
    ];
  }

}
