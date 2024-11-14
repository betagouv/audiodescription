<?php

namespace Drupal\audiodescription\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;

/**
 * Provides a breadcrumb builder for taxonomy terms genres.
 */
class GenreBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $taxonomy_term = $route_match->getParameter('taxonomy_term');
    return $taxonomy_term && $taxonomy_term->bundle() === 'genre';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path', 'route']);

    // Add the 'Home' link.
    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));

    $title = "";
    $view_id = "genres";
    $display_id = "page";
    $view = Views::getView($view_id);

    if ($view) {
      $view->execute();

      $title = $view->getTitle();
    }

    $breadcrumb->addLink(
      Link::createFromRoute(
        $title,
        'view.' . $view_id . '.' . $display_id,
      )
    );

    $taxonomy_term = $route_match->getParameter('taxonomy_term');
    $tid = $taxonomy_term->id();
    $breadcrumb->addLink(Link::createFromRoute($taxonomy_term->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]));

    return $breadcrumb;
  }

}
