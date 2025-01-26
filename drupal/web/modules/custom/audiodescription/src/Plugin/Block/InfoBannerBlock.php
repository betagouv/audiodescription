<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'InfoBanner' Block.
 */
#[Block(
  id: "info_banner_block",
  admin_label: new TranslatableMarkup("Information Banner Block"),
  category: new TranslatableMarkup("Audiodescription")
)]

class InfoBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function build() {
    $config_pages = $this->configPagesLoader;
    $config = $config_pages->load('wordings');

    if (is_null($config)) {
      return [];
    }

    $description = $config->get('field_info_banner_description')->value;
    $title = $config->get('field_info_banner_title')->value;

    return [
      '#theme' => 'info_banner_block',
      '#title' => $title,
      '#description' => $description,
    ];
  }

}
