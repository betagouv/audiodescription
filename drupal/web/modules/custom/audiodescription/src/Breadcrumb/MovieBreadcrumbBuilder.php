<?php

namespace Drupal\audiodescription\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;

/**
 * Provides a breadcrumb builder for movie nodes.
 */
class MovieBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $node = $route_match->getParameter('node');
    return $node && $node->getType() === 'movie';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path', 'route']);

    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));

    $node = $route_match->getParameter('node');
    $genres = $node->get('field_genres')->referencedEntities();

    if (!empty($genres)) {
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

      $genre = $genres[0];

      $breadcrumb->addLink(
        Link::createFromRoute($genre->name->value, 'entity.taxonomy_term.canonical', ['taxonomy_term' => $genre->tid->value])
      );
    }

    $breadcrumb->addLink(
      Link::createFromRoute($node->getTitle(), 'entity.node.canonical', ['node' => $node->nid->value])
    );

    return $breadcrumb;
  }

}
