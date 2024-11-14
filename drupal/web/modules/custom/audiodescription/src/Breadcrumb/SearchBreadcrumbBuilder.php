<?php

namespace Drupal\audiodescription\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a breadcrumb builder for page nodes.
 */
class SearchBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return ($route_match->getRouteName() === 'audiodescription.movie_search');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path', 'route']);

    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));

    $breadcrumb->addLink(
      Link::createFromRoute(
        'Résultats de recherche',
        $route_match->getRouteName()
      )
    );

    return $breadcrumb;
  }

}
