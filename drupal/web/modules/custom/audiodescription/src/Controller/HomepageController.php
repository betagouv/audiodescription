<?php

namespace Drupal\audiodescription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\block\Entity\Block;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the homepage content.
 */
class HomepageController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new PocController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigPagesLoaderServiceInterface $configPagesLoader, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configPagesLoader = $configPagesLoader;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config_pages.loader'),
      $container->get('form_builder'),
    );
  }

  /**
   * Provides the render array for the homepage.
   *
   * @return array
   *   A render array representing the content of the homepage.
   */
  public function build() {
    $config_pages = $this->configPagesLoader;
    $homepage = $config_pages->load('homepage');

    $entity = $homepage->get('field_header_cta')->referencedEntities()[0];
    $cta = [
      'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
      'text' => $entity->get('field_pg_link')->first()->title,
      'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
      'external' => ($entity->get('field_pg_is_external')->value == TRUE),
      'style' => $entity->get('field_pg_style')->value,
    ];

    // Get partners.
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'partner')
      ->exists('field_taxo_logo_black_square')
      ->accessCheck(FALSE);
    $term_ids = $query->execute();

    $header = [
      'title' => $homepage->get('field_header_title')->value,
      'chapo' => $homepage->get('field_header_chapo')->value,
      'has_search_bar' => $homepage->get('field_header_with_search_bar')->value,
      'cta' => $cta,
      'stats' => $this->countMoviesWithAtLeastOneSolution(),
      'partners' => Term::loadMultiple($term_ids),
      'image' => $homepage->get('field_header_image')->entity->field_media_image->entity->uri->value
    ];

    $ctas = [];
    $entities_pg_cta = $homepage->get('field_infos_ctas')->referencedEntities();

    foreach ($entities_pg_cta as $entity) {
      $ctas[] = [
        'url' => $entity->get('field_pg_link')->first()->getUrl()->toString(),
        'text' => $entity->get('field_pg_link')->first()->title,
        'target' => ($entity->get('field_pg_is_external')->value == TRUE) ? 'blank' : 'self',
        'external' => ($entity->get('field_pg_is_external')->value == TRUE),
        'style' => $entity->get('field_pg_style')->value,
      ];
    }

    $infos = [
      'title' => $homepage->get('field_infos_title')->value,
      'description' => $homepage->get('field_infos_description')->value,
      'image' => $homepage->get('field_infos_image')->entity->field_media_image->entity->uri->value,
      'ctas' => $ctas,
    ];

    $about = [
      'title' => $homepage->get('field_about_title')->value,
      'description' => $homepage->get('field_about_description')->value,
      'icon' => $homepage->get('field_about_icon')->entity->field_media_image->entity->uri->value,
      'pre_contact' => $homepage->get('field_about_pre_contact')->value,
      'email' => $homepage->get('field_about_email')->value,
    ];

    $block = Block::load('ad_hp_highlighted_collections_block');

    $highlightedCollections = [];
    if ($block) {
      $highlightedCollections = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }

    $block = Block::load('ad_hp_collections_block');

    $collections = [];
    if ($block) {
      $collections = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }

    $search_form = $this->formBuilder->getForm('Drupal\audiodescription\Form\SimpleMovieSearchForm', 'lg');

    $block = Block::load('ad_hp_last_movies_block');

    $lastMovies = [];
    if ($block) {
      $lastMovies = $this->entityTypeManager
        ->getViewBuilder('block')
        ->view($block);
    }

    return [
      '#theme' => 'homepage',
      '#header' => $header,
      '#infos' => $infos,
      '#about' => $about,
      '#highlighted_collections' => $highlightedCollections,
      '#collections' => $collections,
      '#last_movies' => $lastMovies,
      '#search_form' => $search_form,
    ];
  }

  private function countMoviesWithAtLeastOneSolution():int {
    $current_date = new DrupalDateTime('now');

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'movie') // Récupérer uniquement les nodes de type "movie"
      ->exists('field_offers') // Vérifier que le champ 'field_offers' est renseigné (c'est une référence à des Paragraphes)
      ->accessCheck(FALSE); // Ignorer les vérifications d'accès si nécessaire
    $movie_ids = $query->execute();

    // Initialisation du compteur
    $movie_ids_with_partners = [];

    foreach ($movie_ids as $movie_id) {
      $movie = Node::load($movie_id);


      $offers = $movie->get('field_offers')->referencedEntities();
      foreach ($offers as $offer) {
        if ($offer->getType() == 'pg_offer') {
          $partners = $offer->get('field_pg_partners')->referencedEntities();

          foreach ($partners as $partner) {
            if ($partner->getType() == 'pg_partner') {
              $start_date = $partner->get('field_pg_start_rights')->value;
              $end_date = $partner->get('field_pg_end_rights')->value;

              if ($start_date && $end_date) {
                $start_date = new DrupalDateTime($start_date);
                $end_date = new DrupalDateTime($end_date);

                if ($start_date->getTimestamp() < $current_date->getTimestamp() &&
                  $end_date->getTimestamp() > $current_date->getTimestamp()) {
                  if (!in_array($movie->id(), $movie_ids_with_partners)) {
                    $movie_ids_with_partners[] = $movie->id();
                  }
                }
              }
            }
          }
        }
      }
    }

    return count($movie_ids_with_partners);
  }

}
