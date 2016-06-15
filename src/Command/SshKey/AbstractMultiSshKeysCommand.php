<?php

namespace vierbergenlars\CliCentral\Command\SshKey;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

abstract class AbstractMultiSshKeysCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('repositories', InputArgument::IS_ARRAY)
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Use all repositories')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $repoConfigs = array_map(function($repoName) use($configHelper) {
            return $configHelper->getConfiguration()->getRepositoryConfiguration($repoName, true);
        }, array_combine($input->getArgument('repositories'), $input->getArgument('repositories')));
        if($input->getOption('all')) {
            $repoConfigs = array_merge($repoConfigs, $configHelper->getConfiguration()->getRepositoryConfigurations());
        }
        $input->setArgument('repositories', $repoConfigs);

        if(!count($input->getArgument('repositories'))&&!$input->getOption('all'))
            throw new \RuntimeException('No repositories specified. (Add some as arguments, or use --all|-A)');
    }
}
