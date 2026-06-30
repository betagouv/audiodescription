<?php

declare(strict_types=1);

namespace Drupal\proconnect\Service;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\user\UserInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Handles the ProConnect pre-authorization access checks.
 */
final class ProConnectPreAuthorizeHandler {
  use StringTranslationTrait;
  public function __construct(
    private readonly StateInterface $state,
    private readonly OpenIDConnectSessionInterface $openIdConnectSession,
    private readonly MessengerInterface $messenger,
    private readonly LoggerInterface $logger,
    private readonly ProConnectUserManager $userManager,
  ) {}

  /**
   * Applies the email-domain access control before Drupal authorization.
   *
   * @param \Drupal\user\UserInterface|bool $account
   *   The preloaded Drupal account, if one already exists.
   * @param array<string, mixed> $context
   *   The OpenID Connect authorization context.
   *
   * @return \Drupal\user\UserInterface|bool
   *   The authorized account.
   * @throws \Random\RandomException
   */
  public function preAuthorize(UserInterface|bool $account, array $context): UserInterface|bool {
    if (($context['plugin_id'] ?? NULL) !== 'proconnect') {
      return TRUE;
    }

    $userinfo = isset($context['userinfo']) && is_array($context['userinfo']) ? $context['userinfo'] : [];
    $email = proconnect_normalize_email($userinfo['email'] ?? NULL);
    if ($email !== NULL) {
      $userinfo['email'] = $email;
    }

    $allowedDomains = proconnect_parse_domains(
      $this->state->get('proconnect.allowed_domains', []),
    );
    $domain = proconnect_extract_domain($email);

    if (!proconnect_is_domain_allowed($domain, $allowedDomains)) {
      $url = Url::fromRoute('proconnect.access_denied')->toString();

      $this->openIdConnectSession->saveTargetLinkUri($url);

      $this->logger->warning(
        'Denied ProConnect access for {email} (domain: {domain}). Allowed email domains: {allowed_domains}.',
        [
          'email' => $email ?? 'unknown',
          'domain' => $domain ?? 'invalid',
          'allowed_domains' => implode(', ', $allowedDomains) ?: 'none',
        ],
      );

      return FALSE;
    }
    if ($account instanceof UserInterface) {
      return $account->isBlocked() ? FALSE : $account;
    }

    try {
      return $this->userManager->loadOrCreateUser($userinfo);
    }
    catch (InvalidArgumentException | RuntimeException $exception) {
      $this->logger->error('Unable to authorize the ProConnect account: {message}', [
        'message' => $exception->getMessage(),
      ]);
      return FALSE;
    }
  }

}
