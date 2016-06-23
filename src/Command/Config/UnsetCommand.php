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
            ->setDescription('Removes configuration values')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command removes the configuration parameters from the global configuration file:

  <info>%command.full_name% config[ssh-dir]</info>

<fg=red><options=underscore;bold>WARNING:</> This is a very powerful command, misconfiguration of parameters may break other commands.
Using this command is almost certainly not the right way to change settings; have another look at the other commands.</>

To read configuration parameters, use the <info>config:get</info> command.
To set configuration parameters, use the <info>config:set</info> command.
EOF
            )
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
