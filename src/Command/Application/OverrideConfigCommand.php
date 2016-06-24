<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\ApplicationExistsException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchApplicationException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class OverrideConfigCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:override:config')
            ->addArgument('application', InputArgument::REQUIRED, 'The application to add an override to')
            ->addArgument('config-file', InputArgument::REQUIRED, 'The configuration file to use for the application')
            ->setDescription('Changes the configuration file for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command changes the configuration file for the application,
effectively overriding all settings in the packaged <comment>.cliconfig.json</comment>:

  <info>%command.full_name% authserver ~/clic-overrides/authserver/cliconfig.json</info>

Resetting the configuration file back to the defaults is done by passing an empty second argument:

  <info>%command.full_name% authserver ''</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $application = $configHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $configFile = $input->getArgument('config-file');
        if($configFile) {
            $configFile = new \SplFileInfo($configFile);
            if(!$configFile->isFile())
                throw new NotAFileException($configFile);
            if(!$configFile->isReadable())
                throw new UnreadableFileException($configFile);
            $configFile = $configFile->getRealPath();
        }

        $application->setConfigurationFileOverride($configFile);

        $configHelper->getConfiguration()->write();

    }
}
