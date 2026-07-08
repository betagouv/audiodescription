<?php

declare(strict_types=1);

namespace Drupal\proconnect\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\proconnect\Service\ProConnectAccessDeniedContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure les domaines email autorisés pour ProConnect.
 */
final class AccessDomainsForm extends FormBase implements ContainerInjectionInterface {

  public function __construct(
    private readonly StateInterface $state,
    protected ProConnectAccessDeniedContentManager $accessDeniedContentManager,
  ) {}

  /**
   * Creates the form instance.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('state'),
      $container->get('proconnect.access_denied_content_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'proconnect_access_domains';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $allowed_domains = proconnect_parse_domains($this->state->get('proconnect.allowed_domains', []));

    $content = $this->accessDeniedContentManager->getContent();

    $form['allowed_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Domaines d\'email autorisés'),
      '#default_value' => implode(PHP_EOL, $allowed_domains),
      '#description' => $this->t('Un domaine par ligne, par exemple <b>culture.gouv.fr</b>.'),
    ];

    $form['proconnect_access_denied'] = [
      '#type' => 'details',
      '#title' => $this->t('Message page accès refusé ProConnect'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['proconnect_access_denied']['title_proconnect_access_denied'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Titre'),
      '#default_value' => $content['title'] ?? '',
    ];

    $form['proconnect_access_denied']['message_proconnect_access_denied'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#format' => $content['message_format'] ?? 'full_html',
      '#default_value' => $content['message'] ?? '',
    ];

    $form['show_proconnect_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer le bouton de connexion ProConnect'),
      '#default_value' => $this->state->get('proconnect.show_proconnect_button', TRUE),
      '#description' => $this->t("Permet d'afficher ou masquer le bouton de connexion ProConnect sur le site."),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enregistrer'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $rawAllowedDomains = (string) $form_state->getValue('allowed_domains');
    $domains = preg_split('/\R+/', $rawAllowedDomains) ?: [];

    foreach ($domains as $domain) {
      $domain = mb_strtolower(trim($domain));

      if ($domain === '') {
        continue;
      }

      if (!proconnect_is_valid_domain($domain)) {
        $form_state->setErrorByName(
          'allowed_domains',
          $this->t('Domaine invalide : @domain', ['@domain' => $domain]),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $rawDomains = (string) $form_state->getValue('allowed_domains');

    $domains = preg_split('/\R+/', $rawDomains) ?: [];
    $domains = array_values(array_filter(array_map('trim', $domains)));

    $accessDenied = $form_state->getValue('proconnect_access_denied') ?? [];

    $message = $accessDenied['message_proconnect_access_denied'] ?? [
      'value' => '',
      'format' => 'full_html',
    ];

    $this->state->set('proconnect.allowed_domains', $domains);
    $this->state->set('proconnect.show_proconnect_button', (bool) $form_state->getValue('show_proconnect_button'));

    Cache::invalidateTags(['proconnect:show_proconnect_button']);

    $this->accessDeniedContentManager->saveContent(
      (string) ($accessDenied['title_proconnect_access_denied'] ?? ''),
      $message,
      0,
    );

    $this->messenger()->addStatus($this->t('La configuration a été enregistrée.'));
  }

}
