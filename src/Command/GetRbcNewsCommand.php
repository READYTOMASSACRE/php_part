<?php
namespace App\Command;

use App\Service\Parser\RbcUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class GetRbcNewsCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:get-rbc-news';

    public function __construct(RbcUpdater $updater)
    {
        $this->updater = $updater;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Parsing part of lists from <src>')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to parse limited count from external source...')
            ->addArgument('source', InputArgument::OPTIONAL, 'Http source');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source') ?? $this->updater->getParser()->getDefaultSource();

        $output->writeln([
            'Parsing news from ' . $source,
            '=============',
            ''
        ]);

        // gets payload from parser
        $this->updater->getData($source);

        $output->writeln('News successfuly parsed');
    }
}