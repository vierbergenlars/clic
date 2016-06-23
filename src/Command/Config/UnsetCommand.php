<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class UnsetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:unset')
            ->addArgument('parameter', InputArgument::REQUIRED, 'Configuration parameter to unset')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $configHelper->getConfiguration()->removeConfigOption(Util::createPropertyPath($input->getArgument('parameter')));
        $configHelper->getConfiguration()->write();
    }

}
