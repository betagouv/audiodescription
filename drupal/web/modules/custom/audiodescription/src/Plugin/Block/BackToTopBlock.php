<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Back to top' Block.
 */
#[Block(
  id: "back_to_top_block",
  admin_label: new TranslatableMarkup("Back To Top Block"),
  category: new TranslatableMarkup("Audiodescription")
)]

class BackToTopBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'back_to_top_block',
      '#custom_data' => ['age' => '31', 'DOB' => '2 May 2000'],
      '#custom_string' => 'Hello Block!',
    ];
  }

}
