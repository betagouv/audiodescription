<?php

namespace App\Security;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class OidcAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private OidcService $oidcService,
        private RouterInterface $router,
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private Security $security,
        private UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') == 'security_oidc_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $state = $request->get('state');
        $sessionState = $request->getSession()->get(OidcService::OIDC_AD_STATE);

        if ($state !== $sessionState) {
            throw new UnauthorizedHttpException();
        }

        $code = $request->get('code');

        try {
            $tokens = $this->oidcService->getTokens($code);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->error($e->getResponse()->getContent(throw: false));
        }

        $accessToken = $tokens['access_token'];

        $payload = $this->oidcService->decodeToken($accessToken);

        $userInfo = $this->oidcService->getUserInfo($accessToken);

        $user = $this->userRepository->findOneBy(['username' => $userInfo['name']]);
        if (null === $user) {
            $user = new User();
            $user->setUsername($userInfo['name']);
        }
        $user->setEmail($userInfo['email']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new selfValidatingPassport(
            new UserBadge($user->getUsername()),
        );
    }

    /**
     * @inheritDoc
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info(
            sprintf('User %s logged in with success', $token->getUser()?->getUserIdentifier()),
            context: ['scope' => 'AUTH']
        );
        return new RedirectResponse($this->router->generate('admin_movie_index'));
    }

    /**
     * @inheritDoc
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->info(
            sprintf('Error while logging in: %s', $exception->getMessage()),
            context: ['scope' => 'AUTH']
        );
        return new RedirectResponse($this->router->generate('security_login'));
    }
}
