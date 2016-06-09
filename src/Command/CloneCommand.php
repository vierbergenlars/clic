<?php

namespace vierbergenlars\CliCentral\Command;

use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\FileException;
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
            ->addOption('deploy-key', null, InputOption::VALUE_OPTIONAL, 'Path to the SSH key to use for the deploy. If no value is passed, a new key is generated.')
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

        if(!$repositoryConfiguration) {
            $stderr->writeln('You do not have a deploy key configured for this repository.');
            if ($input->hasParameterOption('--deploy-key')||$questionHelper->ask($input, $output, new ConfirmationQuestion('Do you want to create a deploy key?', false))) {
                $repositoryConfiguration = new RepositoryConfiguration();
                $repositoryConfiguration->setSshAlias(sha1($input->getArgument('repository')).'-'.$input->getArgument('application'));
                if(!$input->getOption('deploy-key')) {
                    /*
                     * No deploy key file was given
                     * Generate a new deploy key
                     */
                    $keyFile = $configHelper->getConfiguration()->getSshDirectory() . '/id_rsa-' . $repositoryConfiguration->getSshAlias();
                    $repositoryConfiguration->setIdentityFile($keyFile);
                    if(!file_exists($keyFile)) {
                        $sshKeygen = ProcessBuilder::create([
                            'ssh-keygen',
                            '-q',
                            '-f',
                            $keyFile,
                            '-C',
                            'clic-deploy-key-'.$repositoryConfiguration->getSshAlias().'@'.gethostname(),
                            '-N',
                            ''
                        ])->setTimeout(null)->getProcess();

                        $processHelper->mustRun($output, $sshKeygen);
                        $stderr->writeln(sprintf('<comment>Generated key <info>%s</info></comment>', $repositoryConfiguration->getIdentityFile()));
                    } else {
                        $stderr->writeln(sprintf('Key <info>%s</info> already exists. Not generating a new one.', $repositoryConfiguration->getIdentityFile()));
                    }
                } else {
                    /*
                     * A deploy key file was given
                     * Check it and set it as identity file
                     */
                    if(!is_file($input->getOption('deploy-key')))
                        throw new NotAFileException($input->getOption('deploy-key'));
                    $repositoryConfiguration->setIdentityFile($input->getOption('deploy-key'));
                    $stderr->writeln(sprintf('<comment>Deploy key <info>%s</info></comment>', $repositoryConfiguration->getIdentityFile()));
                }

                /*
                 * Print out deploy key information, and ask to add it as a deploy key to the repo
                 */
                $output->writeln(file_get_contents($repositoryConfiguration->getIdentityFile().'.pub'), OutputInterface::OUTPUT_PLAIN|OutputInterface::VERBOSITY_QUIET);
                $stderr->writeln('<comment>Please set the public key printed above as a deploy key for the repository</comment>');
                while(!$questionHelper->ask($input, $output, new ConfirmationQuestion('Is the deploy key uploaded?')));

                /*
                 * Add ssh alias to the SSH config file
                 */
                $sshConfigFile = $configHelper->getConfiguration()->getSshDirectory() . '/config';
                $sshConfigFp = fopen($sshConfigFile, 'a');
                $lines = PHP_EOL.'Host '.$repositoryConfiguration->getSshAlias().PHP_EOL
                    .'HostName '.$repoHost.PHP_EOL
                    .'User '.$repoUser.PHP_EOL
                    .'IdentityFile '.$repositoryConfiguration->getIdentityFile().PHP_EOL;

                if(fwrite($sshConfigFp, $lines) !== strlen($lines))
                    throw new \RuntimeException(sprintf('Could not fully write ssh configuration to "%s"', $sshConfigFile));
                if(!fclose($sshConfigFp))
                    throw new \RuntimeException(sprintf('Could not fully write ssh configuration to "%s"', $sshConfigFile));

                $stderr->writeln(sprintf('<comment>Added SSH alias <info>%s</info> to configuration file <info>%s</info>', $repositoryConfiguration->getSshAlias(), $sshConfigFile), OutputInterface::VERBOSITY_VERBOSE);
            }
            if($repositoryConfiguration) {
                /*
                 * If there is a configuration now, save it
                 */
                $configHelper->getConfiguration()->setRepositoryConfiguration($input->getArgument('repository'), $repositoryConfiguration);
                $configHelper->getConfiguration()->write();
            } else {
                /*
                 * Else, just use the defaults
                 */
                $repositoryConfiguration = new RepositoryConfiguration();
                $repositoryConfiguration->setSshAlias($repoUser.'@'.$repoHost);
            }
        }


        /*
         * Run a git clone for the application
         */
        $prevVerbosity = $stderr->getVerbosity();
        $stderr->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $gitClone = ProcessBuilder::create([
            'git',
            'clone',
            $repositoryConfiguration->getSshAlias().':'.$repoUrl,
            $appDirectoryHelper->getDirectoryForApplication($input->getArgument('application')),
        ])->setTimeout(null)->getProcess();
        $processHelper->mustRun($stderr, $gitClone);
        $stderr->setVerbosity($prevVerbosity);

        /*
         * Run post-clone script
         */
        $execCommand = $this->getApplication()
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
