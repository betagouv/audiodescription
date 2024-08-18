<?php

namespace Drupal\audiodescription\Plugin\Block;


use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\Entity\Term;

#[Block(
  id: "highlighted_collections_block",
  admin_label: new TranslatableMarkup("Collections mises en avant"),
  category: new TranslatableMarkup("Audiodescription")
)]
class HighlightedCollectionsBlock extends BlockBase
{

    public function build()
    {
      // TODO: Implement build() method.
      $t = null;
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $query = $term_storage->getQuery()
        ->condition('field_taxo_is_highlighted', true)
        ->accessCheck(FALSE);

      $tids = $query->execute();

      $collections = [];

      foreach ($tids as $tid) {
        $term = Term::load($tid);
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term');
        $render_array = $view_builder->view($term, 'highlighted');

        $collections[] = $render_array;
      }

      return [
        '#theme' => 'highlighted_collections_block',
        '#collections' => $collections
      ];
    }
}
