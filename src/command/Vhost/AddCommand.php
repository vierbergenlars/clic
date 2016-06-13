<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchVhostException;
use vierbergenlars\CliCentral\Exception\Configuration\VhostExistsException;
use vierbergenlars\CliCentral\Exception\File\FileExistsException;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Helper\AppDirectoryHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class AddCommand extends Command
{
    protected function configure()
    {
        $this->setName('vhost:add')
            ->addArgument('vhost', InputArgument::REQUIRED, 'Vhost to add')
            ->addArgument('application', InputArgument::REQUIRED, 'Application to link to vhost')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $appDirectoryHelper = $this->getHelper('app_directory');
        /* @var $appDirectoryHelper AppDirectoryHelper */

        if($output instanceof ConsoleOutputInterface) {
            $stderr = $output->getErrorOutput();
        } else {
            $stderr = $output;
        }
        try {
            $configHelper->getConfiguration()->getVhostConfiguration($input->getArgument('vhost'));
            throw new VhostExistsException($input->getArgument('vhost'));
        } catch(NoSuchVhostException $ex) {
            // noop
        }

        $vhostLink = null;

        do {
            try {
                $vhostLink = $appDirectoryHelper->getLinkForVhost($input->getArgument('vhost'));
                if(is_link($vhostLink))
                    throw new FileExistsException($vhostLink);
                $notSucceeded = false;
            } catch(NotADirectoryException $ex) {
                mkdir($ex->getFilename(), 0777, true);
                $stderr->writeln(sprintf('Created directory <info>%s</info>', $ex->getFilename()), OutputInterface::VERBOSITY_VERY_VERBOSE);
                $notSucceeded = true;
            }
        } while($notSucceeded);

        if(!$vhostLink)
            throw new \LogicException('Could not find vhost link name');

        $application = $appDirectoryHelper->getEnvironment()->getApplication($input->getArgument('application'));
        $webDir = $application->getWebDirectory();

        $vhostConfig = new VhostConfiguration();
        $vhostConfig->setApplication($input->getArgument('application'));
        $vhostConfig->setEnvironment($appDirectoryHelper->getEnvironment()->getName());
        $vhostConfig->setTarget($webDir);
        $vhostConfig->setLink($vhostLink);

        if(!is_dir($vhostConfig->getLink()->getPath())) {
            $stderr->writeln(sprintf('Created directory <info>%s</info>', $vhostConfig->getLink()->getPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
            mkdir($vhostConfig->getLink()->getPath(), 0777, true);
        }

        if(!@symlink($vhostConfig->getTarget(), $vhostConfig->getLink()))
            throw new \RuntimeException(sprintf('Failed to link "%s" to "%s": %s', $vhostConfig->getLink(), $vhostConfig->getTarget(), error_get_last()['message']));

        $output->writeln(sprintf('Created vhost <info>%s</info> for <info>%s</info> (<comment>%s</comment>) (link to: <info>%s</info>)', $input->getArgument('vhost'), $vhostConfig->getApplication(), $vhostConfig->getEnvironment(), $vhostConfig->getTarget()));

        $configHelper->getConfiguration()->setVhostConfiguration($input->getArgument('vhost'), $vhostConfig);
        $configHelper->getConfiguration()->write();
    }

}
