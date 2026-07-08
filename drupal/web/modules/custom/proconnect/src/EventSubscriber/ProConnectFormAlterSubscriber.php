<?php

declare(strict_types=1);

namespace Drupal\proconnect\EventSubscriber;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\core_event_dispatcher\FormHookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters the Drupal login forms for ProConnect.
 */
final class ProConnectFormAlterSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly TranslationInterface $stringTranslation,
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
      case 'openid_connect_login_form':
        $form = &$event->getForm();
        $this->alterOpenIdConnectLoginForm($form);
        break;
    }
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
