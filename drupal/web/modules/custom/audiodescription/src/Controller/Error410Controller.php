<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Error410Controller extends ControllerBase {

  protected $renderer;

  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  public function show410(Request $request) {
    // Récupérer le node depuis la session si disponible
    $session = $request->getSession();
    $node_data = $session->get('error_410_node_data');

    // Construire le rendu
    $build = [
      '#theme' => 'error_410',
      '#node' => NULL,
      '#title' => $node_data['title'] ?? t('Contenu indisponible'),
      '#message' => $node_data['message'] ?? t('Ce contenu n\'est plus disponible.'),
    ];

    // Nettoyer la session
    $session->remove('error_410_node_data');

    // Créer la réponse avec le code 410
    $response = new Response();
    $response->setStatusCode(410);

    return $build;
  }
}
