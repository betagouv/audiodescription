<?php

namespace App\Service;

use Brevo\Client\Configuration;
use Brevo\Client\Api\EmailCampaignsApi;
use Brevo\Client\Model\CreateEmailCampaign;
use Brevo\Client\Model\CreateEmailCampaignSender;
use Brevo\Client\Model\CreateEmailCampaignRecipients;
use Brevo\Client\Model\SendTestEmail;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BrevoCampaignService
{

  private EmailCampaignsApi $apiInstance;

  public function __construct(
    #[Autowire('%newsletter.brevo_api.key%')]
    private string $brevoApiKey,

    #[Autowire('%newsletter.sender.email%')]
    private string $senderEmail,

    #[Autowire('%newsletter.sender.name%')]
    private string $senderName,
    private LoggerInterface $logger
  ) {
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->brevoApiKey);
    $this->apiInstance = new EmailCampaignsApi(new Client(), $config);
  }

  public function createAndSendCampaign(
    string $htmlContent,
    int $listId,
    string $subject,
    string $campaignName,
    bool $sendImmediately = false,
  ) :array{
    // Configuration de l'expéditeur
    $sender = new CreateEmailCampaignSender([
      'name' => $this->senderName,
      'email' => $this->senderEmail
    ]);

    // Configuration des destinataires (liste Brevo)
    $recipients = new CreateEmailCampaignRecipients([
      'listIds' => [$listId]
    ]);

    // Création de la campagne
    $emailCampaign = new CreateEmailCampaign([
      'name' => $campaignName,
      'subject' => $subject,
      'sender' => $sender,
      'htmlContent' => $htmlContent,
      'recipients' => $recipients,
      'inlineImageActivation' => false,
      'mirrorActive' => true,
      'recurring' => false,
      'type' => 'classic'
    ]);

    try {
      // Création de la campagne
      $result = $this->apiInstance->createEmailCampaign($emailCampaign);
      $campaignId = $result->getId();

      $this->logger->info('Campagne créée', ['id' => $campaignId]);

      // Envoi immédiat ou programmé
      if ($sendImmediately) {
        $this->sendCampaignNow($campaignId);
      }

      return [
        'success' => true,
        'campaignId' => $campaignId,
        'message' => $sendImmediately ? 'Campagne envoyée' : 'Campagne créée'
      ];

    } catch (\Exception $e) {
      $this->logger->error('Erreur création campagne', [
        'error' => $e->getMessage()
      ]);

      throw new \RuntimeException('Erreur création campagne: ' . $e->getMessage());
    }
  }

  /**
   * Envoie une campagne immédiatement
   */
  public function sendCampaignNow(int $campaignId): void
  {
    try {
      $this->apiInstance->sendEmailCampaignNow($campaignId);
      $this->logger->info('Campagne envoyée', ['id' => $campaignId]);
    } catch (\Exception $e) {
      throw new \RuntimeException('Erreur envoi campagne: ' . $e->getMessage());
    }
  }

  /**
   * Programme l'envoi d'une campagne
   */
  public function scheduleCampaign(int $campaignId, \DateTime $scheduledAt): void
  {
    try {
      // La bonne méthode est updateEmailCampaign
      $updateCampaign = new \Brevo\Client\Model\UpdateEmailCampaign([
        'scheduledAt' => $scheduledAt->format(\DateTime::ATOM)
      ]);

      $this->apiInstance->updateEmailCampaign($updateCampaign, $campaignId);

      $this->logger->info('Campagne programmée', [
        'id' => $campaignId,
        'date' => $scheduledAt->format('Y-m-d H:i:s')
      ]);
    } catch (\Exception $e) {
      throw new \RuntimeException('Erreur programmation campagne: ' . $e->getMessage());
    }
  }

  /**
   * Envoie un test à des emails spécifiques
   */
  public function sendTestCampaign(int $campaignId, array $testEmails): void
  {
    try {
      $sendTest = new SendTestEmail([
        'emailTo' => $testEmails
      ]);

      $this->apiInstance->sendTestEmail($sendTest, $campaignId);

      $this->logger->info('Test campagne envoyé', [
        'id' => $campaignId,
        'recipients' => $testEmails
      ]);
    } catch (\Exception $e) {
      throw new \RuntimeException('Erreur envoi test: ' . $e->getMessage());
    }
  }
}