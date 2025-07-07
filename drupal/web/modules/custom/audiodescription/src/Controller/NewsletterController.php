<?php

namespace Drupal\audiodescription\Controller;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the newsletter pages content.
 */
class NewsletterController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * Constructs a new NewsletterController.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    ConfigPagesLoaderServiceInterface $configPagesLoader,
  ) {
    $this->formBuilder = $form_builder;
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('form_builder'),
      $container->get('config_pages.loader'),
    );
  }

  /**
   * Provides the render array for the newsletter subscription page.
   *
   * @return array
   *   A render array representing the content of the newsletter subscription page.
   */
  public function subscription() {
    $config_pages = $this->configPagesLoader;
    $newsletter = $config_pages->load('newsletter');

    $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\NewsletterSubscriptionForm');

    $title = 'L\'infolettre "Les films gratuits en audiodescription de la semaine"';

    $build = [
      '#theme' => 'newsletter_subscription',
      '#form' => $form,
      '#title' => $title,
      '#introduction' => $newsletter->get('field_newsletter_introduction')->value,
      '#rgpd' => $newsletter->get('field_newsletter_infos_rgpd')->value,
      '#unsubscribe' => $newsletter->get('field_newsletter_infos_unsub')->value,
    ];

    return $build;
  }

  /**
   * Provides the render array for the newsletter subscription page.
   *
   * @return array
   *   A render array representing the content of the newsletter subscription page.
   */
  public function confirmation() {
    $config_pages = $this->configPagesLoader;
    $newsletter = $config_pages->load('newsletter');

    $title = 'Votre inscription à l’infolettre a bien été prise en compte.';
    $text = $newsletter->get('field_news_confirm_text')->value;
    $entity = $newsletter->get('field_news_confirm_cta')->referencedEntities()[0];

    $cta = [
      'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
      'text' => $entity->get('field_pg_link')->first()->title,
      'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
      'external' => ($entity->get('field_pg_is_external')->value == TRUE),
      'style' => $entity->get('field_pg_style')->value,
    ];

    $underCta = $newsletter->get('field_news_confirm_under_cta')->value;

    $build = [
      '#theme' => 'newsletter_confirmation',
      '#title' => $title,
      '#text' => $text,
      '#cta' => $cta,
      '#under_cta' => $underCta,
    ];

    return $build;
  }

  /**
   * Provides the render array for the newsletter subscription page.
   *
   * @return array
   *   A render array representing the content of the newsletter subscription page.
   */
  public function unsubscription() {
    $config_pages = $this->configPagesLoader;
    $newsletter = $config_pages->load('newsletter');

    $form = $this->formBuilder->getForm('Drupal\audiodescription\Form\NewsletterUnsubscriptionForm');

    $title = 'Désinscription de la liste de diffusion "Les films gratuits en audiodescription"';

    $build = [
      '#theme' => 'newsletter_unsubscription',
      '#form' => $form,
      '#title' => $title,
      '#text' => $newsletter->get('field_news_unsub_text')->value,
    ];

    return $build;
  }

  public function unsubscriptionConfirmation() {
    $config_pages = $this->configPagesLoader;
    $newsletter = $config_pages->load('newsletter');

    $title = 'Votre désinscription a bien été prise en compte.';
    $text = $newsletter->get('field_news_unsub_confirm_text')->value;
    $entity = $newsletter->get('field_news_unsub_confirm_cta')->referencedEntities()[0];

    $cta = [
      'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
      'text' => $entity->get('field_pg_link')->first()->title,
      'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
      'external' => ($entity->get('field_pg_is_external')->value == TRUE),
      'style' => $entity->get('field_pg_style')->value,
    ];

    $build = [
      '#theme' => 'newsletter_unsubscription_confirmation',
      '#title' => $title,
      '#text' => $text,
      '#cta' => $cta,
    ];

    return $build;
  }

}
