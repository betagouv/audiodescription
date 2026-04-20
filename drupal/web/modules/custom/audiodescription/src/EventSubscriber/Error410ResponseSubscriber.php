<?php

namespace Drupal\audiodescription\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;

/**
 * Handles HTTP exceptions and generates proper 410 Gone responses.
 */
class Error410ResponseSubscriber implements EventSubscriberInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priorité basse pour intervenir après les autres.
    return [
      KernelEvents::EXCEPTION => ['on410', -50],
    ];
  }

  /**
   * Handles exception events and converts 410 exceptions to full responses.
   */
  public function on410(ExceptionEvent $event) {
    $exception = $event->getThrowable();

    // Vérifier que c'est bien une erreur 410.
    if (!$exception instanceof HttpException || $exception->getStatusCode() != 410) {
      return;
    }

    $request = $event->getRequest();
    $node = $request->attributes->get('error_410_node');

    // Construire le rendu avec votre theme error_410.
    $build = [
      '#theme' => 'error_410',
      '#node' => $node,
      '#title' => $node ? $node->getTitle() : t('Contenu indisponible'),
      '#message' => $node ?
      t('Le film "@title" n\'est plus disponible dans notre catalogue.', ['@title' => $node->getTitle()]) :
      t("Ce contenu n'est plus disponible."),
    ];

    // Créer le contenu de la page.
    $content = $this->renderer->renderRoot($build);

    // Créer une réponse HTML complète avec le layout.
    $response = new HtmlResponse($content, 410);

    // Définir la réponse.
    $event->setResponse($response);
  }

}
