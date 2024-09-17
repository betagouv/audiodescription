<?php

namespace Drupal\audiodescription\Plugin\Block;


use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Entity\View;
use Drupal\Core\Url;

#[Block(
  id: "hp_collections_block",
  admin_label: new TranslatableMarkup("Collections sur la page d'accueil"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HpCollectionsBlock extends BlockBase
{

    public function build()
    {
      $config_pages = \Drupal::service('config_pages.loader');
      $homepage = $config_pages->load('homepage');

      $field_collections_with_genres = $homepage->get('field_collections_with_genres');

      $collection_genres = [
        'is_displayed' => false,
        'data' => []
      ];

      if ($field_collections_with_genres) {
        $collection_genres['is_displayed'] = true;

        $view = View::load('genres');
        if ($view) {
          $view_display = $view->getDisplay('page');

          // Get view title.
          $title = $view_display['display_title'];

          // Get view url.
          if (!empty($view_display['display_options']['path'])) {
            $path = $view_display['display_options']['path'];

            $url = Url::fromUserInput('/' . $path)->toString();
          }
        }

        $collection_genres['data'] = [
          'title' => $title,
          'url' => $url,
        ];
      }

      $collections = [];
      $terms = $homepage->get('field_collections_collections')->referencedEntities();
      foreach ($terms as $term) {
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term');
        $render_array = $view_builder->view($term, 'tile');

        $collections[] = $render_array;
      }

      return [
        '#theme' => 'hp_collections_block',
        '#title' => $homepage->get('field_collections_title')->value,
        '#collections' => $collections,
        '#collection_genres' => $collection_genres,
      ];
    }
}
