<?php

declare(strict_types=1);

namespace Drupal\proconnect\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserNameValidator;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Maps ProConnect identities to Drupal users.
 */
final class ProConnectUserManager {

  private const EXCEPTION_PREFIX = 'Semaphore - ProConnect: ';


  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly UserNameValidator $userNameValidator,
    protected readonly TransliterationInterface $transliteration,
  ) {}

  /**
   * Loads an existing user by email or creates a new one from ProConnect data.
   *
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   *
   * @return \Drupal\user\UserInterface
   * @throws \Random\RandomException
   */
  public function loadOrCreateUser(array $claims): UserInterface {
    $email = $this->extractEmail($claims);
    $existing = $this->loadByEmail($email);
    if ($existing instanceof UserInterface) {
      if ($existing->isBlocked()) {
        throw new RuntimeException($this->buildExceptionMessage('Le compte Semaphore lié à cette identité ProConnect est bloqué.'));
      }
      return $this->updateProfileFields($existing, $claims);
    }
    return $this->createUser($email, $claims);
  }

  /**
   * Loads an account by email.
   *
   * @param string $email
   *   The email address.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user or NULL if not found.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadByEmail(string $email): ?UserInterface {
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
    $account = $accounts !== [] ? reset($accounts) : FALSE;

    return $account instanceof UserInterface ? $account : NULL;
  }

  /**
   * Creates a new Drupal account from validated ProConnect claims.
   *
   * @param string $email
   *   The user email address.
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   *
   * @return \Drupal\user\UserInterface
   *   The created user entity.
   *
   * @throws \Random\RandomException
   */
  private function createUser(string $email, array $claims): UserInterface {
    $username = $this->generateUniqueUsername($claims, $email);

    $account = $this->entityTypeManager->getStorage('user')->create([
      'name' => $username,
      'mail' => $email,
      'init' => $email,
      'status' => 1,
      'pass' => bin2hex(random_bytes(32)),
    ]);

    if (!$account instanceof UserInterface) {
      throw new \RuntimeException($this->buildExceptionMessage('Impossible de créer le compte Drupal local à partir de l’identité ProConnect.'));
    }

    $this->applyProfileFields($account, $claims);

    try {
      $account->save();
    }
    catch (EntityStorageException $exception) {
      throw new RuntimeException($this->buildExceptionMessage('Impossible d’enregistrer le nouveau compte Drupal Intranet Culture créé via ProConnect.'), 0, $exception);
    }

    $this->logger->notice('Created a Drupal user from ProConnect for uid {uid}.', [
      'uid' => $account->id(),
    ]);

    return $account;
  }

  /**
   * Synchronizes Drupal profile fields from ProConnect claims when available.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   *
   * @return \Drupal\user\UserInterface
   *   The updated user account.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function updateProfileFields(UserInterface $account, array $claims): UserInterface {
    if (!$this->applyProfileFields($account, $claims)) {
      return $account;
    }
    try {
      $account->save();
    }
    catch (EntityStorageException $exception) {
      throw new RuntimeException(
        $this->buildExceptionMessage('Impossible de mettre à jour le profil Drupal local à partir des données ProConnect.'),
        0,
        $exception
      );
    }

    $this->logger->notice('Updated Drupal profile fields from ProConnect for uid {uid}.', [
      'uid' => $account->id(),
    ]);

    return $account;
  }
  /**
   * Extracts and validates the email claim.
   *
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   */
  private function extractEmail(array $claims): string {
    $email = $claims['email'] ?? NULL;
    if (!is_string($email) || $email === '') {
      throw new InvalidArgumentException($this->buildExceptionMessage('Le champ email est obligatoire pour la création du compte Drupal Intranet Culture.'));
    }

    $email = mb_strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException($this->buildExceptionMessage('L’adresse email renvoyée par ProConnect est invalide.'));
    }

    return $email;
  }

  /**
   * Generates a unique Drupal username.
   *
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   * @param string $email
   *   The user email address.
   *
   * @return string
   *   A unique username.
   */
  private function generateUniqueUsername(array $claims, string $email): string {
    $baseCandidate = $this->sanitizeUsername($this->buildPreferredUsername($claims, $email));
    if (!$this->isValidUsername($baseCandidate)) {
      $baseCandidate = 'proconnect.' . substr(hash('sha256', $email), 0, 12);
    }

    $candidate = $baseCandidate;
    $index = 1;
    while (!$this->isUsernameAvailable($candidate)) {
      $suffix = '.' . $index;
      $candidate = $this->truncateUsername($baseCandidate, UserInterface::USERNAME_MAX_LENGTH - strlen($suffix)) . $suffix;
      $index++;
    }

    if (!$this->isValidUsername($candidate)) {
      throw new RuntimeException($this->buildExceptionMessage('Le nom d’utilisateur généré pour le compte ProConnect n’est pas accepté par Drupal.'));
    }

    return $candidate;
  }

  /**
   * Builds the preferred username from claims and falls back to the email.
   *
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   * @param string $email
   *   The user email address.
   *
   * @return string
   *   The preferred username.
   */
  private function buildPreferredUsername(array $claims, string $email): string {
    $parts = [];
    foreach (['given_name', 'usual_name'] as $claimName) {
      if (isset($claims[$claimName]) && is_string($claims[$claimName])) {
        $value = trim($claims[$claimName]);
        if ($value !== '') {
          $parts[] = $value;
        }
      }
    }

    if ($parts === []) {
      return strstr($email, '@', TRUE) ?: $email;
    }

    return implode('.', $parts);
  }

  /**
   * Applies ProConnect identity fields to the Drupal user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   *
   * @return bool
   *   TRUE when at least one field changed.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function applyProfileFields(UserInterface $account, array $claims): bool {
    $changed = FALSE;
    $firstName = $this->extractOptionalClaim($claims, 'given_name');
    $lastName = $this->extractOptionalClaim($claims, 'usual_name');
    $fullName = trim(implode(' ', array_filter([$firstName, $lastName])));

    $changed = $this->applyStringField($account, 'field_name', $firstName) || $changed;
    $changed = $this->applyStringField($account, 'field_last_name', $lastName) || $changed;
    $changed = $this->applyStringField($account, 'field_full_name', $fullName !== '' ? $fullName : NULL) || $changed;

    return $changed;
  }

  /**
   * Applies a string value to a user field when the field exists.
   */
  private function applyStringField(UserInterface $account, string $fieldName, ?string $value): bool {
    if ($value === NULL || !$account->hasField($fieldName)) {
      return FALSE;
    }

    $currentValue = $account->get($fieldName)->value;
    if ($currentValue === $value) {
      return FALSE;
    }

    $account->set($fieldName, $value);
    return TRUE;
  }

  /**
   * Returns a trimmed optional string claim.
   *
   * @param array<string, mixed> $claims
   *   Validated ProConnect claims.
   * @param string $claimName
   *   The claim name.
   *
   * @return string|null
   *   The claim value or NULL.
   */
  private function extractOptionalClaim(array $claims, string $claimName): ?string {
    $value = $claims[$claimName] ?? NULL;
    if (!is_string($value)) {
      return NULL;
    }

    $value = trim($value);

    return $value !== '' ? $value : NULL;
  }

  /**
   * Converts the preferred username into a Drupal-compatible machine string.
   */
  private function sanitizeUsername(string $username): string {
    $username = trim($this->transliteration->transliterate($username, 'en', '.'), " \t\n\r\0\x0B");
    $username = mb_strtolower($username);
    $username = preg_replace('/[^a-z0-9@+_.\'-]+/', '.', $username) ?? '';
    $username = trim($username, ". '-_");

    if ($username === '') {
      return 'proconnect';
    }

    return $this->truncateUsername($username, UserInterface::USERNAME_MAX_LENGTH);
  }

  /**
   * Truncates a username candidate without leaving trailing separators.
   */
  private function truncateUsername(string $username, int $maxLength): string {
    $username = mb_substr($username, 0, $maxLength);
    $username = rtrim($username, ". '-_");

    return $username !== '' ? $username : 'proconnect';
  }

  /**
   * Checks whether the generated username passes Drupal validation rules.
   */
  private function isValidUsername(string $username): bool {
    return count($this->userNameValidator->validateName($username)) === 0;
  }

  /**
   * Checks whether no existing account already uses the username.
   */
  private function isUsernameAvailable(string $username): bool {
    return $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]) === [];
  }

  /**
   * Adds the Semaphore Intranet Culture prefix to exception messages.
   */
  private function buildExceptionMessage(string $message): string {
    return self::EXCEPTION_PREFIX . $message;
  }

}
