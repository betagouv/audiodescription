<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\block\Entity\Block;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the POC pages content.
 */
class PocController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * Constructs a new PocController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigPagesLoaderServiceInterface $configPagesLoader) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config_pages.loader')
    );
  }

  /**
   * Provides the render array for the movie page (POC).
   *
   * @return array
   *   A render array representing the content of the movie page (POC).
   */
  public function movie() {
    $cnc_number = 2021001740;
    $node_storage = $this->entityTypeManager->getStorage('node');

    $nids = $node_storage->getQuery()
      ->condition('type', 'movie')
      ->condition('field_cnc_number', $cnc_number)
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();

    if (!empty($nids)) {
      $nid = reset($nids);
      $node = Node::load($nid);
    }
    else {
      return [
        '#markup' => $this->t('Aucun film trouvé pour le CNC Number : @cnc', ['@cnc' => $cnc_number]),
      ];
    }

    $config_pages = $this->configPagesLoader;
    $config = $config_pages->load('movies');

    $block = Block::load('ad_movies_contact_block');

    $blockContact = [];
    if ($block) {
      $blockContact = $this->entityTypeManager
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
