<?php

namespace Drupal\audiodescription\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a breadcrumb builder for page nodes.
 */
class ViewBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Constructs a new ViewBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuLinkTreeInterface $menuLinkTree) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkTree = $menuLinkTree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $parameters_keys = $route_match->getParameters()->keys();
    return (in_array('view_id', $parameters_keys) && in_array('display_id', $parameters_keys));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path', 'route']);

    // Add the 'Home' link.
    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));

    $menu_link_tree = $this->menuLinkTree;
    $parameters = $menu_link_tree->getCurrentRouteMenuTreeParameters('main');
    $menu_link_tree->load('main', $parameters);

    foreach (array_reverse($parameters->activeTrail) as $trail) {
      // Remove menu_link_content: from uuid.
      $uuid = substr($trail, 18);

      $menu_link = $this->entityTypeManager
        ->getStorage('menu_link_content')
        ->loadByProperties(['uuid' => $uuid]);

      $menu_link_content = reset($menu_link);
      $menu_title = $menu_link_content->getTitle();
      $menu_url = $menu_link_content->getUrlObject();

      $breadcrumb->addLink(Link::fromTextAndUrl($menu_title, $menu_url));
    }

    if (count($parameters->activeTrail) == 0) {
      $title = "";
      $parameters = $route_match->getParameters()->all();
      $view_id = $parameters['view_id'];

      $view = Views::getView($view_id);

      $display_id = $parameters['display_id'];

      if ($view && $view->setDisplay($display_id)) {
        $view->execute();

        // Retourner le titre de la vue.
        $title = $view->getTitle();
      }

      $breadcrumb->addLink(
        Link::createFromRoute(
          $title,
          $route_match->getRouteName(),
          $route_match->getParameters()->all()
        )
      );
    }

    return $breadcrumb;
  }

}
