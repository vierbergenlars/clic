<?php
/**
 * clic, user-friendly PHP application deployment and set-up
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Lars Vierbergen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
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
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Helper\ProcessHelper;
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
            ->addOption('override', 'o', InputOption::VALUE_REQUIRED, 'Override for the application\'s configuration')
            ->addOption('override-type', null, InputOption::VALUE_REQUIRED, 'Type of the override for the application')
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

The <comment>--override|-o</comment> and <comment>--override-type</comment> options allow to immediately add a
configuration override for the application. (See <info>application:override</info>)

To add an existing application, use the <info>application:add</info> command.
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
        $questionHelper = $this->getHelper('question');
        /* @var $questionHelper QuestionHelper */

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
        $gitClone = ProcessBuilder::create([
            'git',
            'clone',
            Util::replaceRepositoryUrl($repositoryParts, $repositoryConfiguration),
            $application->getPath(),
        ])->setTimeout(null)->getProcess();
        $processHelper->mustRun($output, $gitClone, null, null, OutputInterface::VERBOSITY_NORMAL, OutputInterface::VERBOSITY_NORMAL);

        $configHelper->getConfiguration()->removeApplication($application->getName()); // Clean up application again, so application:add further down does not complain.

        $this->getApplication()
            ->find('application:add')
            ->run(new ArrayInput([
                'application' => $input->getArgument('application'),
                '--remote' => $input->getArgument('repository'),
            ]), $output);

        if($input->getOption('override')) {
            $input1 = new ArrayInput([
                'application' => $input->getArgument('application'),
                'config-file' => $input->getOption('override'),
            ]);
            if($input->getOption('override-type')) {
                $input1->setOption('type', $input->getOption('override-type'));
            }
            $this->getApplication()
                ->find('application:override')
                ->run($input1, $output);
        }

        if(!$input->getOption('no-scripts')) {
            /*
             * Run post-clone script
             */
            return $this->getApplication()
                ->find('application:execute')
                ->run(new ArrayInput([
                    'script' => 'post-clone',
                    'application' => $input->getArgument('application'),
                ]), $output);
        }
        return 0;
    }
}
