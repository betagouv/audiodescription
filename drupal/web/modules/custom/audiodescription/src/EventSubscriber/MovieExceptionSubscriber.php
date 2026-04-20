<?php

namespace Drupal\audiodescription\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Returns 410 responses for access denied exceptions on unpublished movies.
 */
class MovieExceptionSubscriber implements EventSubscriberInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The main content renderer service.
   *
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface
   */
  protected $pageRenderer;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(
    RouteMatchInterface $route_match,
    RendererInterface $renderer,
    MainContentRendererInterface $pageRenderer,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->pageRenderer = $pageRenderer;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::EXCEPTION => ['onException', 200],
    ];
  }

  /**
   * Redirects access denied responses for unpublished movies to a 410 page.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();

    if (!$exception instanceof AccessDeniedHttpException) {
      return;
    }

    $request = $event->getRequest();
    $node = $request->attributes->get('node');

    if (!$node) {
      $route_params = $request->attributes->get('_route_params');
      if (isset($route_params['node'])) {
        $node_storage = $this->entityTypeManager->getStorage('node');
        $node = $node_storage->load($route_params['node']);
      }
    }

    if ($node && $node->getType() === 'movie' && !$node->isPublished()) {
      // Construire le contenu.
      $build = [
        '#theme' => 'error_410',
        '#node' => $node,
        '#title' => $node->getTitle(),
        '#message' => t('Le film "@title" n\'est plus disponible dans notre catalogue.', [
          '@title' => $node->getTitle(),
        ]),
      ];

      // Créer un render array complet de page.
      $page = [
        '#type' => 'page',
        '#title' => t('Contenu indisponible'),
        'content' => [
          'system_main' => $build,
        ],
      ];

      // Utiliser le HtmlRenderer pour avoir le layout complet.
      $response = $this->pageRenderer->renderResponse($page, $request, $this->routeMatch);
      $response->setStatusCode(410);

      $event->setResponse($response);
    }
  }

}
