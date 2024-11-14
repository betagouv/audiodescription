<?php

namespace Drupal\audiodescription\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;

/**
 * Provides a breadcrumb builder for taxonomy terms directors.
 */
class DirectorBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $taxonomy_term = $route_match->getParameter('taxonomy_term');
    return $taxonomy_term && $taxonomy_term->bundle() === 'director';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path', 'route']);

    // Add the 'Home' link.
    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));

    $taxonomy_term = $route_match->getParameter('taxonomy_term');
    $tid = $taxonomy_term->id();
    $breadcrumb->addLink(Link::createFromRoute($taxonomy_term->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]));

    return $breadcrumb;
  }

}
