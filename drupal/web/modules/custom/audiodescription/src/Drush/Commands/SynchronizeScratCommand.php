<?php

namespace Drupal\audiodescription\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\audiodescription\EntityManager\ActorManager;
use Drupal\audiodescription\EntityManager\GenreManager;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\Entity\Media;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class SynchronizeScratCommand extends DrushCommands {

  /**
   * Constructs an SynchronizeScratCommand object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ClientInterface $httpClient,
    protected null|LoggerInterface $logger,
    private GenreManager $genreManager,
    private ActorManager $actorManager,
    private FileSystemInterface $fileSystem,
    private FileRepositoryInterface $fileRepository,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('logger.channel.default'),
      $container->get('audiodescription.manager.genre'),
      $container->get('audiodescription.manager.actor'),
      $container->get('file_system'),
      $container->get('file.repository')
    );
  }

  /**
   * Synchronize AD database with Scrat database.
   */
  #[CLI\Command(name: 'ad:synchronize:scrat', aliases: ['adss'])]
  #[CLI\Usage(name: 'ad:synchronize:scrat', description: 'Synchronize AD database with Scrat database.')]
  public function import(): void {
    try {
      $entityQuery = $this->entityTypeManager->getStorage('node')->getQuery();
      $query = $entityQuery
        ->condition('type', 'movie')
        ->notExists('field_is_complete')
        ->accessCheck(TRUE);

      $total_nb = $query->count()->execute();
      $pages_nb = $this->calculateTotalPages($total_nb);

      for ($page = 0; $page < $pages_nb; $page++) {
        $entityQuery = $this->entityTypeManager->getStorage('node')->getQuery();
        $query = $entityQuery
          ->condition('type', 'movie')
          ->notExists('field_is_complete')
          ->range($page * 1000, ($page * 1000) + 1000)
          ->accessCheck(TRUE);

        $nids = $query->execute();

        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

        foreach ($nodes as $node) {
          if (isset($node->field_visa_number->value) && !empty($node->field_visa_number->value)) {
            $visaNumber = $node->field_visa_number->value;
            $url = "https://scrat.corfm.at/requests/films/" . $visaNumber;

            try {
              // Faire une requête GET à l'API.
              $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                  'Content-Type' => 'application/json',
                  'x-auth-token' => 'czcCLj5jJ9pfl8XdjkYA&miMWun4Kz4ZvYe?5i1w',
                ],
              ]);

              $data = json_decode($response->getBody()->getContents(), TRUE);

              $node->set('title', $data['title']);
              $node->set('field_synopsis', $data['synopsis']);

              $genres = [];
              foreach ($data['genres'] as $genre) {
                $genres[] = $this->genreManager->provide(trim($genre));
              }
              $genresData = array_map(function ($genre) {
                return ['target_id' => $genre->id()];
              }, $genres);
              $node->set('field_genres', $genresData);

              $actors = [];
              foreach ($data['actors'] as $actor) {
                $role = isset($actor['role']) ?? '';
                $actors[] = $this->actorManager->provide(trim($actor['name']), $role);
              }
              $actorsData = array_map(function ($actor) {
                return ['target_id' => $actor->id()];
              }, $actors);
              $node->set('field_actors', $actorsData);

              // Poster.
              try {
                $posterUrl = $data['cover'];
                $response = $this->httpClient->get($posterUrl, ['stream' => TRUE]);
                if ($response->getStatusCode() === 200) {
                  $fileName = basename($posterUrl);
                  $fileData = $response->getBody()->getContents();
                  $destination = 'public://posters/' . $fileName;
                  $file = $this->fileRepository->writeData($fileData, $destination, FileExists::Replace);

                  $media = Media::create([
                    'bundle' => 'image',
                    'name' => $node->title->value,
                    'field_media_image' => [
                      'target_id' => $file->id(),
                      'alt' => $node->title->value,
                      'title' => $node->title->value,
                    ],
                  ]);

                  $media->save();

                  $node->set('field_poster', [
                    'target_id' => $media->id(),
                  ]);
                }
              }
              catch (\Exception $e) {
                /*
                 * $this->logger->error(
                 * 'Erreur lors du téléchargement de l\'affiche: @message',
                 * ['@message' => $e->getMessage()]
                 * );
                 */
                dump("Erreur download poster");
              }

              $node->set('field_is_complete', TRUE);
              $node->save();

              dump('************************************');
              dump($node->title->value . ' synchronized');
              dump('************************************');

            }
            catch (\Exception $e) {
              /*
               * $this->logger->error(
               * 'Erreur lors de l\'appel à l\'API: @message',
               * ['@message' => $e->getMessage()]
               * );
               */
              dump($node->title->value . ' non trouvé dans l\'API Scrat');
            }
          }
        }

        // Clear entities cache.
        $this->entityTypeManager->clearCachedDefinitions();
      }
    }
    catch (\Throwable $t) {
      // $this->logger()->error('Erreur fatale : ' . $t->getMessage());
      dump("Erreur fatale");
    };
  }

  /**
   * Calculate the total number of pages in the queue.
   */
  protected function calculateTotalPages($total, $results_per_page = 1000) {
    return (int) ceil($total / $results_per_page);
  }

}
