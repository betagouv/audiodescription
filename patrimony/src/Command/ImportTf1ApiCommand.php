<?php

namespace App\Command;

use App\Enum\ImportSourceType;
use App\Importer\Movie\ImporterFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'ad:import:tf1-api')]
class ImportTf1ApiCommand extends Command
{
    public function __construct(
        private ImporterFactory $importerFactory,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = [
            'create-movies' => filter_var($input->getOption('create-movies'), FILTER_VALIDATE_BOOLEAN),
        ];

        $importer = $this->importerFactory->createImporter(ImportSourceType::TF1_API);
        $importer->import($options);

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Import solutions from TF1 file.')
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command is used to import solutions from TF1 file.')
            ->addOption(
                'create-movies',
                null,
                InputOption::VALUE_REQUIRED,
                'Set to "true" to create movies, "false" to skip creation.',
                'false'
            );
        ;
    }
}

