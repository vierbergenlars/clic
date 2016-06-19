<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class VariableGetCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:variable:get')
            ->addArgument('application', InputArgument::REQUIRED)
            ->addArgument('variable', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configurationHelper = $this->getHelper('configuration');
        /* @var $configurationHelper GlobalConfigurationHelper */
        $application = $configurationHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $output->writeln($application->getVariable($input->getArgument('variable')));
    }

}
