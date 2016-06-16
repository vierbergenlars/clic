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
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class AddCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:add')
            ->addArgument('application', InputArgument::REQUIRED, 'Application name')
            ->addOption('remote', null, InputOption::VALUE_REQUIRED, 'git remote for the application (will be guessed if empty)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper =  $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        try {
            $configHelper->getConfiguration()->getApplication($input->getArgument('application'));
            throw new ApplicationExistsException($input->getArgument('application'));
        } catch(NoSuchApplicationException $ex) {
            // no op
        }
        $application = Application::create($configHelper->getConfiguration(), $input->getArgument('application'));

        if(!$input->getOption('remote')) {
            try {
                $process = $application->getProcessBuilder([
                    'bash',
                    '-c',
                    'git remote show origin -n | awk \'/Fetch URL:/{print $3}\'',
                ])->getProcess();
                $processHelper->mustRun($output, $process);
                $input->setOption('remote', trim($process->getOutput()));
            } catch(ProcessFailedException $ex) {
                $output->writeln(sprintf('<comment>Could not determine repository remote: %s</comment>', $ex->getMessage()));
            }
        }

        if($input->getOption('remote')) {
            $repoParts = Util::parseRepositoryUrl($input->getOption('remote'));
            if(Util::isSshRepositoryUrl($repoParts)) {
                // If the remote is already mangled to contain an unique ssh alias, put it back in canonical form
                foreach($configHelper->getConfiguration()->getRepositoryConfigurations() as $repoName => $repoConf) {
                    /* @var $repoConf RepositoryConfiguration */
                    if($repoConf->getSshAlias() === $repoParts['host']) {
                        $repoConfParts = Util::parseRepositoryUrl($repoName);
                        if($repoConfParts['repository'] === $repoParts['repository'])
                            $input->setOption('remote', $repoName);
                    }
                }
            }
            $application->setRepository($input->getOption('remote'));
        }

        $configHelper->getConfiguration()->write();

        if(!$application->getRepository()) {
            $output->writeln(sprintf('Registered application <info>%s</info> (<comment>without repository</comment>)', $input->getArgument('application')));
        } else {
            $output->writeln(sprintf('Registered application <info>%s</info> with repository <info>%s</info>', $input->getArgument('application'), $application->getRepository()));
        }
    }
}
