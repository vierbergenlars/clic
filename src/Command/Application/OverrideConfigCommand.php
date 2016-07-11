<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class OverrideConfigCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:override:config')
            ->addArgument('application', InputArgument::REQUIRED, 'The application to add an override to')
            ->addArgument('config-file', InputArgument::REQUIRED, 'The configuration file to use for the application')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The type of the config-file argument (file,http,git)', 'file')
            ->setDescription('Changes the configuration file for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command changes the configuration file for the application,
effectively overriding all settings in the packaged <comment>.cliconfig.json</comment>:

  <info>%command.full_name% authserver ~/clic-overrides/authserver/cliconfig.json</info>

Automatically downloading the override files from an url is also possible with git, or by downloading an archive over http(s):

  <info>%command.full_name% authserver --type git https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae</info>
  <info>%command.full_name% authserver --type http https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae/archive/f371b4ad7130e1d528e99e2f888bb7e6d36b129e.zip

Accepted archive formats are: zip, rar, tar, tar.gz, tar.bz2, tar.xz, tar.Z.

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
        $processHelper = $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        $globalConfiguration = $configHelper->getConfiguration();
        $application = $globalConfiguration->getApplication($input->getArgument('application'));
        $configFile = $input->getArgument('config-file');
        switch($input->getOption('type')) {
            case 'git':
                $overridesDirectory = $globalConfiguration->getOverridesDirectory();
                $configDir = $overridesDirectory.'/'.sha1($configFile). '-'. basename($configFile, '.git');
                if(!is_dir($configDir)) {
                    FsUtil::mkdir($configDir);

                    /*
                     * Run a git clone for the application
                     */
                    $prevVerbosity = $output->getVerbosity();
                    $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
                    $gitClone = ProcessBuilder::create([
                        'git',
                        'clone',
                        $configFile,
                        $configDir
                    ])->setTimeout(null)->getProcess();
                    $processHelper->mustRun($output, $gitClone);
                    $output->setVerbosity($prevVerbosity);
                } else {
                    /*
                     * Run a git pull for the application
                     */
                    $prevVerbosity = $output->getVerbosity();
                    $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
                    $gitPull = ProcessBuilder::create([
                        'git',
                        'pull',
                    ])->setTimeout(null)->setWorkingDirectory($configDir)->getProcess();
                    $processHelper->mustRun($output, $gitPull);
                    $output->setVerbosity($prevVerbosity);
                }
                $configFile = $configDir;
                break;
            case 'http':
                $overridesDirectory = $globalConfiguration->getOverridesDirectory();
                $configDir = $overridesDirectory.'/'.sha1($configFile). '-'. basename($configFile);
                FsUtil::mkdir($configDir);
                /*
                 * Wget the file
                 */
                $prevVerbosity = $output->getVerbosity();
                $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
                $gitClone = ProcessBuilder::create([
                    'wget',
                    $configFile,
                    '-O',
                    $configDir.'/'.basename($configFile),
                ])->setTimeout(null)->getProcess();
                $processHelper->mustRun($output, $gitClone);
                $output->setVerbosity($prevVerbosity);
                $configFile = $configDir.'/'.basename($configFile);
                break;
            case 'file':
                break;
            default:
                throw new \InvalidArgumentException('--type must be one of git, http, file');
        }

        $configFile = $this->extractFile($output, $configFile);

        if($configFile) {
            $configFile = new \SplFileInfo($configFile);
            if(!$configFile->isFile())
                throw new NotAFileException($configFile);
            if(!$configFile->isReadable())
                throw new UnreadableFileException($configFile);
            $configFile = $configFile->getRealPath();
        }

        $application->setConfigurationFileOverride($configFile);

        $globalConfiguration->write();

        $output->writeln(sprintf('Registered <comment>%s</comment> as override file for <info>%s</info>', $configFile, $application->getName()));

    }

    private function extractFile(OutputInterface $output, $configFile)
    {
        if (is_file($configFile)) {
            try {
                Util::extractArchive($configFile, dirname($configFile));
                FsUtil::unlink($configFile);
                $configFile = dirname($configFile);
            } catch(UnreadableFileException $ex) {
                $data = @json_decode(file_get_contents($configFile));
                if($data !== null)
                    return $configFile;
                throw new \RuntimeException(sprintf('Cannot handle format of %s', $configFile), 0, $ex);
            }
        }

        if(is_dir($configFile)) {
            return $this->findConfigJsonInDirectory($configFile);
        }

        throw new \RuntimeException(sprintf('Could not identify the override file, please add it manually. Files are in %s', $configFile));
    }

    private function findConfigJsonInDirectory($directory, $depth = 0)
    {
        if($depth > 20)
            throw new \RuntimeException(sprintf('Could not determine correct override file in %s (search depth exceeded)', $directory));
        /*
         * Find override file
         */
        $jsonFiles = Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->depth($depth)
            ->name('/\.json$/i')
            ->in($directory);

        switch(count($jsonFiles)) {
            case 0:
                return $this->findConfigJsonInDirectory($directory, $depth+1);
            case 1:
                foreach($jsonFiles as $f)
                    return (string)$f;
                break;
            default:
                $clicFiles = $jsonFiles->name('clic');
                switch(count($clicFiles)) {
                    case 0:
                        return $this->findConfigJsonInDirectory($directory, $depth+1);
                    case 1:
                        foreach($jsonFiles as $f)
                            return (string)$f;
                }
        }
        throw new \RuntimeException(sprintf('Could not determine correct override file in %s', $directory));
    }
}
