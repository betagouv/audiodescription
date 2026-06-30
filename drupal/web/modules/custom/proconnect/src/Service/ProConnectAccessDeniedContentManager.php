<?php

declare(strict_types=1);

namespace Drupal\proconnect\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\file\FileInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages the editorial content shown on the ProConnect access denied page.
 */
final class ProConnectAccessDeniedContentManager {

  public const CACHE_TAG = 'proconnect_access_denied_content';

  private const DEFAULT_TITLE = 'Accès refusé';

  public function __construct(
    private readonly StateInterface $state,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns the current editorial content from State.
   *
   * @return array{title: string, message: string, message_format: string, image_fid: int}
   */
  public function getContent(): array {
    return [
      'title' => (string) ($this->state->get('proconnect.access_denied.title') ?: self::DEFAULT_TITLE),
      'message' => (string) ($this->state->get('proconnect.access_denied.message') ?? ''),
      'message_format' => (string) ($this->state->get('proconnect.access_denied.message_format') ?: 'full_html'),
      'image_fid' => (int) ($this->state->get('proconnect.access_denied.image_fid', 0)),
    ];
  }

  /**
   * Saves the editorial content into State and invalidates the cache tag.
   *
   * @param string $title
   *   The configured title.
   * @param array<string, mixed> $body
   *   The formatted body value from the config form.
   * @param int $imageFid
   *   The uploaded image file ID.
   */
  public function saveContent(string $title, array $body, int $imageFid): void {
    $this->state->set('proconnect.access_denied.title', trim($title) ?: self::DEFAULT_TITLE);
    $this->state->set('proconnect.access_denied.message', (string) ($body['value'] ?? ''));
    $this->state->set('proconnect.access_denied.message_format', (string) ($body['format'] ?? 'full_html'));
    $this->state->set('proconnect.access_denied.image_fid', $imageFid);

    $this->markFilePermanent($imageFid);
    $this->cacheTagsInvalidator->invalidateTags([self::CACHE_TAG]);
  }

  /**
   * Ensures the selected image file stays permanent.
   */
  private function markFilePermanent(int $imageFid): void {
    if ($imageFid <= 0) {
      return;
    }

    $file = $this->entityTypeManager->getStorage('file')->load($imageFid);

    if (!$file instanceof FileInterface) {
      $this->logger->warning('ProConnect access denied: unable to load file ID {fid}.', ['fid' => $imageFid]);
      return;
    }

    $file->setPermanent();
    $file->save();
  }

}
