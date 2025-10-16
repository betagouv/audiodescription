<?php

namespace Drupal\audiodescription\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a contact block on search page.
 */
#[Block(
  id: "search_block",
  admin_label: new TranslatableMarkup("Block search sur toutes les pages"),
  category: new TranslatableMarkup("Audiodescription")
)]
class SearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private ConfigPagesLoaderServiceInterface $configPagesLoader,
    protected FormBuilderInterface $formBuilder,
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
      $container->get('config_pages.loader'),
      $container->get('form_builder')
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

    $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\SimpleMovieSearchForm', 'lg', "Rechercher un titre de film");

    return [
      '#theme' => 'search_block',
      '#title' => $searchCp->get('field_search_title')->value,
      '#form' => $form
    ];
  }

}
