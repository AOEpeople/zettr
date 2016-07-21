<?php

namespace Zettr\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('apply')
            ->setDescription('Apply settings')
            ->addOption(
                'dryRun',
                'd',
                InputOption::VALUE_NONE,
                'Dry run'
            )
            ->addOption(
                'groups',
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma-separated list of groups to execute'
            )
            ->addOption(
                'excludeGroups',
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma-separated list of groups to exclude'
            )
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            )
            ->addArgument(
                'file',
                InputArgument::IS_ARRAY + InputArgument::REQUIRED,
                'CSV file(s) to apply'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getArgument('env');
        $output->writeln('Environment: <info>' . $environment . '</info>');
        $files = $input->getArgument('file');
        foreach ($files as $file) {
            $output->writeln('Applying file <info>' . $file . '</info>...');
            $processor = new \Zettr\Processor(
                $environment,
                $file,
                $input->getOption('groups'),
                $input->getOption('excludeGroups')
            );
            $processor->setOutput($output);
            try {
                $processor->check();
                if($input->getOption('dryRun')){
                    $processor->dryRun();
                } else {
                    $processor->apply();
                }
                $processor->printResults();
            } catch (\Exception $e) {
                $processor->printResults();
                $output->writeln('<error>ERROR: Stopping execution because an error has occured!</error>');
                $output->writeln("Detail: {$e->getMessage()}");
                exit(1);
            }
        }
    }

}