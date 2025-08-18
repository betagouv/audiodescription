<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Back Button' Block.
 */
#[Block(
  id: "back_button_block",
  admin_label: new TranslatableMarkup("Back Button Block"),
  category: new TranslatableMarkup("Audiodescription")
)]

class BackButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'back_button_block',
      '#attached' => [
        'library' => [
          'audiodescription/back_button_block',
        ],
      ],
    ];
  }

}
