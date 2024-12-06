<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a contact block on movie page.
 */
#[Block(
  id: "movies_contact_block",
  admin_label: new TranslatableMarkup("Block contact sur les films"),
  category: new TranslatableMarkup("Audiodescription")
)]
class MoviesContactBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $config = $config_pages->load('movies');

    $title = $config->get('field_block_contact_title')->value;
    $description = $config->get('field_block_contact_description')->value;
    $pre_contact = $config->get('field_block_contact_pre_contact')->value;
    $email = $config->get('field_block_contact_email')->value;
    $image = $config->get('field_block_contact_image')->entity->field_media_image->entity->uri->value;

    return [
      '#theme' => 'movies_contact_block',
      '#title' => $title,
      '#description' => $description,
      '#pre_contact' => $pre_contact,
      '#email' => $email,
      '#image' => $image,
    ];
  }

}
