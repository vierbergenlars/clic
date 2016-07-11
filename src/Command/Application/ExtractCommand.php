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

class ExtractCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:extract')
            ->addArgument('archive', InputArgument::REQUIRED, 'The archive to extract the application from')
            ->addArgument('application', InputArgument::OPTIONAL, 'The name of the application to extract to. (Defaults to archive name)')
            ->addOption('no-scripts', null, InputOption::VALUE_NONE, 'Do not run post-clone script')
            ->setDescription('Create a new application from an archive')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command creates a new application from an archive.

  <info>%command.full_name% /tmp/authserver-v0.8.0.zip</info>
  <info>%command.full_name% https://github.com/vierbergenlars/authserver/archive/v0.8.0.zip</info>

By default, the application name and location is derived from the archive name.
The application name can be changed by adding a second argument:

  <info>%command.full_name% https://github.com/vierbergenlars/authserver/archive/v0.8.0.zip prod/authserver</info>

The location of the application is relative to the <comment>applications-dir</comment> configuration option.

When the archive is detected to be an URL, the archive will be downloaded automatically.

After unpacking the application is completed, the <info>post-extract</info> script of the application is executed.
(For more informations on scripts, see the <info>application:execute</info> command)
Automatically running of this script can be prevented by using the <comment>--no-scripts</comment> option.

Accepted archive formats are: zip, rar, tar, tar.gz, tar.bz2, tar.xz, tar.Z.

To add an existing application, use the <info>application:add</info> command.
To clone an application with git, use the <info>application:clone</info> command.
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
            $appName = basename($input->getArgument('archive'));
            $input->setArgument('application', preg_replace('/\\.(zip|rar|(tar(\\.(gz|bz2|xz|Z))?))$/i', '', $appName));
        }


        $application = Application::create($configHelper->getConfiguration(), $input->getArgument('application'));
        if(!is_dir($application->getPath())) {
            FsUtil::mkdir($application->getPath(), true);
            $output->writeln(sprintf('Created directory <info>%s</info>', $application->getPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        NotEmptyException::assert($application->getPath());

        $appDir = $application->getPath();

        $configHelper->getConfiguration()->removeApplication($application->getName()); // Clean up application again, so application:add further down does not complain.


        if(strpos($input->getArgument('archive'), '://') !== false) { // This is an url
            $tempDir = $configHelper->getConfiguration()->getClicDirectory() . '/tmp';
            if(!is_dir($tempDir))
                FsUtil::mkdir($tempDir, true);
            $outputFile = $tempDir.'/'.sha1($input->getArgument('archive')).'-'.basename($input->getArgument('archive'));
            /*
             * Wget the file
             */
            $prevVerbosity = $output->getVerbosity();
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            $wget = ProcessBuilder::create([
                'wget',
                $input->getArgument('archive'),
                '-O',
                $outputFile,
            ])->setTimeout(null)->getProcess();
            $processHelper->mustRun($output, $wget);
            $output->setVerbosity($prevVerbosity);
            Util::extractArchive($outputFile, $appDir);
            FsUtil::unlink($outputFile);
            $this->getApplication()
                ->find('application:add')
                ->run(new ArrayInput([
                    'application' => $input->getArgument('application'),
                    '--archive-url' => $input->getArgument('archive'),
                ]), $output);
        } else {
            Util::extractArchive($input->getArgument('archive'), $application->getPath());
            $this->getApplication()
                ->find('application:add')
                ->run(new ArrayInput([
                    'application' => $input->getArgument('application'),
                ]), $output);
        }


        if(!$input->getOption('no-scripts')) {
            /*
             * Run post-extract script
             */
            return $this->getApplication()
                ->find('application:execute')
                ->run(new ArrayInput([
                    '--skip-missing' => true,
                    'script' => 'post-extract',
                    'applications' => [
                        $input->getArgument('application'),
                    ],
                ]), $output);
        }
        return 0;
    }
}
