<?php

declare(strict_types=1);

namespace Drupal\proconnect\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\PrivateKey;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Manages the ProConnect nonce stored alongside the OpenID Connect flow.
 */
final class ProConnectStateManager {

  private const NONCE_SESSION_KEY = 'proconnect.nonce';
  private const AUTHENTICATED_SESSION_KEY = 'proconnect.authenticated';
  private const LOGOUT_STATE_TTL = 900;

  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly TimeInterface $time,
    private readonly PrivateKey $privateKey,
  ) {}

  /**
   * Generates and stores a nonce for the current authorization request.
   */
  public function generateNonce(): string {
    $nonce = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $this->getSession()->set(self::NONCE_SESSION_KEY, $nonce);
    return $nonce;
  }

  /**
   * Consumes the stored nonce.
   */
  public function consumeNonce(): ?string {
    $nonce = $this->getSession()->get(self::NONCE_SESSION_KEY);
    $this->getSession()->remove(self::NONCE_SESSION_KEY);
    return is_string($nonce) && $nonce !== '' ? $nonce : NULL;
  }

  /**
   * Clears the stored nonce.
   */
  public function clearNonce(): void {
    $this->getSession()->remove(self::NONCE_SESSION_KEY);
  }

  /**
   * Marks the current session as authenticated through ProConnect.
   */
  public function markAuthenticatedViaProConnect(): void {
    $this->getSession()->set(self::AUTHENTICATED_SESSION_KEY, TRUE);
  }

  /**
   * Returns whether the current session was established via ProConnect.
   */
  public function isAuthenticatedViaProConnect(): bool {
    return $this->getSession()->get(self::AUTHENTICATED_SESSION_KEY, FALSE) === TRUE;
  }

  /**
   * Clears the authentication marker for ProConnect.
   */
  public function clearAuthenticatedViaProConnect(): void {
    $this->getSession()->remove(self::AUTHENTICATED_SESSION_KEY);
  }

  /**
   * Starts a stateless logout flow and signs the CSRF state payload.
   */
  public function startLogout(?string $destination = NULL): string {
    $payload = [
      't' => $this->time->getCurrentTime(),
      'destination' => $destination,
      'nonce' => bin2hex(random_bytes(16)),
    ];
    try {
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
    }
    catch (JsonException $exception) {
      throw new RuntimeException('Unable to encode the ProConnect logout state payload.', 0, $exception);
    }

    $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->getLogoutStateSecret(), TRUE));

    return $encodedPayload . '.' . $signature;
  }

  /**
   * Consumes the stateless logout context when the signature matches.
   *
   * @param string $state
   *
   * @return array{destination: ?string}|null
   *   The stored logout context, or NULL when invalid.
   * @throws \JsonException
   */
  public function consumeLogoutContext(string $state): ?array {
    $parts = explode('.', $state, 2);
    if (count($parts) !== 2) {
      return NULL;
    }

    [$encodedPayload, $providedSignature] = $parts;
    $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->getLogoutStateSecret(), TRUE));
    if (!hash_equals($expectedSignature, $providedSignature)) {
      return NULL;
    }

    try {
      $payload = json_decode($this->base64UrlDecode($encodedPayload), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (JsonException | RuntimeException) {
      return NULL;
    }
    if (!is_array($payload) || !isset($payload['t']) || !is_int($payload['t'])) {
      return NULL;
    }
    if ($payload['t'] < $this->time->getCurrentTime() - self::LOGOUT_STATE_TTL) {
      return NULL;
    }

    $destination = isset($payload['destination']) && is_string($payload['destination']) && $payload['destination'] !== ''
      ? $payload['destination']
      : NULL;

    return ['destination' => $destination];
  }

  /**
   * Clears any pending logout context.
   */
  public function clearLogoutContext(): void {
    // Logout state is stateless, so there is nothing to clear.
  }

  /**
   * Returns the signing secret for logout state tokens.
   */
  private function getLogoutStateSecret(): string {
    $secret = $this->privateKey->get();
    if ($secret === '') {
      throw new \RuntimeException('The Drupal private key is missing; cannot sign the ProConnect logout state.');
    }

    return $secret;
  }

  /**
   * Encodes a string as base64url without padding.
   */
  private function base64UrlEncode(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
  }

  /**
   * Decodes a base64url string.
   */
  private function base64UrlDecode(string $value): string {
    $padding = 4 - (strlen($value) % 4);
    if ($padding < 4) {
      $value .= str_repeat('=', $padding);
    }

    $decoded = base64_decode(strtr($value, '-_', '+/'), TRUE);
    if ($decoded === FALSE) {
      throw new \RuntimeException('Unable to decode the ProConnect logout state payload.');
    }

    return $decoded;
  }

  /**
   * Returns the active session.
   */
  private function getSession(): SessionInterface {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      throw new \RuntimeException('No active request is available to manage the ProConnect session state.');
    }

    return $request->getSession();
  }

  /**
   * Clears ProConnect and OpenID Connect session artifacts from Drupal.
   */
  public function clearSession(OpenIDConnectSessionInterface $session): void {
    $this->clearNonce();
    $this->clearLogoutContext();
    $this->clearAuthenticatedViaProConnect();

    $session->retrieveIdToken(TRUE);
    $session->retrieveAccessToken(TRUE);
    $session->retrieveRefreshToken(TRUE);
    $session->retrieveExpireToken(TRUE);
    $session->retrieveStateToken(TRUE);
    $session->retrieveDestination(TRUE);
    $session->retrieveOp(TRUE);
  }

}
