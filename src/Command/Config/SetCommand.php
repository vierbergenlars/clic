<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class SetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:set')
            ->addArgument('parameter', InputArgument::REQUIRED, 'Configuration parameter to set')
            ->addArgument('value', InputArgument::REQUIRED, 'Value to set configuration parameter to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $configHelper->getConfiguration()->setConfig($input->getArgument('parameter'), $input->getArgument('value'));
        $configHelper->getConfiguration()->write();
    }

}
