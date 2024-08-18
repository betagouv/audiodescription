<?php

namespace Drupal\audiodescription\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;

class AudiodescriptionController extends ControllerBase
{
  public function homepage() {
    $config_pages = \Drupal::service('config_pages.loader');
    $homepage = $config_pages->load('homepage');

    $header = [
      'title' => $homepage->get('field_header_title')->value,
      'chapo' => $homepage->get('field_header_chapo')->value,
    ];

    $ctas = [];
    $entities_pg_cta = $homepage->get('field_infos_ctas')->referencedEntities();

    foreach ($entities_pg_cta as $entity) {
      $ctas[] = [
        'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
        'text' => $entity->get('field_pg_link')->first()->title,
        'target' => ($entity->get('field_pg_is_external')->value == true) ? 'blank' : 'self',
        'external' => ($entity->get('field_pg_is_external')->value == true),
        'style' => $entity->get('field_pg_style')->value
      ];
    }

    $infos = [
      'title' => $homepage->get('field_infos_title')->value,
      'description' => $homepage->get('field_infos_description')->value,
      'ctas' => $ctas,
    ];

    $about = [
      'title' => $homepage->get('field_about_title')->value,
      'description' => $homepage->get('field_about_description')->value,
      'email' => $homepage->get('field_about_email')->value,
    ];

    $block = Block::load('ad_hp_highlighted_collections_block');

    if ($block) {
      $highlightedCollections = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);
    }

    $block = Block::load('ad_hp_collections_block');

    if ($block) {
      $collections = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);
    }

    return [
      '#theme' => 'homepage',
      '#header' => $header,
      '#infos' => $infos,
      '#about' => $about,
      '#highlighted_collections' => $highlightedCollections,
      '#collections' => $collections,
    ];
  }
}
