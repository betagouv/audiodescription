<?php

namespace Drupal\audiodescription\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a breadcrumb builder for nodes (article and page).
 */
class NodeBreadcrumbBuilder implements BreadcrumbBuilderInterface {

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
    $node = $route_match->getParameter('node');
    return $node && ($node->getType() === 'article' || $node->getType() === 'page');
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
      $node = $route_match->getParameter('node');
      $breadcrumb->addLink(
        Link::createFromRoute($node->getTitle(), 'entity.node.canonical', ['node' => $node->nid->value])
      );
    }

    return $breadcrumb;
  }

}
