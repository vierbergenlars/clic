<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

abstract class AbstractMultiApplicationsCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('applications', InputArgument::IS_ARRAY)
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Use all applications')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $appConfigs = array_map(function($appName) use($configHelper) {
            return $configHelper->getConfiguration()->getApplication($appName);
        }, array_combine($input->getArgument('applications'), $input->getArgument('applications')));
        if($input->getOption('all')) {
            $appConfigs = array_merge($appConfigs, $configHelper->getConfiguration()->getApplications());
        }
        $input->setArgument('applications', $appConfigs);

        if(!count($input->getArgument('applications'))&&!$input->getOption('all'))
            throw new \RuntimeException('No applications specified. (Add some as arguments, or use --all|-A)');
    }
}
