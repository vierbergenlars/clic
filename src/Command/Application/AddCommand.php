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
            ->addOption('archive-url', null, InputOption::VALUE_REQUIRED, 'Download URL from where the application was downloaded')
            ->setDescription('Add an existing application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command adds an application to the list of managed applications:

  <info>%command.full_name% authserver</info>

The location of the application is relative to the <comment>applications-dir</comment> configuration option.
You can also set the reference repository by using the <comment>--remote</comment> option:

  <info>%command.full_name% authserver --remote=https://github.com/vierbergenlars/authserver</info>

If no reference repository is passed as option, an attempt is made to automatically detect it.

Alternatively, if the application was downloaded and extracted from an archive, use the <comment>--archive-url</comment>
option to set the location where it was downloaded from:

  <info>%command.full_name% authserver --archive-url=https://github.com/vierbergenlars/authserver/archive/v0.8.0.zip</info>

To create a new application from a remote repository, use the <info>application:clone</info> command.
To create a new application from an archive, use the <info>application:extract</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper =  $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        if($input->getOption('remote')&&$input->getOption('archive-url'))
            throw new \InvalidArgumentException('The --remote and --archive-url options are mutually exclusive.');

        try {
            $configHelper->getConfiguration()->getApplication($input->getArgument('application'));
            throw new ApplicationExistsException($input->getArgument('application'));
        } catch(NoSuchApplicationException $ex) {
            // no op
        }
        $application = Application::create($configHelper->getConfiguration(), $input->getArgument('application'));

        if(!$input->getOption('remote')&&!$input->getOption('archive-url')) {
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

        if($input->getOption('archive-url')) {
            $application->setArchiveUrl($input->getOption('archive-url'));
        }

        $configHelper->getConfiguration()->write();

        if($application->getArchiveUrl()) {
            $output->writeln(sprintf('Registered application <info>%s</info> (downloaded from <info>%s</info>)', $input->getArgument('application'), $application->getArchiveUrl()));
        } elseif(!$application->getRepository()) {
            $output->writeln(sprintf('Registered application <info>%s</info> (<comment>without repository</comment>)', $input->getArgument('application')));
        } else {
            $output->writeln(sprintf('Registered application <info>%s</info> with repository <info>%s</info>', $input->getArgument('application'), $application->getRepository()));
        }
    }
}
