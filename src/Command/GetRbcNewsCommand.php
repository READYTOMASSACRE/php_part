<?php
namespace App\Command;

use App\Service\Parser\RbcParser;
use App\Repository\NewsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

class GetRbcNewsCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:get-rbc-news';

    public function __construct(RbcParser $parser, NewsRepository $repository)
    {
        $this->parser = $parser;
        $this->repository = $repository;

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
        $source = $input->getArgument('source') ?? $this->getDefaultSource();

        $output->writeln([
            'Parsing news from ' . $source,
            '=============',
            ''
        ]);

        // gets payload from parser
        $payload = $this->parser->getPayload($source);

        // save data by repository
        $this->repository->createOrUpdateByExternalId($payload);

        $output->writeln('News successfuly parsed');
    }

    /**
     * Return default source for Rbc parser
     * 
     * @return string
     */
    protected function getDefaultSource() : string
    {
        $source = 'https://www.rbc.ru/v10/ajax/get-news-feed/project/rbcnews/lastDate/{{date}}/limit/15';

        return str_replace('{{date}}', time(), $source);
    }
}