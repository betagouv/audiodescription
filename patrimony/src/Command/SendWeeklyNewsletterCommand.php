<?php

namespace App\Command;

use App\Service\BrevoCampaignService;
use App\Service\NewsletterContentGenerator;
use DateTime;
use IntlDateFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
  name: 'app:send-weekly-newsletter',
  description: 'Crée et envoie la newsletter hebdomadaire via Brevo'
)]
class SendWeeklyNewsletterCommand extends Command
{
  public function __construct(
    private readonly BrevoCampaignService $brevoService,
    private readonly NewsletterContentGenerator $contentGenerator,

    #[Autowire('%newsletter.brevo_api.list_id%')]
    private readonly int $brevoListId
  ) {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->addOption('test', 't', InputOption::VALUE_NONE, 'Envoyer en test uniquement')
      ->addOption('schedule', 's', InputOption::VALUE_OPTIONAL, 'Programmer l\'envoi (format: Y-m-d H:i)')
      ->addOption('test-email', null, InputOption::VALUE_OPTIONAL, 'Email pour le test');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $io->title('Newsletter Hebdomadaire Patrimony');

    // 1. Génération du contenu HTML via le service
    $io->section('Génération du contenu de la newsletter...');

    try {
      $htmlContent = $this->contentGenerator->generateWeeklyNewsletter();
    } catch (\Exception $e) {
      $io->error('Erreur lors de la génération du contenu : ' . $e->getMessage());
      return Command::FAILURE;
    }

    // 2. Création de la campagne
    $formatter = new IntlDateFormatter(
      'fr_FR',
      IntlDateFormatter::LONG,
      IntlDateFormatter::NONE
    );
    $date = $formatter->format(new DateTime());

    $campaignName = sprintf('Infolettre du %s', $date);
    $subject = sprintf('Les films gratuits en audiodescription de la semaine du %s', $date);

    $io->section("Création de la campagne : $campaignName");

    try {
      $result = $this->brevoService->createAndSendCampaign(
        htmlContent: $htmlContent,
        listId: $this->brevoListId,
        subject: $subject,
        campaignName: $campaignName,
        sendImmediately: false
      );

      $campaignId = $result['campaignId'];
      $io->success("Campagne créée avec l'ID : $campaignId");

      // 3. Mode test
      if ($input->getOption('test')) {
        $testEmail = $input->getOption('test-email') ?: 'ton-email@test.fr';
        $io->section("Envoi d'un test à : $testEmail");

        $this->brevoService->sendTestCampaign($campaignId, [$testEmail]);
        $io->success('Test envoyé ! Vérifie ta boîte mail.');

        return Command::SUCCESS;
      }

      // 5. Programmation ou envoi immédiat
      if ($scheduleDate = $input->getOption('schedule')) {
        $scheduledAt = \DateTime::createFromFormat('Y-m-d H:i', $scheduleDate);

        if (!$scheduledAt) {
          $io->error('Format de date invalide. Utilisez : Y-m-d H:i');
          return Command::FAILURE;
        }

        $this->brevoService->scheduleCampaign($campaignId, $scheduledAt);
        $io->success("Campagne programmée pour le : " . $scheduledAt->format('d/m/Y à H:i'));
        return Command::SUCCESS;
      }

      // 5. Envoi immédiat
      if ($input->getOption('no-interaction')) {
        // Mode automatique : envoi direct sans confirmation
        $io->section('Envoi de la campagne...');
        $this->brevoService->sendCampaignNow($campaignId);
        $io->success('Campagne envoyée automatiquement (mode cron)');
      } else {
        // Mode interactif : demander confirmation
        if ($io->confirm('Envoyer la campagne maintenant ?', false)) {
          $this->brevoService->sendCampaignNow($campaignId);
          $io->success('Campagne envoyée avec succès !');
        } else {
          $io->note("Campagne créée mais non envoyée. ID : $campaignId");
          $io->note("Tu peux l'envoyer manuellement depuis ton compte Brevo.");
        }
      }

      return Command::SUCCESS;

    } catch (\Exception $e) {
      $io->error('Erreur : ' . $e->getMessage());
      return Command::FAILURE;
    }
  }
}