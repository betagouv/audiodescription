<?php

declare(strict_types=1);

namespace Drupal\proconnect\Plugin\OpenIDConnectClient;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Url;
use Drupal\openid_connect\OpenIDConnectAutoDiscover;
use Drupal\openid_connect\OpenIDConnectStateTokenInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClient\OpenIDConnectGenericClient;
use Drupal\proconnect\Service\ProConnectClient as ProConnectHttpClient;
use Drupal\proconnect\Service\ProConnectStateManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * ProConnect OpenID Connect client.
 *
 * @OpenIDConnectClient(
 *   id = "proconnect",
 *   label = @Translation("ProConnect")
 * )
 */
final class ProConnect extends OpenIDConnectGenericClient {

  /**
   * Constructs the ProConnect plugin.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Component\Datetime\TimeInterface $datetime_time
   *   The time service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The page cache kill switch.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\openid_connect\OpenIDConnectStateTokenInterface $state_token
   *   The OpenID Connect state token service.
   * @param \Drupal\openid_connect\OpenIDConnectAutoDiscover $auto_discover
   *   The OpenID Connect autodiscover service.
   * @param \Drupal\proconnect\Service\ProConnectClient $proConnectClient
   *   The ProConnect HTTP client.
   * @param \Drupal\proconnect\Service\ProConnectStateManager $stateManager
   *   The ProConnect state manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    RequestStack $request_stack,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    TimeInterface $datetime_time,
    KillSwitch $page_cache_kill_switch,
    LanguageManagerInterface $language_manager,
    OpenIDConnectStateTokenInterface $state_token,
    OpenIDConnectAutoDiscover $auto_discover,
    private readonly ProConnectHttpClient $proConnectClient,
    private readonly ProConnectStateManager $stateManager,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $request_stack,
      $http_client,
      $logger_factory,
      $datetime_time,
      $page_cache_kill_switch,
      $language_manager,
      $state_token,
      $auto_discover,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('datetime.time'),
      $container->get('page_cache_kill_switch'),
      $container->get('language_manager'),
      $container->get('openid_connect.state_token'),
      $container->get('openid_connect.autodiscover'),
      $container->get('proconnect.client'),
      $container->get('proconnect.state_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    unset($configuration['iss_allowed_domains']);

    $configuration['prompt'] = ['login' => 'login'];
    $configuration['scopes'] = [
      'openid',
      'email',
      'given_name',
      'usual_name',
      'uid',
      'idp_id',
      'phone',
      'custom',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $configuration = $this->proConnectClient->getClientConfiguration($this->configuration);

    $form['callback_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Callback URL'),
      '#markup' => $this->getRedirectUrl()->toString(),
    ];
    $form['effective_domain'] = [
      '#type' => 'item',
      '#title' => $this->t('Configured ProConnect domain'),
      '#markup' => $configuration['domain'],
    ];
    $form['effective_algorithm'] = [
      '#type' => 'item',
      '#title' => $this->t('Configured signature algorithm'),
      '#markup' => $configuration['algorithm'],
    ];
    $form['scopes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scopes'),
      '#description' => $this->t('Scopes separated by spaces.'),
      '#default_value' => implode(' ', $this->configuration['scopes'] ?? []),
    ];
    $form['prompt'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Prompt'),
      '#options' => [
        'none' => $this->t('None'),
        'login' => $this->t('Login'),
        'consent' => $this->t('Consent'),
        'select_account' => $this->t('Select account'),
      ],
      '#default_value' => array_filter(array_values($this->configuration['prompt'] ?? [])),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $configuration = $form_state->getValues();

    if (isset($configuration['prompt']) && is_array($configuration['prompt'])) {
      $promptValues = array_filter($configuration['prompt']);
      if (isset($promptValues['none']) && count($promptValues) > 1) {
        $form_state->setErrorByName('prompt', $this->t('The "None" option cannot be selected with other values.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $configuration = $form_state->getValues();

    $this->unsetConfigurationKeys(['iss_allowed_domains']);
    $this->setConfiguration([
      'issuer_url' => trim((string) ($configuration['issuer_url'] ?? '')),
      'issuer' => trim((string) ($configuration['issuer'] ?? '')),
      'jwks_uri' => trim((string) ($configuration['jwks_uri'] ?? '')),
      'scopes' => array_values(array_filter(explode(' ', trim((string) ($configuration['scopes'] ?? ''))))),
      'prompt' => array_filter($configuration['prompt'] ?? []),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientScopes(): ?array {
    $scopes = $this->configuration['scopes'] ?? [];

    return is_array($scopes) ? $scopes : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(): array {
    $endpoints = $this->proConnectClient->getEndpoints($this->configuration);

    return [
      'authorization' => $endpoints['authorization'],
      'token' => $endpoints['token'],
      'userinfo' => $endpoints['userinfo'],
      'end_session' => $endpoints['end_session'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    $this->proConnectClient->assertUsableConfiguration($this->configuration);

    return parent::authorize($scope, $additional_params);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveTokens(string $authorization_code): ?array {
    try {
      $issuer = $this->requestStack->getCurrentRequest()?->query->get('iss');

      $this->proConnectClient->validateAuthorizationResponseIssuer(
        is_string($issuer) ? $issuer : NULL,
        $this->configuration,
      );

      $tokens = $this->proConnectClient->exchangeCode(
        $authorization_code,
        $this->getRedirectUrl()->toString(),
        $this->configuration,
      );

      $expectedNonce = $this->stateManager->consumeNonce();
      if ($expectedNonce === NULL) {
        throw new \RuntimeException('The ProConnect nonce is missing from the session.');
      }

      $this->proConnectClient->validateIdToken(
        $tokens['id_token'],
        $expectedNonce,
        $this->configuration,
      );

      $result = [
        'id_token' => $tokens['id_token'],
        'access_token' => $tokens['access_token'],
      ];

      if (isset($tokens['expires_in']) && is_numeric($tokens['expires_in'])) {
        $result['expire'] = $this->dateTime->getRequestTime() + (int) $tokens['expires_in'];
      }

      if (
        isset($tokens['refresh_token']) &&
        is_string($tokens['refresh_token']) &&
        $tokens['refresh_token'] !== ''
      ) {
        $result['refresh_token'] = $tokens['refresh_token'];
      }

      return $result;
    }
    catch (\InvalidArgumentException | \RuntimeException $exception) {
      $this->loggerFactory
        ->get('openid_connect_' . $this->pluginId)
        ->error(
          'Could not retrieve ProConnect tokens. Details: @error_message',
          ['@error_message' => $exception->getMessage()]
        );

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo(string $access_token): ?array {
    try {
      return $this->proConnectClient->fetchUserInfo($access_token, $this->configuration);
    }
    catch (\InvalidArgumentException | \RuntimeException $exception) {
      $this->loggerFactory
        ->get('openid_connect_' . $this->pluginId)
        ->error(
          'Could not retrieve ProConnect user profile information. Details: @error_message',
          ['@error_message' => $exception->getMessage()]
        );

      return NULL;
    }
  }

  /**
   * Builds URL options for the authorization request.
   *
   * @param string $scope
   *   The OAuth scopes.
   * @param \Drupal\Core\GeneratedUrl $redirect_uri
   *   The generated redirect URI.
   *
   * @return array<string, mixed>
   *   The URL options.
   */
  protected function getUrlOptions(string $scope, GeneratedUrl $redirect_uri): array {
    $configuration = $this->proConnectClient->getClientConfiguration($this->configuration);

    $query = [
      'client_id' => $configuration['client_id'],
      'response_type' => 'code',
      'scope' => $scope,
      'redirect_uri' => $redirect_uri->getGeneratedUrl(),
      'state' => $this->stateToken->generateToken(),
      'nonce' => $this->stateManager->generateNonce(),
      'claims' => $this->proConnectClient->getAuthorizationClaimsParameter(),
    ];

    if (!empty($this->configuration['prompt'])) {
      $query['prompt'] = implode(' ', array_filter($this->configuration['prompt']));
    }

    return ['query' => $query];
  }

  /**
   * Returns the ProConnect callback URL.
   *
   * @param array<string, mixed> $route_parameters
   *   The route parameters.
   * @param array<string, mixed> $options
   *   The route options.
   *
   * @return \Drupal\Core\Url
   *   The callback URL.
   */
  protected function getRedirectUrl(array $route_parameters = [], array $options = []): Url {
    $options += [
      'absolute' => TRUE,
      'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE),
    ];

    return Url::fromRoute('proconnect.check', $route_parameters, $options);
  }

}
