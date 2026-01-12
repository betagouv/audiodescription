<?php

namespace App\Controller\Admin;

use App\Repository\MovieRepository;
use App\Service\NewsletterContentGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'newsletter_')]
class NewsletterController extends AbstractController
{
    #[Route('/newsletter', name: 'newsletter')]
    public function newsletter(): Response
    {

        return $this->render('newsletter/newsletter.html.twig');
    }

    #[Route('/newsletter/generator', name: 'generator')]
    public function generator(
      NewsletterContentGenerator $contentGenerator
    ): Response
    {
      // Le service fait tout le travail
      $data = $contentGenerator->prepareNewsletterData();

      return $this->render('newsletter/generator.html.twig', $data);
    }
}