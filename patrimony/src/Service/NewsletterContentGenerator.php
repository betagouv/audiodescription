<?php

namespace App\Service;

use App\Repository\MovieRepository;
use DateTime;
use IntlDateFormatter;
use Twig\Environment;

class NewsletterContentGenerator
{
  private const int TOTAL_MOVIES = 16;
  private const int MAX_RANDOM_MOVIES = 4;
  private const int MAX_NEW_ARTE_MOVIES = 2;
  private const int MAX_NEW_TF1_MOVIES = 1;
  private const int MAX_NEW_MOVIES = 6;
  private const int MAX_NEAR_END_MOVIES = 7;

  public function __construct(
    private readonly MovieRepository $movieRepository,
    private readonly Environment $twig
  ) {}

  /**
   * Génère le contenu HTML de la newsletter hebdomadaire
   */
  public function generateWeeklyNewsletter(): string
  {
    $data = $this->prepareNewsletterData();

    return $this->twig->render('newsletter/generator.html.twig', $data);
  }

  /**
   * Prépare toutes les données pour la newsletter
   */
  public function prepareNewsletterData(): array
  {
    $newMoviesArte = $this->movieRepository->findNewFreeMovies('ARTE');
    shuffle($newMoviesArte);
    $newMoviesArte = array_splice($newMoviesArte, 0,self::MAX_NEW_ARTE_MOVIES);

    $newMoviesTf1 = $this->movieRepository->findNewFreeMovies('TF1');
    shuffle($newMoviesTf1);
    $newMoviesTf1 = array_splice($newMoviesTf1, 0,self::MAX_NEW_TF1_MOVIES);

    $length = self::MAX_NEW_MOVIES - count($newMoviesArte) - count($newMoviesTf1);

    $newMoviesFranceTv = $this->movieRepository->findNewFreeMovies('FRANCE_TV');
    shuffle($newMoviesFranceTv);
    $newMoviesFranceTv = array_splice($newMoviesFranceTv, 0,$length);

    $newMovies = array_merge($newMoviesArte, $newMoviesTf1, $newMoviesFranceTv);
    shuffle($newMovies);

    $nearEndMovies = $this->movieRepository->findNearEndFreeMovies($newMovies, self::MAX_NEAR_END_MOVIES);

    $alreadySelectedMovies = array_merge($newMovies, $nearEndMovies);
    $randomMovies = $this->movieRepository->findNotSelectedFreeMovies($alreadySelectedMovies);
    shuffle($randomMovies);
    $randomMovies = array_splice($randomMovies, 0, self::MAX_RANDOM_MOVIES);

    $freeMoviesCount = $this->movieRepository->countFreeMovies();

    return [
      'newMovies' => $newMovies,
      'nearEndMovies' => $nearEndMovies,
      'randomMovies' => $randomMovies,
      'freeMoviesCount' => $freeMoviesCount,
      'totalMoviesCount' => self::TOTAL_MOVIES,
    ];
  }
}