<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class SetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:set')
            ->addArgument('parameter', InputArgument::REQUIRED, 'Configuration parameter to set')
            ->addArgument('value', InputArgument::REQUIRED, 'Value to set configuration parameter to')
            ->setDescription('Sets configuration values')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command sets the configuration parameters from the global configuration file:

  <info>%command.full_name% config[ssh-dir] ~/.ssh</info>

<fg=red><options=underscore;bold>WARNING:</> This is a very powerful command, misconfiguration of parameters may break other commands.
Using this command is almost certainly not the right way to change settings; have another look at the other commands.</>

To read configuration parameters, use the <info>config:get</info> command.
To remove configuration parameters, use the <info>config:unset</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $configHelper->getConfiguration()->setConfigOption(Util::createPropertyPath($input->getArgument('parameter')), $input->getArgument('value'));
        $configHelper->getConfiguration()->write();
    }

}
