<?php

declare(strict_types=1);

namespace Drupal\proconnect\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\proconnect\Service\ProConnectAccessDeniedContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the dedicated ProConnect access denied page.
 */
final class AccessDeniedController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Builds the controller.
   */
  public function __construct(
    private readonly ProConnectAccessDeniedContentManager $accessDeniedContentManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Creates the controller from the container.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('proconnect.access_denied_content_manager'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
      $container->get('messenger'),
    );
  }

  /**
   * Builds the ProConnect access denied page.
   *
   * @return array<string, mixed>
   *   The render array.
   */
  public function display(): array {
    $this->removeOpenIdErrorMessages();

    $cacheability = new CacheableMetadata();

    $content = $this->accessDeniedContentManager->getContent();

    $imageFid = (int) ($content['image_fid'] ?? 0);

    $imageSrc = NULL;
    $imageAlt = '';

    if ($imageFid > 0) {
      [$imageSrc, $imageAlt] = $this->buildFileImageData(
        $imageFid,
        $cacheability,
      );
    }

    $body = NULL;

    if (!empty($content['message'])) {
      $body = [
        '#type' => 'processed_text',
        '#text' => (string) $content['message'],
        '#format' => (string) (
          $content['message_format'] ?? 'full_html'
        ),
      ];
    }

    $build = [
      '#theme' => 'proconnect_access_denied',
      '#page_title' => !empty($content['title'])
        ? (string) $content['title']
        : (string) $this->t('Accès refusé'),

      '#body' => $body,

      '#image_src' => $imageSrc,
      '#image_alt' => $imageAlt,
    ];

    $cacheability->addCacheTags([ProConnectAccessDeniedContentManager::CACHE_TAG]);
    $cacheability->applyTo($build);

    return $build;
  }

  /**
   * Removes generic OpenID Connect messages.
   */
  private function removeOpenIdErrorMessages(): void {
    $messages = $this->messenger->messagesByType('error');

    foreach ($messages as $message) {
      $messageText = (string) $message;

      if (
        str_contains($messageText, 'Logging in with ProConnect') ||
        str_contains($messageText, 'could not be completed')
      ) {
        $this->messenger->deleteByType('error');
        break;
      }
    }
  }

  /**
   * Builds image render data from file entity.
   *
   * @param int $fileId
   *   The file ID.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Cacheability metadata.
   *
   * @return array{0: ?string, 1: string}
   *   Image source and alt.
   */
  private function buildFileImageData(
    int $fileId,
    CacheableMetadata $cacheability,
  ): array {
    if ($fileId <= 0) {
      return [NULL, ''];
    }

    $file = $this->entityTypeManager
      ->getStorage('file')
      ->load($fileId);

    if (!$file instanceof FileInterface) {
      return [NULL, ''];
    }

    if ($file->isTemporary()) {
      return [NULL, ''];
    }

    $cacheability->addCacheableDependency($file);

    $imageSrc = $this->fileUrlGenerator->generateString(
      $file->getFileUri(),
    );

    $imageAlt = $file->getFilename() !== ''
      ? $file->getFilename()
      : (string) $this->t('Access denied');

    return [
      $imageSrc,
      $imageAlt,
    ];
  }

}
