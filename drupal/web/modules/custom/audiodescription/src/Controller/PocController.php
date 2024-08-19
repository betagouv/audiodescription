<?php

namespace Drupal\audiodescription\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class PocController extends ControllerBase
{
  public function movie() {
    $cnc_number = 2021001740;
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $nids = $node_storage->getQuery()
      ->condition('type', 'movie') // Filtrer pour le type de contenu 'movie'.
      ->condition('field_cnc_number', $cnc_number) // Filtrer par la valeur du field_cnc_number.
      ->accessCheck(TRUE)
      ->range(0, 1) // Limiter à un résultat.
      ->execute();

    if (!empty($nids)) {
      $nid = reset($nids);
      $node = Node::load($nid);
    } else {
      return [
        '#markup' => $this->t('Aucun film trouvé pour le CNC Number : @cnc', ['@cnc' => $cnc_number]),
      ];
    }

    $config_pages = \Drupal::service('config_pages.loader');
    $config = $config_pages->load('movies');

    $block = Block::load('ad_movies_contact_block');

    if ($block) {
      $blockContact = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);
    }

    $config = [
      'block_infos' => [
        'title' => $config->get('field_block_infos_title')->value,
      ],
      'block_ad' => [
        'title' => $config->get('field_block_ad_title')->value,
      ],
    ];


    return [
      '#theme' => 'poc_movie',
      '#node' => $node,
      '#config' => $config,
      '#block_contact' => $blockContact,
    ];
  }
}
