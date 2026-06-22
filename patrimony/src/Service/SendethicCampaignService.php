<?php

namespace App\Service;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SendethicCampaignService
{
    private const BASE_URL = 'https://services.message-business.com/api/rest/v4';

    public function __construct(
        #[Autowire('%newsletter.sendethic.account_id%')]
        private readonly string $accountId,
        #[Autowire('%newsletter.sendethic.api_key%')]
        private readonly string $apiKey,
        #[Autowire('%newsletter.sendethic.segment_id%')]
        private readonly string $segmentId,
        #[Autowire('%newsletter.sender.email%')]
        private readonly string $senderEmail,
        #[Autowire('%newsletter.sender.name%')]
        private readonly string $senderName,
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /** @return array<mixed> */
    public function createAndSendCampaign(
        string $htmlContent,
        string $subject,
        string $campaignName,
        bool $sendImmediately = false,
    ): array {
        try {
            $response = $this->httpClient->post(self::BASE_URL . '/emailing', [
                'auth' => [$this->accountId, $this->apiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'name' => $campaignName,
                    'subject' => $subject,
                    'replyTo' => $this->senderEmail,
                    'fromName' => $this->senderName,
                    'fromMail' => $this->senderEmail,
                    'culture' => 'fr-fr',
                    'html' => $htmlContent,
                    'schedule' => ['type' => 'asap'],
                    'receivers' => ['segmentIds' => [$this->segmentId]],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $campaignId = $data['id'];

            $this->logger->info('Campagne Sendethic créée', ['id' => $campaignId]);

            if ($sendImmediately) {
                $this->sendCampaignNow($campaignId);
            }

            return [
                'success' => true,
                'campaignId' => $campaignId,
                'message' => $sendImmediately ? 'Campagne envoyée' : 'Campagne créée',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur création campagne Sendethic', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur création campagne: ' . $e->getMessage());
        }
    }

    public function sendCampaignNow(int $campaignId): void
    {
        try {
            $this->httpClient->put(self::BASE_URL . '/emailing/approve/' . $campaignId, [
                'auth' => [$this->accountId, $this->apiKey],
                'headers' => ['Content-Type' => 'application/json'],
            ]);

            $this->logger->info('Campagne Sendethic approuvée', ['id' => $campaignId]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur envoi campagne: ' . $e->getMessage());
        }
    }
}
