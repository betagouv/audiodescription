<?php

declare(strict_types=1);

namespace Drupal\proconnect\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\core_event_dispatcher\FormHookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Alters the Drupal login forms for ProConnect.
 */
final class ProConnectFormAlterSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly TranslationInterface $stringTranslation,
    private readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FormHookEvents::FORM_ALTER => 'alterForm',
    ];
  }

  /**
   * Alters supported login forms to expose the ProConnect entry point.
   */
  public function alterForm(FormAlterEvent $event): void {
    switch ($event->getFormId()) {
      case 'user_login_form':
        $form = &$event->getForm();
        $this->alterUserLoginForm($form);
        break;

      case 'openid_connect_login_form':
        $form = &$event->getForm();
        $this->alterOpenIdConnectLoginForm($form);
        break;
    }
  }

  /**
   * Adds the ProConnect button to the Drupal login form.
   *
   * @param array<string, mixed> $form
   *   The login form.
   */
  private function alterUserLoginForm(array &$form): void {
    if (!$this->state->get('proconnect.show_proconnect_button', TRUE)) {
      return;
    }

    if (!isset($form['actions']) || !is_array($form['actions'])) {
      return;
    }

    $query = [];
    $destination = $this->requestStack->getCurrentRequest()?->query->get('destination');
    if (is_string($destination) && $destination !== '') {
      $query['destination'] = $destination;
    }

    $form['actions']['proconnect'] = [
      '#type' => 'link',
      '#title' => $this->stringTranslation->translate('Se connecter avec ProConnect'),
      '#url' => Url::fromRoute('proconnect.login', [], ['query' => $query]),
      '#attributes' => [
        'class' => [
          'button',
          'button--secondary',
          'button--proconnect',
        ],
      ],
      '#weight' => 50,
    ];
  }

  /**
   * Renames the OpenID Connect login action for ProConnect.
   *
   * @param array<string, mixed> $form
   *   The OpenID Connect login form.
   */
  private function alterOpenIdConnectLoginForm(array &$form): void {
    foreach (array_keys($form) as $key) {
      if (str_contains($key, 'openid_connect_client_proconnect_login')) {
        $form[$key]['#value'] = $this->stringTranslation->translate('Se connecter avec ProConnect');
      }
    }
  }

}
