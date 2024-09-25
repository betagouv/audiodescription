<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a contact block on movie page.
 */
#[Block(
  id: "movies_contact_block",
  admin_label: new TranslatableMarkup("Block contact sur les films"),
  category: new TranslatableMarkup("Audiodescription")
)]
class MoviesContactBlock extends BlockBase {

  /**
   * Builds the render array for the block.
   *
   * @return array
   *   A render array representing the block's content.
   */
  public function build() {
    $config_pages = \Drupal::service('config_pages.loader');
    $config = $config_pages->load('movies');

    $title = $config->get('field_block_contact_title')->value;
    $description = $config->get('field_block_contact_description')->value;
    $email = $config->get('field_block_contact_email')->value;

    return [
      '#theme' => 'movies_contact_block',
      '#title' => $title,
      '#description' => $description,
      '#email' => $email,
    ];
  }

}
