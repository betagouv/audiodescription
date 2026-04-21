<?php

namespace Drupal\audiodescription;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides contrib services.
 */
class AudiodescriptionServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('elasticsearch_connector.query_filter_builder')) {
      $container->getDefinition('elasticsearch_connector.query_filter_builder')
        ->setClass('Drupal\audiodescription\SearchAPI\Query\FilterBuilder');
    }
  }

}
