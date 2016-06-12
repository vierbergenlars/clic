<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class GetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:get')
            ->addArgument('parameter', InputArgument::OPTIONAL, 'Configuration parameter to get')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $config = $configHelper->getConfiguration()->getConfig()->config;
        if($input->getArgument('parameter')) {
            if (!isset($config->{$input->getArgument('parameter')}))
                throw new MissingConfigurationParameterException($input->getArgument('parameter'));
            $output->writeln($config->{$input->getArgument('parameter')});
        } else {
            foreach($config as $parameter=>$value) {
                $output->writeln(sprintf('%s: %s', $parameter, $value));
            }
        }
    }

}
