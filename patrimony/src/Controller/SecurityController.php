<?php

namespace App\Controller;

use App\Security\OidcService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'security_')]
class SecurityController extends AbstractController
{

    #[Route('/login', name: 'login')]
    public function loginAction(
        OidcService $oidcService
    ): Response
    {
        $oidcUrl = $oidcService->generateLoginUrl();
        return $this->render('security/login.html.twig', ['oidcUrl' => $oidcUrl]);
    }

    #[Route(path: '/oidc/callback', name: 'oidc_callback')]
    public function oidcCallback(): Response
    {
        return new RedirectResponse('security_login');
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new UnauthorizedHttpException(challenge: '');
    }
}