<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a contact block on search page.
 */
#[Block(
  id: "search_contact_block",
  admin_label: new TranslatableMarkup("Block contact sur les pages de recherche"),
  category: new TranslatableMarkup("Audiodescription")
)]
class SearchContactBlock extends BlockBase {

  /**
   * Builds the render array for the block.
   *
   * @return array
   *   A render array representing the block's content.
   */
  public function build() {
    $config_pages = \Drupal::service('config_pages.loader');
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
