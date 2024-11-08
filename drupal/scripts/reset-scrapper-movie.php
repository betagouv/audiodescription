<?php

use Drupal\node\Entity\Node;

// Charger tous les nœuds de type "movie".
$nids = \Drupal::entityQuery('node')
  ->condition('type', 'movie')
  ->condition('field_is_complete', 1)
  ->accessCheck(FALSE)
  ->execute();

// Charger chaque nœud et mettre à jour le champ `field_is_complete`.
foreach ($nids as $nid) {
  $node = Node::load($nid);
  if ($node->hasField('field_is_complete')) {
    // Mettre le champ à `false`.
    $node->set('field_is_complete', 0);
    $node->save();
    \Drupal::logger('custom_update')->info('Mise à jour du champ field_is_complete pour le nœud ID : ' . $nid);
    echo('Mise à jour du champ field_is_complete pour le nœud ID : ' . $nid . "\n");
  }
}

echo "Tous les champs field_is_complete des contenus Movie ont été mis à jour avec succès.\n";
