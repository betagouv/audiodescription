<?php

namespace App\Controller\Admin;

use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'newsletter_')]
class NewsletterController extends AbstractController
{
    const int TOTAL_MOVIES = 16;
    const int MAX_RANDOM_MOVIES = 4;
    const int MAX_NEW_ARTE_MOVIES = 2;
    const int MAX_NEW_TF1_MOVIES = 1;
    const int MAX_NEW_MOVIES = 6;
    const int MAX_NEAR_END_MOVIES = 7;

    #[Route('/newsletter', name: 'newsletter')]
    public function newsletter(): Response
    {

        return $this->render('newsletter/newsletter.html.twig');
    }

    #[Route('/newsletter/generator', name: 'generator')]
    public function generator(
      MovieRepository $movieRepository
    ): Response
    {
      $newMoviesArte = $movieRepository->findNewFreeMovies('ARTE');
      shuffle($newMoviesArte);
      $newMoviesArte = array_splice($newMoviesArte, 0,self::MAX_NEW_ARTE_MOVIES);

      $newMoviesTf1 = $movieRepository->findNewFreeMovies('TF1');
      shuffle($newMoviesTf1);
      $newMoviesTf1 = array_splice($newMoviesTf1, 0,self::MAX_NEW_TF1_MOVIES);

      $length = self::MAX_NEW_MOVIES - count($newMoviesArte) - count($newMoviesTf1);

      $newMoviesFranceTv = $movieRepository->findNewFreeMovies('FRANCE_TV');
      shuffle($newMoviesFranceTv);
      $newMoviesFranceTv = array_splice($newMoviesFranceTv, 0,$length);

      $newMovies = array_merge($newMoviesArte, $newMoviesTf1, $newMoviesFranceTv);
      shuffle($newMovies);

      $nearEndMovies = $movieRepository->findNearEndFreeMovies($newMoviesArte, self::MAX_NEAR_END_MOVIES);

      $alreadySelectedMovies = array_merge($newMovies, $nearEndMovies);
      $randomMovies = $movieRepository->findNotSelectedFreeMovies($alreadySelectedMovies);
      shuffle($randomMovies);
      $randomMovies = array_splice($randomMovies, 0, self::MAX_RANDOM_MOVIES);

      $freeMoviesCount = $movieRepository->countFreeMovies();

      return $this->render(
        'newsletter/generator.html.twig',
        [
          'newMovies' => $newMovies,
          'nearEndMovies' => $nearEndMovies,
          'randomMovies' => $randomMovies,
          'freeMoviesCount' => $freeMoviesCount,
          'totalMoviesCount' => self::TOTAL_MOVIES
        ]
      );
    }
}