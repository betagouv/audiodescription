<?php

namespace App\Command;

use App\Enum\ImportSourceType;
use App\Importer\Movie\ImporterFactory;
use App\Importer\Public\PublicCsvImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'ad:import:cnc-public')]
class ImportCncPublicCommand extends Command
{
    private string $dataDir;

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private PublicCsvImporter $publicCsvImporter,
    )
    {
        $this->dataDir = sprintf('%s/data/', $this->parameterBag->get('kernel.project_dir'));

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = sprintf('%s/%s', $this->dataDir, 'publics.csv');
        $this->publicCsvImporter->import($file);

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Import public from a CNC CSV file.')
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command is used to import public from a CNC CSV file.')
        ;
    }
}

