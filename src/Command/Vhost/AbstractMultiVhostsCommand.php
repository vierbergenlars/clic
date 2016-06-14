<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

abstract class AbstractMultiVhostsCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('vhosts', InputArgument::IS_ARRAY)
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Use all vhosts')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $vhostConfigs = array_map(function($vhostName) use($configHelper) {
            return $configHelper->getConfiguration()->getVhostConfiguration($vhostName);
        }, array_combine($input->getArgument('vhosts'), $input->getArgument('vhosts')));
        if($input->getOption('all')) {
            $vhostConfigs = array_merge($vhostConfigs, $configHelper->getConfiguration()->getVhostConfigurations());
        }
        $input->setArgument('vhosts', $vhostConfigs);

        if(!count($input->getArgument('vhosts'))&&!$input->getOption('all'))
            throw new \RuntimeException('No vhosts specified. (Add some as arguments, or use --all|-A)');
    }
}
