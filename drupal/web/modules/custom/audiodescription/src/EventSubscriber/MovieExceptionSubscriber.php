<?php

namespace Drupal\audiodescription\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MovieExceptionSubscriber implements EventSubscriberInterface {

  protected $routeMatch;
  protected $renderer;
  protected $pageRenderer;

  public function __construct(RouteMatchInterface $route_match, RendererInterface $renderer) {
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->pageRenderer = \Drupal::service('main_content_renderer.html');
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::EXCEPTION => ['onException', 200],
    ];
  }

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
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $node = $node_storage->load($route_params['node']);
      }
    }

    if ($node && $node->getType() === 'movie' && !$node->isPublished()) {
      // Construire le contenu
      $build = [
        '#theme' => 'error_410',
        '#node' => $node,
        '#title' => $node->getTitle(),
        '#message' => t('Le film "@title" n\'est plus disponible dans notre catalogue.', [
          '@title' => $node->getTitle()
        ]),
      ];

      // Créer un render array complet de page
      $page = [
        '#type' => 'page',
        '#title' => t('Contenu indisponible'),
        'content' => [
          'system_main' => $build,
        ],
      ];

      // Utiliser le HtmlRenderer pour avoir le layout complet
      $response = $this->pageRenderer->renderResponse($page, $request, $this->routeMatch);
      $response->setStatusCode(410);

      $event->setResponse($response);
    }
  }
}
