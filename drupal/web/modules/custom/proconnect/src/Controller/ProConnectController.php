<?php

declare(strict_types=1);

namespace Drupal\proconnect\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Drupal\proconnect\Service\ProConnectClient;
use Drupal\proconnect\Service\ProConnectStateManager;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles ProConnect login and callback routes.
 */
final class ProConnectController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  private readonly EntityStorageInterface $clientStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly OpenIDConnectSessionInterface $openIdConnectSession,
    private readonly OpenIDConnectClaims $openIdConnectClaims,
    private readonly ProConnectClient $proConnectClient,
    private readonly ProConnectStateManager $stateManager,
    private readonly RequestStack $requestStack,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly MessengerInterface $messenger,
    private readonly LoggerChannelInterface $logger,
  ) {
    $this->clientStorage = $entityTypeManager->getStorage('openid_connect_client');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('openid_connect.session'),
      $container->get('openid_connect.claims'),
      $container->get('proconnect.client'),
      $container->get('proconnect.state_manager'),
      $container->get('request_stack'),
      $container->get('url_generator'),
      $container->get('messenger'),
      $container->get('logger.channel.proconnect'),
    );
  }

  /**
   * Redirects the user to ProConnect.
   */
  public function login(): Response {
    try {
      $client = $this->loadEnabledClient();
      $plugin = $client->getPlugin();
      $scopes = $this->openIdConnectClaims->getScopes($plugin);

      $destination = $this->getStringQueryParameter($this->getCurrentRequest(), 'destination');
      if ($destination !== NULL && !UrlHelper::isExternal($destination) && !str_starts_with($destination, '//')) {
        $this->openIdConnectSession->saveTargetLinkUri($destination);
      }
      else {
        $this->openIdConnectSession->saveTargetLinkUri('/');
      }
      $this->openIdConnectSession->saveOp('login');
      $this->logger->notice('ProConnect login flow started.');

      return $plugin->authorize($scopes);
    }
    catch (\InvalidArgumentException|\RuntimeException $exception) {
      $this->logger->error('Unable to start ProConnect login flow: {message}', [
        'message' => $exception->getMessage(),
      ]);
      $this->messenger->addError($this->t('La connexion avec ProConnect est actuellement indisponible. Veuillez réessayer plus tard.'));

      return $this->redirectToLoginForm();
    }
  }

  /**
   * Handles the ProConnect authorization callback.
   */
  public function callback(): Response {
    $this->logger->notice('ProConnect callback received.');

    try {
      $request = $this->getCurrentRequest();
      $client = $this->loadEnabledClient();
      $providerError = $request->query->get('error');
      if (is_string($providerError) && $providerError !== '') {
        $errorDescription = $this->getStringQueryParameter($request, 'error_description');
        $this->clearFailedAuthenticationAttempt();

        if ($this->isDomainAccessDeniedError($providerError, $errorDescription)) {
          $this->logger->notice('ProConnect access denied for an unauthorized email domain.', [
            'error' => $providerError,
            'error_description' => $errorDescription,
            'state' => $this->getStringQueryParameter($request, 'state'),
            'iss' => $this->getStringQueryParameter($request, 'iss'),
          ]);

          return new LocalRedirectResponse($this->urlGenerator->generateFromRoute('proconnect.access_denied'));
        }

        $this->logger->warning('ProConnect returned an authorization error: {error}.', [
          'error' => $providerError,
          'error_description' => $errorDescription,
          'state' => $this->getStringQueryParameter($request, 'state'),
          'iss' => $this->getStringQueryParameter($request, 'iss'),
        ]);
        $this->messenger->addError($this->t('La connexion avec ProConnect a été refusée ou interrompue.'));

        return $this->redirectToLoginForm();
      }

      if ($this->getStringQueryParameter($request, 'state') === NULL || $this->getStringQueryParameter($request, 'code') === NULL) {
        throw new InvalidArgumentException('The ProConnect callback did not provide the expected authorization parameters.');
      }

      return new LocalRedirectResponse($this->urlGenerator->generateFromRoute(
        'openid_connect.redirect_controller_redirect',
        ['openid_connect_client' => $client->id()],
        ['query' => $request->query->all()],
      ));
    }
    catch (InvalidArgumentException|RuntimeException $exception) {
      $this->clearFailedAuthenticationAttempt();
      $this->logger->error('ProConnect authentication failed: {message}', [
        'message' => $exception->getMessage(),
      ]);
      $this->messenger->addError($this->t('La connexion avec ProConnect a échoué. Veuillez réessayer.'));

      return $this->redirectToLoginForm();
    }
  }

  /**
   * Redirects the user to the ProConnect logout endpoint.
   */
  public function logout(): Response {
    $destination = '/';

    try {
      if (!$this->currentUser->isAuthenticated() || !$this->stateManager->isAuthenticatedViaProConnect()) {
        return $this->performLocalLogout($destination);
      }

      $client = $this->loadEnabledClient();
      $endpoints = $client->getPlugin()->getEndpoints();

      $endSessionEndpoint = $endpoints['end_session'] ?? NULL;
      if (!is_string($endSessionEndpoint) || $endSessionEndpoint === '') {
        throw new RuntimeException('The ProConnect end session endpoint could not be resolved.');
      }

      $idToken = $this->openIdConnectSession->retrieveIdToken();
      if ($idToken === NULL || $idToken === '') {
        throw new RuntimeException('The ProConnect id_token is missing from the Drupal session.');
      }

      $state = $this->stateManager->startLogout($destination);

      $logoutUrl = $this->buildEndSessionUrl(
        $endSessionEndpoint,
        $idToken,
        $state,
        $this->buildPostLogoutRedirectUri($client),
      );
      $settings = $client->get('settings') ?? [];

      $this->logger->error(
        'PROCONNECT LOGOUT DEBUG:
  logout_url=@logout_url
  decoded_logout_url=@decoded_logout_url
  post_logout_redirect_uri=@redirect
  host=@host
  request_uri=@request_uri
  client_id=@client_id
  current_uri=@current_uri',
        [
          '@logout_url' => $logoutUrl,
          '@decoded_logout_url' => urldecode($logoutUrl),
          '@redirect' => $this->buildPostLogoutRedirectUri($client),
          '@host' => $this->getCurrentRequest()->getSchemeAndHttpHost(),
          '@request_uri' => $this->getCurrentRequest()->getRequestUri(),
          '@client_id' => $settings['client_id'] ?? 'undefined',
          '@current_uri' => $this->getCurrentRequest()->getUri(),
        ]
      );

      $this->clearProConnectSessionArtifacts();

      if ($this->currentUser->isAuthenticated()) {
        user_logout();
      }
      return new TrustedRedirectResponse($logoutUrl);
    }
    catch (InvalidArgumentException|RuntimeException $exception) {
      $this->logger->error('Unable to start ProConnect logout flow: {message}', [
        'message' => $exception->getMessage(),
      ]);

      $this->messenger->addWarning($this->t('La déconnexion ProConnect distante est indisponible. La session locale a été fermée.'));

      return $this->performLocalLogout($destination);
    }
  }

  /**
   * Handles the ProConnect post-logout callback.
   */
  public function postLogoutRedirect(): Response {
    $this->logger->notice('ProConnect post-logout callback received.');

    try {
      $state = $this->getStringQueryParameter($this->getCurrentRequest(), 'state');
      if ($state === NULL) {
        throw new InvalidArgumentException('The ProConnect post logout callback did not provide a state parameter.');
      }

      $context = $this->stateManager->consumeLogoutContext($state);
      if ($context === NULL) {
        throw new InvalidArgumentException('The ProConnect logout state is invalid or expired.');
      }

      $this->clearProConnectSessionArtifacts();

      return $this->redirectToDestination($context['destination']);
    }
    catch (InvalidArgumentException|RuntimeException $exception) {
      $this->clearProConnectSessionArtifacts();
      $this->logger->error('ProConnect logout callback failed: {message}', [
        'message' => $exception->getMessage(),
      ]);

      return $this->redirectToDestination(NULL);
    }
  }

  /**
   * Clears ProConnect session artifacts and performs a local Drupal logout.
   */
  public function metadata(): Response {
    return $this->performLocalLogout($this->normalizeDestination($this->getCurrentRequest()->query->get('destination')));
  }

  /**
   * Redirects back to Drupal's login form.
   */
  private function redirectToLoginForm(): LocalRedirectResponse {
    return new LocalRedirectResponse($this->urlGenerator->generateFromRoute('user.login'));
  }

  /**
   * Redirects to an internal destination or the front page.
   */
  private function redirectToDestination(?string $destination): LocalRedirectResponse {
    if ($destination !== NULL) {
      return new LocalRedirectResponse($destination);
    }

    return new LocalRedirectResponse($this->urlGenerator->generateFromRoute('<front>'));
  }

  /**
   * Logs the user out locally and clears ProConnect session artifacts.
   */
  private function performLocalLogout(?string $destination): LocalRedirectResponse {
    $this->clearProConnectSessionArtifacts();
    if ($this->currentUser->isAuthenticated()) {
      user_logout();
    }

    return $this->redirectToDestination($destination);
  }

  /**
   * Clears ProConnect and OpenID Connect session artifacts from Drupal.
   */
  private function clearProConnectSessionArtifacts(): void {
    $this->stateManager->clearNonce();
    $this->stateManager->clearLogoutContext();
    $this->stateManager->clearAuthenticatedViaProConnect();

    $this->openIdConnectSession->retrieveIdToken(TRUE);
    $this->openIdConnectSession->retrieveAccessToken(TRUE);
    $this->openIdConnectSession->retrieveRefreshToken(TRUE);
    $this->openIdConnectSession->retrieveExpireToken(TRUE);
    $this->openIdConnectSession->retrieveStateToken(TRUE);
    $this->openIdConnectSession->retrieveDestination(TRUE);
    $this->openIdConnectSession->retrieveOp(TRUE);
  }

  /**
   * Builds the absolute post-logout redirect URI.
   */
  private function buildPostLogoutRedirectUri(OpenIDConnectClientEntityInterface $client): string {
    return $this->urlGenerator->generateFromRoute('proconnect.post_logout_redirect', [], ['absolute' => TRUE]);
  }

  /**
   * Builds the ProConnect end-session URL.
   */
  private function buildEndSessionUrl(
    string $endSessionEndpoint,
    string $idToken,
    ?string $state = NULL,
    ?string $postLogoutRedirectUri = NULL,
  ): string {
    $query = [
      'id_token_hint' => $idToken,
    ];

    if ($state !== NULL) {
      $query['state'] = $state;
    }

    if ($postLogoutRedirectUri !== NULL) {
      $query['post_logout_redirect_uri'] = $postLogoutRedirectUri;
    }

    return $endSessionEndpoint . '?' . http_build_query(
        $query,
        '',
        '&',
        PHP_QUERY_RFC3986,
      );
  }

  /**
   * Loads the enabled ProConnect OpenID Connect client entity.
   */
  private function loadEnabledClient(): OpenIDConnectClientEntityInterface {
    $client = $this->clientStorage->load('proconnect');
    if (!$client instanceof OpenIDConnectClientEntityInterface || !$client->status()) {
      throw new \RuntimeException('The OpenID Connect client "proconnect" is missing or disabled.');
    }

    $plugin = $client->getPlugin();
    if (!$plugin instanceof OpenIDConnectClientInterface) {
      throw new \RuntimeException('The OpenID Connect ProConnect plugin could not be loaded.');
    }

    return $client;
  }

  /**
   * Returns a normalized internal destination, or NULL when unsafe.
   */
  private function normalizeDestination(mixed $destination): ?string {
    if (!is_string($destination)) {
      return NULL;
    }

    $destination = trim($destination);
    if ($destination === '' || UrlHelper::isExternal($destination) || str_starts_with($destination, '//')) {
      return NULL;
    }

    return '/' . ltrim($destination, '/');
  }

  /**
   * Clears the session artifacts kept during a failed authentication attempt.
   */
  private function clearFailedAuthenticationAttempt(): void {
    $this->stateManager->clearNonce();
    $this->openIdConnectSession->retrieveStateToken(TRUE);
    $this->openIdConnectSession->retrieveDestination(TRUE);
    $this->openIdConnectSession->retrieveOp(TRUE);
  }

  /**
   * Detects the ProConnect refusal used for unauthorized email domains.
   */
  private function isDomainAccessDeniedError(string $providerError, ?string $errorDescription): bool {
    $haystack = mb_strtolower(trim($providerError . ' ' . (string) $errorDescription));
    if ($haystack === '') {
      return FALSE;
    }

    foreach ([
      'y500015',
      '500015',
      'e-mail non autorisé',
      'email non autorise',
      'compte agent autorisé',
      'compte agent autorise',
      'vous n’êtes pas reconnu comme agent',
      'vous n\'etes pas reconnu comme agent',
    ] as $needle) {
      if (str_contains($haystack, $needle)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns a non-empty string query parameter when present.
   */
  private function getStringQueryParameter(Request $request, string $name): ?string {
    $value = $request->query->get($name);
    if (!is_string($value) || trim($value) === '') {
      return NULL;
    }

    return trim($value);
  }

  /**
   * Returns the current request or fails fast when unavailable.
   */
  private function getCurrentRequest(): Request {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      throw new RuntimeException('No active request is available for the ProConnect authentication flow.');
    }

    return $request;
  }

}
