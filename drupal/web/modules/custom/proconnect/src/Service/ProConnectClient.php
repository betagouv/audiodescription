<?php

declare(strict_types=1);

namespace Drupal\proconnect\Service;

use GuzzleHttp\Exception\GuzzleException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves ProConnect endpoints and validates OIDC artifacts.
 */
final class ProConnectClient {

  private const DEFAULT_DISCOVERY_PATH = '/api/v2/.well-known/openid-configuration';
  private const DEFAULT_AUTHORIZATION_PATH = '/api/v2/authorize';
  private const DEFAULT_TOKEN_PATH = '/api/v2/token';
  private const DEFAULT_USERINFO_PATH = '/api/v2/userinfo';
  private const DEFAULT_END_SESSION_PATH = '/api/v2/session/end';
  private const DEFAULT_SCOPES = [
    'openid',
    'email',
    'given_name',
    'usual_name',
    'uid',
    'idp_id',
    'phone',
    'custom',
  ];
  private const DISCOVERY_CACHE_TTL = 3600;
  private const JWKS_CACHE_TTL = 3600;

  /**
   * Module settings loaded from settings.php.
   *
   * @var array<string, mixed>
   */
  private array $settingsConfiguration = [];

  /**
   * Builds the ProConnect HTTP client helper.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly CacheBackendInterface $cache,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
    Settings $settings,
  ) {
    $configuration = $settings->get('proconnect', []);
    $this->settingsConfiguration = is_array($configuration) ? $configuration : [];
  }

  /**
   * Returns the effective ProConnect configuration for the client plugin.
   *
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   *
   * @return array<string, mixed>
   *   Merged configuration.
   */
  public function getClientConfiguration(array $pluginConfiguration): array {
    $configuration = array_merge([
      'client_id' => '',
      'client_secret' => '',
      'domain' => '',
      'algorithm' => '',
      'issuer_url' => '',
      'issuer' => '',
      'discovery_path' => self::DEFAULT_DISCOVERY_PATH,
      'authorization_endpoint' => '',
      'authorization_path' => self::DEFAULT_AUTHORIZATION_PATH,
      'token_endpoint' => '',
      'token_path' => self::DEFAULT_TOKEN_PATH,
      'userinfo_endpoint' => '',
      'userinfo_path' => self::DEFAULT_USERINFO_PATH,
      'end_session_endpoint' => '',
      'end_session_path' => self::DEFAULT_END_SESSION_PATH,
      'post_logout_redirect_uri' => '',
      'jwks_uri' => '',
      'scopes' => self::DEFAULT_SCOPES,
    ], $pluginConfiguration);

    foreach ($this->settingsConfiguration as $key => $value) {
      if (!array_key_exists($key, $configuration)) {
        continue;
      }
      if (is_string($value)) {
        $value = trim($value);
      }
      if ($value === '' || $value === NULL) {
        continue;
      }
      $configuration[$key] = $value;
    }

    if (
      isset($this->settingsConfiguration['scope']) &&
      is_string($this->settingsConfiguration['scope']) &&
      trim($this->settingsConfiguration['scope']) !== ''
    ) {
      $configuration['scopes'] = $this->settingsConfiguration['scope'];
    }

    if (is_string($configuration['scopes'])) {
      $configuration['scopes'] = preg_split('/\s+/', trim($configuration['scopes'])) ?: [];
      $configuration['scopes'] = array_values(array_filter(
        $configuration['scopes'],
        static fn (mixed $scope): bool => is_string($scope) && $scope !== '',
      ));
    }
    if (!is_array($configuration['scopes']) || $configuration['scopes'] === []) {
      $configuration['scopes'] = self::DEFAULT_SCOPES;
    }

    return $configuration;
  }

  /**
   * Builds the optional OIDC claims parameter for ProConnect authorization.
   *
   * ProConnect can return the authentication method reference (`amr`) in the
   * `id_token` when it is explicitly requested through the `claims` parameter.
   * The identity attributes themselves are requested through scopes, so we do
   * not duplicate them in the `userinfo` claims block.
   *
   * @throws \RuntimeException
   *   Thrown when the claims payload cannot be encoded.
   */
  public function getAuthorizationClaimsParameter(): string {
    $claims = [
      'id_token' => [
        'amr' => ['essential' => TRUE],
      ],
    ];

    try {
      return json_encode($claims, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException('Unable to encode the ProConnect claims parameter.', 0, $exception);
    }
  }

  /**
   * Fails fast when the effective ProConnect configuration is incomplete.
   *
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   */
  public function assertUsableConfiguration(array $pluginConfiguration): void {
    $configuration = $this->getClientConfiguration($pluginConfiguration);

    $this->getRequiredString($configuration, 'client_id');
    $this->getRequiredString($configuration, 'client_secret');
    $this->getRequiredString($configuration, 'domain');
    $this->getRequiredString($configuration, 'algorithm');

    $this->getEndpoints($pluginConfiguration);
    $this->getExpectedIssuer($configuration);
  }

  /**
   * Resolves the OIDC endpoints for the effective configuration.
   *
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   *
   * @return array{authorization: string, token: string, userinfo: string, end_session: string}
   *   Resolved endpoint URLs.
   */
  public function getEndpoints(array $pluginConfiguration): array {
    $configuration = $this->getClientConfiguration($pluginConfiguration);

    return [
      'authorization' => $this->resolveEndpoint(
        'authorization_endpoint',
        'authorization_endpoint',
        'authorization_path',
        self::DEFAULT_AUTHORIZATION_PATH,
        $configuration,
      ),
      'token' => $this->resolveEndpoint(
        'token_endpoint',
        'token_endpoint',
        'token_path',
        self::DEFAULT_TOKEN_PATH,
        $configuration,
      ),
      'userinfo' => $this->resolveEndpoint(
        'userinfo_endpoint',
        'userinfo_endpoint',
        'userinfo_path',
        self::DEFAULT_USERINFO_PATH,
        $configuration,
      ),
      'end_session' => $this->resolveEndpoint(
        'end_session_endpoint',
        'end_session_endpoint',
        'end_session_path',
        self::DEFAULT_END_SESSION_PATH,
        $configuration,
      ),
    ];
  }

  /**
   * Returns the effective post-logout redirect URI.
   *
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   * @param string $defaultUri
   *   The default fallback URI.
   *
   * @return string
   *   The resolved URI.
   */
  public function getPostLogoutRedirectUri(array $pluginConfiguration, string $defaultUri): string {
    $configuration = $this->getClientConfiguration($pluginConfiguration);
    $configuredUri = $configuration['post_logout_redirect_uri'] ?? NULL;

    if (is_string($configuredUri) && trim($configuredUri) !== '') {
      return $this->normalizeAbsoluteUri(trim($configuredUri));
    }

    return $defaultUri;
  }

  /**
   * Validates the optional issuer hint from the authorization response.
   *
   * @param string|null $issuer
   *   The issuer from the authorization response.
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   */
  public function validateAuthorizationResponseIssuer(?string $issuer, array $pluginConfiguration): void {
    if ($issuer === NULL || trim($issuer) === '') {
      return;
    }

    $configuration = $this->getClientConfiguration($pluginConfiguration);
    if (rtrim($this->normalizeBaseUri(trim($issuer)), '/') !== rtrim($this->getExpectedIssuer($configuration), '/')) {
      throw new \RuntimeException('The ProConnect authorization response issuer is invalid.');
    }
  }

  /**
   * Exchanges the authorization code for tokens.
   *
   * @param string $code
   *   The authorization code.
   * @param string $redirectUri
   *   The redirect URI used in the authorization request.
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   *
   * @return array<string, mixed>
   *   Token response data.
   */
  public function exchangeCode(string $code, string $redirectUri, array $pluginConfiguration): array {
    $configuration = $this->getClientConfiguration($pluginConfiguration);
    $endpoint = $this->resolveEndpoint(
      'token_endpoint',
      'token_endpoint',
      'token_path',
      self::DEFAULT_TOKEN_PATH,
      $configuration,
    );

    $responseBody = $this->request('POST', $endpoint, [
      'form_params' => [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => $this->getRequiredString($configuration, 'client_id'),
        'client_secret' => $this->getRequiredString($configuration, 'client_secret'),
      ],
      'headers' => [
        'Accept' => 'application/json',
      ],
    ], 'exchange the ProConnect authorization code');

    $tokenResponse = $this->decodeJson($responseBody, 'ProConnect token response');
    $this->requireNonEmptyString($tokenResponse['access_token'] ?? NULL, 'The ProConnect token response did not contain an access_token.');
    $this->requireNonEmptyString($tokenResponse['id_token'] ?? NULL, 'The ProConnect token response did not contain an id_token.');

    return $tokenResponse;
  }

  /**
   * Validates the signed id_token and returns its claims.
   *
   * @param string $idToken
   *   The ID token.
   * @param string $expectedNonce
   *   The expected nonce.
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   *
   * @return array<string, mixed>
   *   Validated id_token claims.
   */
  public function validateIdToken(string $idToken, string $expectedNonce, array $pluginConfiguration): array {
    return $this->parseAndValidateJwt(
      $idToken,
      TRUE,
      $expectedNonce,
      $this->getClientConfiguration($pluginConfiguration),
    );
  }

  /**
   * Fetches and validates userinfo from ProConnect.
   *
   * @param string $accessToken
   *   The access token.
   * @param array<string, mixed> $pluginConfiguration
   *   Stored plugin configuration.
   *
   * @return array<string, mixed>
   *   Validated userinfo claims.
   */
  public function fetchUserInfo(string $accessToken, array $pluginConfiguration): array {
    $configuration = $this->getClientConfiguration($pluginConfiguration);
    $endpoint = $this->resolveEndpoint(
      'userinfo_endpoint',
      'userinfo_endpoint',
      'userinfo_path',
      self::DEFAULT_USERINFO_PATH,
      $configuration,
    );

    $responseBody = $this->request('GET', $endpoint, [
      'headers' => [
        'Accept' => 'application/json, application/jwt',
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ], 'retrieve ProConnect user information');

    $userinfo = $this->looksLikeJwt($responseBody)
      ? $this->parseAndValidateJwt($responseBody, FALSE, NULL, $configuration)
      : $this->decodeJson($responseBody, 'ProConnect userinfo response');

    $email = $userinfo['email'] ?? NULL;
    if (!is_string($email) || trim($email) === '' || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException('The ProConnect identity does not contain a valid email claim.');
    }

    return $userinfo;
  }

  /**
   * Performs an HTTP request and returns the raw response body.
   */
  private function request(string $method, string $uri, array $options, string $action): string {
    try {
      $response = $this->httpClient->request($method, $uri, $options + [
        'http_errors' => FALSE,
        'timeout' => 10,
        'connect_timeout' => 5,
      ]);
    }
    catch (GuzzleException $exception) {
      throw new \RuntimeException(sprintf('Unable to %s.', $action), 0, $exception);
    }

    if ($response->getStatusCode() < Response::HTTP_OK || $response->getStatusCode() >= Response::HTTP_MULTIPLE_CHOICES) {
      throw new \RuntimeException(sprintf(
        'ProConnect returned HTTP %d while trying to %s.',
        $response->getStatusCode(),
        $action,
      ));
    }

    $body = trim((string) $response->getBody());
    if ($body === '') {
      throw new \RuntimeException(sprintf('ProConnect returned an empty response while trying to %s.', $action));
    }

    return $body;
  }

  /**
   * Decodes a JSON payload into an associative array.
   *
   * @return array<string, mixed>
   *   Decoded JSON structure.
   */
  private function decodeJson(string $payload, string $context): array {
    try {
      $data = json_decode($payload, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(sprintf('Unable to decode the %s as JSON.', $context), 0, $exception);
    }

    if (!is_array($data)) {
      throw new \RuntimeException(sprintf('The %s did not decode into the expected JSON object structure.', $context));
    }

    return $data;
  }

  /**
   * Resolves an endpoint using explicit config, discovery, then defaults.
   *
   * @param string $metadataKey
   *   The metadata key.
   * @param string $endpointSettingKey
   *   The configuration key for the endpoint.
   * @param string $pathSettingKey
   *   The configuration key for the path.
   * @param string $defaultPath
   *   The default path.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return string
   *   The resolved endpoint URL.
   */
  private function resolveEndpoint(string $metadataKey, string $endpointSettingKey, string $pathSettingKey, string $defaultPath, array $configuration): string {
    if (!empty($configuration[$endpointSettingKey]) && is_string($configuration[$endpointSettingKey])) {
      return $this->normalizeAbsoluteUri($configuration[$endpointSettingKey]);
    }

    $metadata = $this->tryGetProviderMetadata($configuration);
    if ($metadata !== NULL && isset($metadata[$metadataKey]) && is_string($metadata[$metadataKey]) && $metadata[$metadataKey] !== '') {
      return $this->normalizeAbsoluteUri($metadata[$metadataKey]);
    }

    $path = $configuration[$pathSettingKey] ?? $defaultPath;
    if (!is_string($path) || $path === '') {
      $path = $defaultPath;
    }

    return $this->buildAbsoluteUri($path, $configuration);
  }

  /**
   * Retrieves the provider discovery document.
   *
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return array<string, mixed>|null
   *   Discovery metadata or NULL on failure.
   */
  private function tryGetProviderMetadata(array $configuration): ?array {
    $cacheId = $this->buildCacheId('discovery', $configuration);
    $cache = $this->cache->get($cacheId);
    if ($cache !== FALSE && is_array($cache->data)) {
      return $cache->data;
    }

    $discoveryUri = !empty($configuration['discovery_uri']) && is_string($configuration['discovery_uri'])
      ? $configuration['discovery_uri']
      : $this->buildDiscoveryUri($configuration);

    try {
      $metadata = $this->decodeJson(
        $this->request('GET', $this->normalizeAbsoluteUri($discoveryUri), [
          'headers' => ['Accept' => 'application/json'],
        ], 'retrieve the ProConnect discovery document'),
        'ProConnect discovery document',
      );
    }
    catch (\RuntimeException $exception) {
      $this->logger->warning('Unable to load ProConnect discovery metadata, falling back to configured endpoints: {message}', [
        'message' => $exception->getMessage(),
      ]);
      return NULL;
    }

    $this->cache->set($cacheId, $metadata, $this->time->getRequestTime() + self::DISCOVERY_CACHE_TTL);
    return $metadata;
  }

  /**
   * Retrieves the provider JWKS.
   *
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return array<int, array<string, mixed>>
   *   The usable JWKs.
   */
  private function getJwks(array $configuration): array {
    $cacheId = $this->buildCacheId('jwks', $configuration);
    $cache = $this->cache->get($cacheId);
    if ($cache !== FALSE && is_array($cache->data)) {
      return $cache->data;
    }

    $jwksUri = $configuration['jwks_uri'] ?? '';
    if (!is_string($jwksUri) || $jwksUri === '') {
      $metadata = $this->tryGetProviderMetadata($configuration);
      if ($metadata === NULL || !isset($metadata['jwks_uri']) || !is_string($metadata['jwks_uri']) || $metadata['jwks_uri'] === '') {
        throw new \RuntimeException('The ProConnect JWKS URI could not be resolved.');
      }
      $jwksUri = $metadata['jwks_uri'];
    }

    $jwks = $this->decodeJson(
      $this->request('GET', $this->normalizeAbsoluteUri($jwksUri), [
        'headers' => ['Accept' => 'application/json'],
      ], 'retrieve the ProConnect JWKS document'),
      'ProConnect JWKS document',
    );

    if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
      throw new \RuntimeException('The ProConnect JWKS document does not contain a valid keys array.');
    }

    /** @var array<int, array<string, mixed>> $keys */
    $keys = array_values(array_filter($jwks['keys'], 'is_array'));
    if ($keys === []) {
      throw new \RuntimeException('The ProConnect JWKS document does not contain any usable signing keys.');
    }

    $this->cache->set($cacheId, $keys, $this->time->getRequestTime() + self::JWKS_CACHE_TTL);
    return $keys;
  }

  /**
   * Parses and validates a JWT.
   *
   * @param string $jwt
   *   The JWT string.
   * @param bool $validateIdToken
   *   Whether to validate ID token claims.
   * @param string|null $expectedNonce
   *   The expected nonce.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return array<string, mixed>
   *   Validated JWT claims.
   */
  private function parseAndValidateJwt(string $jwt, bool $validateIdToken, ?string $expectedNonce, array $configuration): array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
      throw new \InvalidArgumentException('The ProConnect JWT is malformed.');
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $header = $this->decodeJson($this->base64UrlDecode($encodedHeader), 'ProConnect JWT header');
    $claims = $this->decodeJson($this->base64UrlDecode($encodedPayload), 'ProConnect JWT payload');
    $signature = $this->base64UrlDecode($encodedSignature);

    $this->verifyJwtSignature($header, $encodedHeader . '.' . $encodedPayload, $signature, $configuration);
    $this->validateTemporalClaims($claims);

    if ($validateIdToken) {
      $this->validateIdTokenClaims($claims, $expectedNonce, $configuration);
    }
    return $claims;
  }

  /**
   * Validates id_token-specific claims.
   *
   * @param array<string, mixed> $claims
   *   JWT claims.
   * @param string|null $expectedNonce
   *   The expected nonce.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   */
  private function validateIdTokenClaims(array $claims, ?string $expectedNonce, array $configuration): void {
    $issuer = $claims['iss'] ?? NULL;
    if (
      !is_string($issuer) ||
      $issuer === '' ||
      rtrim($this->normalizeBaseUri($issuer), '/') !== rtrim($this->getExpectedIssuer($configuration), '/')
    ) {
      throw new \RuntimeException('The ProConnect id_token issuer is invalid.');
    }

    $subject = $claims['sub'] ?? NULL;
    if (!is_string($subject) || $subject === '') {
      throw new \RuntimeException('The ProConnect id_token does not contain a valid subject claim.');
    }

    if (!$this->audienceMatchesClientId($claims['aud'] ?? NULL, $configuration)) {
      throw new \RuntimeException('The ProConnect id_token audience does not match the configured client.');
    }

    $expiration = $claims['exp'] ?? NULL;
    if (!is_int($expiration) && !is_numeric($expiration)) {
      throw new \RuntimeException('The ProConnect id_token does not contain a valid expiration claim.');
    }
    if ((int) $expiration <= $this->time->getCurrentTime()) {
      throw new \RuntimeException('The ProConnect id_token is expired.');
    }

    $nonce = $claims['nonce'] ?? NULL;
    if (!is_string($nonce) || $nonce === '' || $expectedNonce === NULL || !hash_equals($expectedNonce, $nonce)) {
      throw new \RuntimeException('The ProConnect id_token nonce is invalid.');
    }
  }

  /**
   * Validates standard JWT temporal claims.
   *
   * @param array<string, mixed> $claims
   *   JWT claims.
   */
  private function validateTemporalClaims(array $claims): void {
    $now = $this->time->getCurrentTime();

    if (isset($claims['exp']) && (!is_int($claims['exp']) && !is_numeric($claims['exp']))) {
      throw new \RuntimeException('The ProConnect JWT contains an invalid expiration claim.');
    }
    if (isset($claims['exp']) && (int) $claims['exp'] <= $now) {
      throw new \RuntimeException('The ProConnect JWT is expired.');
    }

    if (isset($claims['nbf']) && (!is_int($claims['nbf']) && !is_numeric($claims['nbf']))) {
      throw new \RuntimeException('The ProConnect JWT contains an invalid not-before claim.');
    }
    if (isset($claims['nbf']) && (int) $claims['nbf'] > $now + 60) {
      throw new \RuntimeException('The ProConnect JWT is not valid yet.');
    }

    if (isset($claims['iat']) && (!is_int($claims['iat']) && !is_numeric($claims['iat']))) {
      throw new \RuntimeException('The ProConnect JWT contains an invalid issued-at claim.');
    }
    if (isset($claims['iat']) && (int) $claims['iat'] > $now + 60) {
      throw new \RuntimeException('The ProConnect JWT issued-at claim is in the future.');
    }
  }

  /**
   * Verifies the signature of a JWT.
   *
   * @param array<string, mixed> $header
   *   JWT header.
   * @param string $signingInput
   *   The signing input.
   * @param string $signature
   *   The signature.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   */
  private function verifyJwtSignature(array $header, string $signingInput, string $signature, array $configuration): void {
    $configuredAlgorithm = strtoupper($this->getRequiredString($configuration, 'algorithm'));
    $tokenAlgorithm = strtoupper($this->requireNonEmptyString($header['alg'] ?? NULL, 'The ProConnect JWT header is missing the signing algorithm.'));

    if ($configuredAlgorithm !== $tokenAlgorithm) {
      throw new \RuntimeException(sprintf(
        'The ProConnect JWT signing algorithm "%s" does not match the configured algorithm "%s".',
        $tokenAlgorithm,
        $configuredAlgorithm,
      ));
    }

    if (str_starts_with($configuredAlgorithm, 'HS')) {
      $this->verifyHmacSignature($configuredAlgorithm, $signingInput, $signature, $configuration);
      return;
    }

    $jwk = $this->selectSigningKey($header, $configuredAlgorithm, $configuration);
    $publicKey = $this->buildPublicKeyFromJwk($jwk);
    $opensslAlgorithm = $this->getOpenSslAlgorithm($configuredAlgorithm);

    if (str_starts_with($configuredAlgorithm, 'ES')) {
      $signature = $this->convertJoseEcdsaSignatureToDer($configuredAlgorithm, $signature);
    }

    $verification = openssl_verify($signingInput, $signature, $publicKey, $opensslAlgorithm);
    if ($verification !== 1) {
      throw new \RuntimeException('Unable to verify the ProConnect JWT signature.');
    }
  }

  /**
   * Verifies an HMAC-signed JWT.
   *
   * @param string $algorithm
   *   The algorithm.
   * @param string $signingInput
   *   The signing input.
   * @param string $signature
   *   The signature.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   */
  private function verifyHmacSignature(string $algorithm, string $signingInput, string $signature, array $configuration): void {
    $hashAlgorithm = match ($algorithm) {
      'HS256' => 'sha256',
      'HS384' => 'sha384',
      'HS512' => 'sha512',
      default => throw new \InvalidArgumentException(sprintf('The ProConnect algorithm "%s" is not supported.', $algorithm)),
    };

    $expectedSignature = hash_hmac(
      $hashAlgorithm,
      $signingInput,
      $this->getRequiredString($configuration, 'client_secret'),
      TRUE,
    );

    if (!hash_equals($expectedSignature, $signature)) {
      throw new \RuntimeException('Unable to verify the ProConnect JWT signature.');
    }
  }

  /**
   * Selects the appropriate JWK for the JWT header.
   *
   * @param array<string, mixed> $header
   *   JWT header.
   * @param string $algorithm
   *   The algorithm.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return array<string, mixed>
   *   Matching JWK.
   */
  private function selectSigningKey(array $header, string $algorithm, array $configuration): array {
    $kid = isset($header['kid']) && is_string($header['kid']) && $header['kid'] !== '' ? $header['kid'] : NULL;
    $candidates = [];

    foreach ($this->getJwks($configuration) as $jwk) {
      if ($kid !== NULL && (($jwk['kid'] ?? NULL) !== $kid)) {
        continue;
      }
      if (isset($jwk['use']) && $jwk['use'] !== 'sig') {
        continue;
      }
      if (isset($jwk['alg']) && is_string($jwk['alg']) && strtoupper($jwk['alg']) !== $algorithm) {
        continue;
      }
      $candidates[] = $jwk;
    }

    if ($candidates === []) {
      throw new \RuntimeException('No ProConnect signing key matched the JWT header.');
    }

    if ($kid !== NULL || count($candidates) === 1) {
      return $candidates[0];
    }

    foreach ($candidates as $candidate) {
      $keyType = $candidate['kty'] ?? NULL;
      if (str_starts_with($algorithm, 'RS') && $keyType === 'RSA') {
        return $candidate;
      }
      if (str_starts_with($algorithm, 'ES') && $keyType === 'EC') {
        return $candidate;
      }
    }

    return $candidates[0];
  }

  /**
   * Builds a PEM public key from a JWK.
   *
   * @param array<string, mixed> $jwk
   *   JWK definition.
   */
  private function buildPublicKeyFromJwk(array $jwk): string {
    $keyType = $jwk['kty'] ?? NULL;

    if ($keyType === 'RSA') {
      $modulus = $this->base64UrlDecode($this->requireNonEmptyString($jwk['n'] ?? NULL, 'The ProConnect RSA key is missing the modulus.'));
      $exponent = $this->base64UrlDecode($this->requireNonEmptyString($jwk['e'] ?? NULL, 'The ProConnect RSA key is missing the exponent.'));

      $rsaPublicKey = $this->asn1Sequence(
        $this->asn1Integer($modulus) .
        $this->asn1Integer($exponent)
      );
      $subjectPublicKeyInfo = $this->asn1Sequence(
        $this->asn1Sequence(
          $this->asn1ObjectIdentifier("\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01") .
          $this->asn1Null()
        ) .
        $this->asn1BitString($rsaPublicKey)
      );

      return $this->toPem($subjectPublicKeyInfo, 'PUBLIC KEY');
    }

    if ($keyType === 'EC') {
      $curve = $this->requireNonEmptyString($jwk['crv'] ?? NULL, 'The ProConnect EC key is missing the curve name.');
      $x = $this->base64UrlDecode($this->requireNonEmptyString($jwk['x'] ?? NULL, 'The ProConnect EC key is missing the X coordinate.'));
      $y = $this->base64UrlDecode($this->requireNonEmptyString($jwk['y'] ?? NULL, 'The ProConnect EC key is missing the Y coordinate.'));

      $curveOid = match ($curve) {
        'P-256' => "\x2A\x86\x48\xCE\x3D\x03\x01\x07",
        'P-384' => "\x2B\x81\x04\x00\x22",
        'P-521' => "\x2B\x81\x04\x00\x23",
        default => throw new \InvalidArgumentException(sprintf('The ProConnect EC curve "%s" is not supported.', $curve)),
      };

      $point = "\x04" . $x . $y;
      $subjectPublicKeyInfo = $this->asn1Sequence(
        $this->asn1Sequence(
          $this->asn1ObjectIdentifier("\x2A\x86\x48\xCE\x3D\x02\x01") .
          $this->asn1ObjectIdentifier($curveOid)
        ) .
        $this->asn1BitString($point)
      );

      return $this->toPem($subjectPublicKeyInfo, 'PUBLIC KEY');
    }

    throw new \InvalidArgumentException('The ProConnect JWK key type is not supported.');
  }

  /**
   * Returns the expected issuer.
   *
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   */
  private function getExpectedIssuer(array $configuration): string {
    if (!empty($configuration['issuer']) && is_string($configuration['issuer'])) {
      return $this->normalizeBaseUri($configuration['issuer']);
    }

    $metadata = $this->tryGetProviderMetadata($configuration);
    if ($metadata !== NULL && isset($metadata['issuer']) && is_string($metadata['issuer']) && $metadata['issuer'] !== '') {
      return $this->normalizeBaseUri($metadata['issuer']);
    }

    if (!empty($configuration['issuer_url']) && is_string($configuration['issuer_url'])) {
      return $this->normalizeBaseUri($configuration['issuer_url']);
    }

    $path = $configuration['authorization_path'] ?? self::DEFAULT_AUTHORIZATION_PATH;
    if (!is_string($path) || $path === '') {
      $path = self::DEFAULT_AUTHORIZATION_PATH;
    }

    if (preg_match('@^https?://@i', $path) === 1) {
      $path = (string) parse_url($path, PHP_URL_PATH);
    }

    $issuerPath = rtrim((string) dirname($path), '/');
    if ($issuerPath !== '' && $issuerPath !== '.') {
      $baseUri = $this->normalizeBaseUri($this->getRequiredString($configuration, 'domain'));
      $basePath = rtrim((string) parse_url($baseUri, PHP_URL_PATH), '/');

      if ($basePath !== '' && $basePath === $issuerPath) {
        return $baseUri;
      }

      return $baseUri . $issuerPath;
    }

    return $this->normalizeBaseUri($this->getRequiredString($configuration, 'domain'));
  }

  /**
   * Validates the JWT audience against the configured client ID.
   *
   * @param mixed $audience
   *   JWT audience claim.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   */
  private function audienceMatchesClientId(mixed $audience, array $configuration): bool {
    $clientId = $this->getRequiredString($configuration, 'client_id');

    if (is_string($audience)) {
      return hash_equals($clientId, $audience);
    }
    if (is_array($audience)) {
      foreach ($audience as $entry) {
        if (is_string($entry) && hash_equals($clientId, $entry)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns a required string from the effective configuration.
   *
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   * @param string $key
   *   The configuration key.
   *
   * @return string
   *   The value.
   */
  private function getRequiredString(array $configuration, string $key): string {
    $value = $configuration[$key] ?? NULL;
    if (!is_string($value) || trim($value) === '') {
      throw new \InvalidArgumentException(sprintf('The ProConnect setting "%s" must be configured in settings.php.', $key));
    }

    return trim($value);
  }

  /**
   * Normalizes a relative or absolute URI into an HTTPS URL.
   *
   * @param string $pathOrUri
   *   The path or URI.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return string
   *   The absolute URI.
   */
  private function buildAbsoluteUri(string $pathOrUri, array $configuration): string {
    if (preg_match('@^https?://@i', $pathOrUri) === 1) {
      return $this->normalizeAbsoluteUri($pathOrUri);
    }

    $baseUri = $this->normalizeBaseUri($this->getRequiredString($configuration, 'domain'));
    $basePath = rtrim((string) parse_url($baseUri, PHP_URL_PATH), '/');
    $normalizedPath = '/' . ltrim($pathOrUri, '/');

    if ($basePath !== '' && str_starts_with($normalizedPath . '/', $basePath . '/')) {
      $normalizedPath = substr($normalizedPath, strlen($basePath)) ?: '';
      if ($normalizedPath === '') {
        return $baseUri;
      }
    }

    return $baseUri . $normalizedPath;
  }

  /**
   * Builds the discovery URI from the effective configuration.
   *
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   */
  private function buildDiscoveryUri(array $configuration): string {
    if (!empty($configuration['issuer_url']) && is_string($configuration['issuer_url'])) {
      return $this->normalizeBaseUri($configuration['issuer_url']) . '/.well-known/openid-configuration';
    }

    return $this->buildAbsoluteUri((string) ($configuration['discovery_path'] ?? self::DEFAULT_DISCOVERY_PATH), $configuration);
  }

  /**
   * Normalizes an absolute URI.
   */
  private function normalizeAbsoluteUri(string $uri): string {
    if (preg_match('@^https?://@i', $uri) !== 1) {
      $uri = 'https://' . ltrim($uri, '/');
    }

    return rtrim($uri, '/');
  }

  /**
   * Normalizes a base URI.
   */
  private function normalizeBaseUri(string $uri): string {
    if (preg_match('@^https?://@i', $uri) !== 1) {
      $uri = 'https://' . ltrim($uri, '/');
    }

    return rtrim($uri, '/');
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
      throw new \InvalidArgumentException('Unable to decode the ProConnect base64url payload.');
    }

    return $decoded;
  }

  /**
   * Ensures a value is a non-empty string.
   */
  private function requireNonEmptyString(mixed $value, string $message): string {
    if (!is_string($value) || trim($value) === '') {
      throw new \RuntimeException($message);
    }

    return trim($value);
  }

  /**
   * Returns whether the payload resembles a JWT.
   */
  private function looksLikeJwt(string $payload): bool {
    return preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $payload) === 1;
  }

  /**
   * Maps JWT algorithms to OpenSSL constants.
   */
  private function getOpenSslAlgorithm(string $algorithm): int {
    return match ($algorithm) {
      'RS256', 'ES256' => OPENSSL_ALGO_SHA256,
      'RS384', 'ES384' => OPENSSL_ALGO_SHA384,
      'RS512', 'ES512' => OPENSSL_ALGO_SHA512,
      default => throw new \InvalidArgumentException(sprintf('The ProConnect algorithm "%s" is not supported.', $algorithm)),
    };
  }

  /**
   * Converts a JOSE ECDSA signature to DER for OpenSSL.
   */
  private function convertJoseEcdsaSignatureToDer(string $algorithm, string $signature): string {
    $componentLength = match ($algorithm) {
      'ES256' => 32,
      'ES384' => 48,
      'ES512' => 66,
      default => throw new \InvalidArgumentException(sprintf('The ProConnect algorithm "%s" is not supported.', $algorithm)),
    };

    if (strlen($signature) !== ($componentLength * 2)) {
      throw new \RuntimeException('The ProConnect ECDSA signature length is invalid.');
    }

    $r = substr($signature, 0, $componentLength);
    $s = substr($signature, $componentLength);

    return $this->asn1Sequence(
      $this->asn1Integer($r) .
      $this->asn1Integer($s)
    );
  }

  /**
   * Builds an ASN.1 INTEGER from raw bytes.
   */
  private function asn1Integer(string $value): string {
    $value = ltrim($value, "\x00");
    if ($value === '') {
      $value = "\x00";
    }
    if ((ord($value[0]) & 0x80) !== 0) {
      $value = "\x00" . $value;
    }

    return "\x02" . $this->asn1Length(strlen($value)) . $value;
  }

  /**
   * Builds an ASN.1 SEQUENCE.
   */
  private function asn1Sequence(string $value): string {
    return "\x30" . $this->asn1Length(strlen($value)) . $value;
  }

  /**
   * Builds an ASN.1 BIT STRING.
   */
  private function asn1BitString(string $value): string {
    return "\x03" . $this->asn1Length(strlen($value) + 1) . "\x00" . $value;
  }

  /**
   * Builds an ASN.1 OBJECT IDENTIFIER.
   */
  private function asn1ObjectIdentifier(string $derBody): string {
    return "\x06" . $this->asn1Length(strlen($derBody)) . $derBody;
  }

  /**
   * Builds an ASN.1 NULL value.
   */
  private function asn1Null(): string {
    return "\x05\x00";
  }

  /**
   * Encodes an ASN.1 length.
   */
  private function asn1Length(int $length): string {
    if ($length < 0x80) {
      return chr($length);
    }

    $encoded = '';
    while ($length > 0) {
      $encoded = chr($length & 0xFF) . $encoded;
      $length >>= 8;
    }

    return chr(0x80 | strlen($encoded)) . $encoded;
  }

  /**
   * Converts DER bytes to PEM.
   */
  private function toPem(string $der, string $label): string {
    return sprintf(
      "-----BEGIN %s-----\n%s\n-----END %s-----\n",
      $label,
      rtrim(chunk_split(base64_encode($der), 64, "\n"), "\n"),
      $label,
    );
  }

  /**
   * Builds a cache ID for ProConnect metadata.
   *
   * @param string $suffix
   *   The cache suffix.
   * @param array<string, mixed> $configuration
   *   Effective client configuration.
   *
   * @return string
   *   The cache ID.
   */
  private function buildCacheId(string $suffix, array $configuration): string {
    return 'proconnect.' . $suffix . '.' . hash('sha256', serialize([
      $configuration['domain'] ?? NULL,
      $configuration['issuer_url'] ?? NULL,
      $configuration['issuer'] ?? NULL,
      $configuration['discovery_path'] ?? NULL,
      $configuration['jwks_uri'] ?? NULL,
    ]));
  }

}
