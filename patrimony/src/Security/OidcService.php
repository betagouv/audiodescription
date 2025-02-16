<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Random\RandomException;
use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OidcService
{
    public const string OIDC_AD_STATE = 'oidc_ad_state';

    private string $oidcAdBaseUrl;
    private string $oidcAdPublicUrl;
    private string $oidcAdClientId;
    private string $oidcAdRedirectBaseUrl;
    private string $oidcAdClientSecret;

    public function __construct(
        private HttpClientInterface $client,
        private RequestStack $requestStack,
        private ParameterBagInterface $parameterBag,
    ) {
        $this->oidcAdBaseUrl = $this->parameterBag->get('oidc_ad.base_url');
        $this->oidcAdPublicUrl = $this->parameterBag->get('oidc_ad.public_url');
        $this->oidcAdClientId = $this->parameterBag->get('oidc_ad.client_id');
        $this->oidcAdRedirectBaseUrl = $this->parameterBag->get('oidc_ad.redirect_base_url');
        $this->oidcAdClientSecret = $this->parameterBag->get('oidc_ad.client_secret');
        $this->oidcAdPublicKey = $this->parameterBag->get('oidc_ad.public_key');
    }

    /**
     * @return string
     * @throws ClientExceptionInterface
     * @throws RandomException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function generateLoginUrl(): string
    {
        $uniqid = uniqid();

        $session = $this->requestStack->getSession();
        $session->set(self::OIDC_AD_STATE, $uniqid);

        $oidcUrl = $this->oidcAdPublicUrl .
            '/oauth/authorize?response_type=code&client_id=' . $this->oidcAdClientId .
            '&redirect_uri=' . $this->oidcAdRedirectBaseUrl . '/oidc/callback' .
            '&scope=admin&state=' . $uniqid;

        return $oidcUrl;
    }


    /**
     * @return array<mixed>
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getTokens(string $code): array
    {
        $url = $this->oidcAdBaseUrl . '/oauth/token';

        $response = $this->client
            ->withOptions([
                'verify_host' => false,
                'verify_peer' => false,
                'headers' => [
                    "Content-Type" => "application/json",
                ],

                'body' => [
                    'code' => $code,
                    'client_id' => $this->oidcAdClientId,
                    'client_secret' => $this->oidcAdClientSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $this->oidcAdRedirectBaseUrl . '/oidc/callback',
                ],
            ])
            ->request(
                'POST',
                $url
            );

        return $response->toArray();
    }

    /**
     * @param string $token
     * @return stdClass
     */
    public function decodeToken(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->oidcAdPublicKey, 'RS256'));
    }

    /**
     * @return array<mixed>
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getUserinfo(string $token): array
    {
        $response = $this->client
            ->withOptions([
                'verify_host' => false,
                'verify_peer' => false,
                'headers' => [
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer $token"
                ]
            ])
            ->request(
                'GET',
                $this->oidcAdBaseUrl . '/oauth/userinfo'
            );

        return $response->toArray();
    }
}
