<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Configuration\ApplicationConfiguration;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class RemoveCommand extends AbstractMultiApplicationsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('application:remove')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Permanently remove the application directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper =  $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        $exitCode = 0;

        foreach($input->getArgument('applications') as $appName => $appConfig) {
            /* @var $appConfig Application */
            $output->write(sprintf('Remove application <info>%s</info>...', $appName));
            if($input->getOption('purge')) {
                $output->writeln(sprintf('Purging <comment>%s</comment>', $appConfig->getPath()));
                if(!$this->isAppInAppdir($appConfig)) {
                    $output->writeln('<error>Application path is outside application directory. Not purging this one, just to be sure.</error>');
                    $exitCode = 1;
                } else {
                    $processHelper->mustRun($output, ProcessBuilder::create(['rm', '-rf', $appConfig->getPath()])->getProcess());
                }
            }
            $configHelper->getConfiguration()->removeApplication($appName);
            $output->writeln('<info>OK</info>');
        }

        $configHelper->getConfiguration()->write();

        return $exitCode;
    }

    private function isAppInAppdir(ApplicationConfiguration $applicationConfiguration)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $appDir = $configHelper->getConfiguration()->getApplicationsDirectory();
        $path = realpath($applicationConfiguration->getPath());
        while(($parentPath = dirname($path)) !== $path) {
            if($parentPath === $appDir)
                return true;
            $path = $parentPath;
        }
        return false;
    }
}
