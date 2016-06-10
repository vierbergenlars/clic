<?php

namespace vierbergenlars\CliCentral\Command;

use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\FileException;
use vierbergenlars\CliCentral\Exception\FileExistsException;
use vierbergenlars\CliCentral\Exception\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\NotAFileException;
use vierbergenlars\CliCentral\Exception\NotEmptyException;
use vierbergenlars\CliCentral\Helper\AppDirectoryHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\ProcessBuilder;

class CloneCommand extends Command
{
    protected function configure()
    {
        $this->setName('clone')
            ->addArgument('repository', InputArgument::REQUIRED, 'The remote repository to clone from.')
            ->addArgument('application', InputArgument::OPTIONAL, 'The name of the application to clone to. (Defaults to repository name)')
            ->addOption('no-deploy-key', null, InputOption::VALUE_NONE, 'Do not generate or use a deploy key')
            ->addOption('no-scripts', null, InputOption::VALUE_NONE, 'Do not run post-clone script')
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
        $appDirectoryHelper = $this->getHelper('app_directory');
        /* @var $appDirectoryHelper AppDirectoryHelper */
        if($output instanceof ConsoleOutputInterface) {
            $stderr = $output->getErrorOutput();
        } else {
            $stderr = $output;
        }

        if(!$input->getArgument('application')) {
            $input->setArgument('application', basename($input->getArgument('repository'), '.git'));
        }

        $repositoryConfiguration = $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'));

        list($repoUser, $repoHost, $repoUrl) = preg_split('/@|:/', $input->getArgument('repository'));

        /*
         * Create application directory
         */
        do {
            try {
                $appDirectoryHelper->getDirectoryForApplication($input->getArgument('application'));
                $notSucceeded = false;
            } catch(NotADirectoryException $ex) {
                mkdir($ex->getFilename());
                $stderr->writeln(sprintf('Created directory <info>%s</info>', $ex->getFilename()), OutputInterface::VERBOSITY_VERY_VERBOSE);
                $notSucceeded = true;
            }
        } while($notSucceeded);

        if(count(scandir($appDirectoryHelper->getDirectoryForApplication($input->getArgument('application'))))>2) {
            throw new NotEmptyException($appDirectoryHelper->getDirectoryForApplication($input->getArgument('application')));
        }

        if(!$repositoryConfiguration&&!$input->getOption('no-deploy-key')) {
            $stderr->writeln('You do not have a deploy key configured for this repository.', OutputInterface::VERBOSITY_VERBOSE);
            $repositoryConfiguration = new RepositoryConfiguration();
            $repositoryConfiguration->setSshAlias(sha1($input->getArgument('repository')).'-'.$input->getArgument('application'));
            /*
             * Generate a new deploy key, link it to the repository and print it.
             */
            $keyFile = $configHelper->getConfiguration()->getSshDirectory() . '/id_rsa-' . $repositoryConfiguration->getSshAlias();
            try {
                $this->getApplication()->find('sshkey:generate')
                    ->run(new ArrayInput([
                        'key' => $keyFile,
                        '--comment' => 'clic-deploy-key-' . $repositoryConfiguration->getSshAlias() . '@' . gethostname(),
                        '--target-repository' => $input->getArgument('repository'),
                        '--print-public-key' => true,
                    ]), $output);
                $repositoryConfiguration = $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'));
                if(!$repositoryConfiguration)
                    throw new \UnexpectedValueException('Repository configuration was not persisted right.');
            } catch(FileExistsException $ex) {
                $repositoryConfiguration->setIdentityFile($ex->getFilename());
                $stderr->writeln(sprintf('Key <info>%s</info> already exists. Not generating a new one.', $ex->getFilename()));
            }

            /*
             * Ask to add it as a deploy key to the repo
             */
            $stderr->writeln('<comment>Please set the public key printed above as a deploy key for the repository</comment>');
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
        $prevVerbosity = $stderr->getVerbosity();
        $stderr->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $gitClone = ProcessBuilder::create([
            'git',
            'clone',
            $repositoryConfiguration?($repositoryConfiguration->getSshAlias().':'.$repoUrl):$input->getArgument('repository'),
            $appDirectoryHelper->getDirectoryForApplication($input->getArgument('application')),
        ])->setTimeout(null)->getProcess();
        $processHelper->mustRun($stderr, $gitClone);
        $stderr->setVerbosity($prevVerbosity);

        if(!$input->getOption('no-scripts')) {
            /*
             * Run post-clone script
             */
            return $this->getApplication()
                ->find('exec')
                ->run(new ArrayInput([
                    'command' => 'exec',
                    '--env' => $input->getOption('env'),
                    '--config' => $input->getOption('config'),
                    '--skip-missing' => true,
                    'script' => 'post-clone',
                    'apps' => [
                        $input->getArgument('application'),
                    ],
                ]), $output);
        }
    }
}
