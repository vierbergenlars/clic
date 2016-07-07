<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Exception\File\FileExistsException;
use vierbergenlars\CliCentral\Exception\File\NotEmptyException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\DirectoryHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class CloneCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:clone')
            ->addArgument('repository', InputArgument::REQUIRED, 'The remote repository to clone from.')
            ->addArgument('application', InputArgument::OPTIONAL, 'The name of the application to clone to. (Defaults to repository name)')
            ->addOption('no-deploy-key', null, InputOption::VALUE_NONE, 'Do not generate or use a deploy key')
            ->addOption('no-scripts', null, InputOption::VALUE_NONE, 'Do not run post-clone script')
            ->setDescription('Create a new application from remote repository')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command creates a new application from a remote repository.

  <info>%command.full_name% https://github.com/vierbergenlars/authserver</info>

By default, the application name and location is derived from the repository URL.
The application name can be changed by adding a second argument:

  <info>%command.full_name% https://github.com/vierbergenlars/authserver prod/authserver</info>

The location of the application is relative to the <comment>applications-dir</comment> configuration option.

When cloning using ssh, a deploy key is used for that repository, unless the <comment>--no-deploy-key</comment> option is used.
If there is no deploy key available yet, one is generated and displayed automatically. (See the <info>repository:generate-key</info> command)
It should be uploaded as a read-only deploy key in the interface of the repository host.

After cloning the application is completed, the <info>post-clone</info> script of the repository is executed.
(For more informations on scripts, see the <info>application:execute</info> command)
Automatically running of this script can be prevented by using the <comment>--no-scripts</comment> option.

To add an existing application, use the <info>application:add</info> command.
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
        $questionHelper = $this->getHelper('question');
        /* @var $questionHelper QuestionHelper */
        $directoryHelper = $configHelper->getDirectoryHelper();
        /* @var $directoryHelper DirectoryHelper */

        if(!$input->getArgument('application')) {
            $input->setArgument('application', basename($input->getArgument('repository'), '.git'));
        }

        try {
            $repositoryConfiguration = $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'));
        } catch(NoSuchRepositoryException $ex) {
            $repositoryConfiguration = null;
        }

        $repositoryParts = Util::parseRepositoryUrl($input->getArgument('repository'));
        if(!Util::isSshRepositoryUrl($repositoryParts)) {
            $input->setOption('no-deploy-key', true);
        }

        $application = Application::create($configHelper->getConfiguration(), $input->getArgument('application'));
        if(!is_dir($application->getPath())) {
            FsUtil::mkdir($application->getPath(), true);
            $output->writeln(sprintf('Created directory <info>%s</info>', $application->getPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        NotEmptyException::assert($application->getPath());

        $configHelper->getConfiguration()->removeApplication($application->getName()); // Clean up application again, so application:add further down does not complain.

        if(!$repositoryConfiguration&&!$input->getOption('no-deploy-key')) {
            $output->writeln('You do not have a deploy key configured for this repository.', OutputInterface::VERBOSITY_VERBOSE);
            $repositoryConfiguration = new RepositoryConfiguration();
            $repositoryConfiguration->setSshAlias(sha1($input->getArgument('repository')).'-'.basename($input->getArgument('application')));
            /*
             * Generate a new deploy key, link it to the repository and print it.
             */
            $keyFile = $configHelper->getConfiguration()->getSshDirectory() . '/id_rsa-' . $repositoryConfiguration->getSshAlias();
            try {
                $this->getApplication()->find('repository:generate-key')
                    ->run(new ArrayInput([
                        'key' => $keyFile,
                        '--comment' => 'clic-deploy-key-' . $repositoryConfiguration->getSshAlias() . '@' . gethostname(),
                        '--target-repository' => $input->getArgument('repository'),
                        '--print-public-key' => true,
                    ]), $output);
                $repositoryConfiguration = $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'));
            } catch(FileExistsException $ex) {
                $repositoryConfiguration->setIdentityFile($ex->getFilename());
                $output->writeln(sprintf('Key <info>%s</info> already exists. Not generating a new one.', $ex->getFilename()));
            }

            /*
             * Ask to add it as a deploy key to the repo
             */
            $output->writeln('<comment>Please set the public key printed above as a deploy key for the repository</comment>');
            while(!$questionHelper->ask($input, $output, new ConfirmationQuestion('Is the deploy key uploaded?')));
        }

        if($repositoryConfiguration&&!$input->getOption('no-deploy-key')) {
            /*
             * If there is a configuration now, save it
             */
            $configHelper->getConfiguration()->setRepositoryConfiguration($input->getArgument('repository'), $repositoryConfiguration);
            $configHelper->getConfiguration()->write();
        }  else {
            $repositoryConfiguration = null;
        }


        /*
         * Run a git clone for the application
         */
        $prevVerbosity = $output->getVerbosity();
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $gitClone = ProcessBuilder::create([
            'git',
            'clone',
            Util::replaceRepositoryUrl($repositoryParts, $repositoryConfiguration),
            $directoryHelper->getDirectoryForApplication($input->getArgument('application')),
        ])->setTimeout(null)->getProcess();
        $processHelper->mustRun($output, $gitClone);
        $output->setVerbosity($prevVerbosity);

        $this->getApplication()
            ->find('application:add')
            ->run(new ArrayInput([
                'application' => $input->getArgument('application'),
                '--remote' => $input->getArgument('repository'),
            ]), $output);

        if(!$input->getOption('no-scripts')) {
            /*
             * Run post-clone script
             */
            return $this->getApplication()
                ->find('application:execute')
                ->run(new ArrayInput([
                    '--skip-missing' => true,
                    'script' => 'post-clone',
                    'applications' => [
                        $input->getArgument('application'),
                    ],
                ]), $output);
        }
        return 0;
    }
}
