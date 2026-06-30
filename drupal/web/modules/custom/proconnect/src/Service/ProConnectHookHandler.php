<?php

declare(strict_types=1);

namespace Drupal\proconnect\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Centralizes the remaining ProConnect hook logic.
 */
final class ProConnectHookHandler {

  public function __construct(
    private readonly AccountProxyInterface $currentUser,
    private readonly OpenIDConnectSessionInterface $openIdConnectSession,
    private readonly LoggerInterface $logger,
    private readonly ProConnectStateManager $stateManager,
  ) {}

  /**
   * Alters ProConnect userinfo before account authorization.
   *
   * @param array<string, mixed> $userinfo
   *   The userinfo payload.
   * @param array<string, mixed> $context
   *   The OpenID Connect context.
   */
  public function alterUserinfo(array &$userinfo, array $context): void {
    if (($context['plugin_id'] ?? NULL) !== 'proconnect') {
      return;
    }

    if (isset($userinfo['email']) && is_string($userinfo['email'])) {
      $userinfo['email'] = mb_strtolower(trim($userinfo['email']));
    }

    if (!empty($userinfo['preferred_username']) || !empty($userinfo['name'])) {
      return;
    }

    $parts = [];
    foreach (['given_name', 'usual_name'] as $claim) {
      if (isset($userinfo[$claim]) && is_string($userinfo[$claim])) {
        $value = trim($userinfo[$claim]);
        if ($value !== '') {
          $parts[] = $value;
        }
      }
    }

    if ($parts !== []) {
      $preferredUsername = implode('.', $parts);
      $userinfo['preferred_username'] = $preferredUsername;
      $userinfo['name'] = $preferredUsername;
    }
  }

  /**
   * Applies the post-authorization side effects for ProConnect.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param array<string, mixed> $context
   *   The OpenID Connect context.
   */
  public function postAuthorize(UserInterface $account, array $context): void {
    if (($context['plugin_id'] ?? NULL) !== 'proconnect') {
      return;
    }

    $this->stateManager->markAuthenticatedViaProConnect();
    $this->logger->notice('ProConnect user logged in successfully for uid {uid}.', [
      'uid' => $account->id(),
    ]);

    (new RedirectResponse('/'))->send();
  }

  /**
   * Adds ProConnect session state to page template variables.
   *
   * @param array<string, mixed> $variables
   *   Page preprocess variables.
   */
  public function preprocessPage(array &$variables): void {
    $variables['proconnect_authenticated'] = $this->currentUser->isAuthenticated()
      && $this->stateManager->isAuthenticatedViaProConnect();
  }

  /**
   * Clears ProConnect session artifacts when a user logs out.
   */
  public function handleUserLogout(AccountInterface $account): void {
    $this->stateManager->clearNonce();
    $this->stateManager->clearLogoutContext();
    $this->stateManager->clearAuthenticatedViaProConnect();

    $this->openIdConnectSession->retrieveIdToken(TRUE);
    $this->openIdConnectSession->retrieveAccessToken(TRUE);
    $this->openIdConnectSession->retrieveRefreshToken(TRUE);
    $this->openIdConnectSession->retrieveExpireToken(TRUE);
    $this->openIdConnectSession->retrieveStateToken(TRUE);
  }

}
