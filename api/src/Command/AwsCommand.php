<?php

namespace App\Command;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:aws',
    description: 'Add a short description for your command',
)]
class AwsCommand extends Command
{
    public function __construct(
        private FilesystemOperator $awsStorage
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->awsStorage->write('test.txt', 'Hello AWS');
        return Command::SUCCESS;
    }
}
