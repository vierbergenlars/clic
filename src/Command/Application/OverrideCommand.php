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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\ExtractHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Helper\ProcessHelper;
use vierbergenlars\CliCentral\Util;

class OverrideCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:override')
            ->addArgument('application', InputArgument::REQUIRED, 'The application to add an override to')
            ->addArgument('config-file', InputArgument::REQUIRED, 'The configuration file to use for the application')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The type of the config-file argument (file,http,git)')
            ->setDescription('Changes the configuration file for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command changes the configuration file for the application,
effectively overriding all settings in the packaged <comment>.cliconfig.json</comment>:

  <info>%command.full_name% authserver ~/clic-overrides/authserver/cliconfig.json</info>

Automatically downloading the override files from an url is also possible with git, or by downloading an archive over http(s):

  <info>%command.full_name% owncloud https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae</info>
  <info>%command.full_name% owncloud https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae/archive/f371b4ad7130e1d528e99e2f888bb7e6d36b129e.zip

An attempt is made to automatically detect the type of configuration file.
Use the <comment>--type</comment> option to explicitly set the type.

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
        $extractHelper = $this->getHelper('extract');
        /* @var $extractHelper ExtractHelper */

        $globalConfiguration = $configHelper->getConfiguration();
        $application = $globalConfiguration->getApplication($input->getArgument('application'));
        $configFile = $input->getArgument('config-file');

        if($configFile) {
            if(!$input->getOption('type')) {
                // Attempt to detect the type of config-file if none is given
                if (is_file($input->getArgument('config-file'))) {
                    $input->setOption('type', 'file');
                } else {
                    try {
                        $repoParts = Util::parseRepositoryUrl($input->getArgument('config-file'));
                        if (in_array($repoParts['protocol'], ['git', 'ssh', 'rsync']))
                            $input->setOption('type', 'git');
                    } catch (\InvalidArgumentException $ex) {
                        $input->setOption('type', 'http');
                    }
                }
            }
            switch($input->getOption('type')?:'git') {
                case 'git':
                    try {
                        $configFile = $this->downloadGit($output, $configFile);
                    } catch(ProcessFailedException $ex) {
                        if($input->getOption('type'))
                            throw $ex;
                        $errorOutput = $ex->getProcess()->getErrorOutput();
                        if (preg_match('/fatal: repository .*not found/i', $errorOutput)) {
                            // "No such repository" when trying to clone a normal http url. Try downloading the file
                            $configFile = $extractHelper->downloadFile($configFile, $output);
                        } else {
                            throw $ex;
                        }
                    }
                    break;
                case 'http':
                    $configFile = $extractHelper->downloadFile($configFile, $output);
                    break;
                case 'file':
                    break;
                default:
                    throw new \InvalidArgumentException('--type must be one of git, http, file');
            }

            $configFile = $this->extractFile($output, $configFile);

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
            // Try to JSON decode the file first
            $data = @json_decode(file_get_contents($configFile));
            if($data !== null)
                return $configFile;
            // And then extract if it is not valid json
            $configHelper = $this->getHelper('configuration');
            /* @var $configHelper GlobalConfigurationHelper */
            $extractHelper = $this->getHelper('extract');
            /* @var $extractHelper ExtractHelper */
            $overridesDir = $configHelper->getConfiguration()->getOverridesDirectory();
            $overridesDir.='/'.sha1($configFile).'-'.basename($configFile);
            $extractHelper->extractArchive($configFile, $overridesDir, $output);
            FsUtil::unlink($configFile);
            $configFile = $overridesDir;
        }

        if(is_dir($configFile)) {
            return $this->findConfigJsonInDirectory($configFile);
        }

        throw new \RuntimeException(sprintf('Could not identify the override file, please add it manually. Files are in %s', $configFile));
    }

    private function findConfigJsonInDirectory($directory)
    {
        /*
         * Find override file
         */
        $jsonFiles = Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->depth(0)
            ->name('/\.json$/i')
            ->in($directory);

        switch(count($jsonFiles)) {
            case 0:
                break;
            case 1:
                foreach($jsonFiles as $f)
                    return (string)$f;
                break;
            default:
                $clicFiles = $jsonFiles->name('clic');
                switch(count($clicFiles)) {
                    case 1:
                        foreach($jsonFiles as $f)
                            return (string)$f;
                }
        }
        throw new \RuntimeException(sprintf('Could not determine correct override file in %s', $directory));
    }

    /**
     * @param OutputInterface $output
     * @param $configFile
     * @return string
     */
    protected function downloadGit(OutputInterface $output, $configFile)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper = $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        $globalConfiguration = $configHelper->getConfiguration();
        $overridesDirectory = $globalConfiguration->getOverridesDirectory();
        $configDir = $overridesDirectory . '/' . sha1($configFile) . '-' . basename($configFile, '.git');
        if (!is_dir($configDir)) {
            FsUtil::mkdir($configDir);

            /*
             * Run a git clone for the application
             */
            $gitClone = ProcessBuilder::create([
                'git',
                'clone',
                $configFile,
                $configDir
            ])->setTimeout(null)->getProcess();
            $processHelper->mustRun($output, $gitClone, null, null, OutputInterface::VERBOSITY_NORMAL, OutputInterface::VERBOSITY_NORMAL);
        } else {
            /*
             * Run a git pull for the application
             */
            $gitPull = ProcessBuilder::create([
                'git',
                'pull',
            ])->setTimeout(null)->setWorkingDirectory($configDir)->getProcess();
            $processHelper->mustRun($output, $gitPull, null, null, OutputInterface::VERBOSITY_NORMAL, OutputInterface::VERBOSITY_NORMAL);
        }
        $configFile = $configDir;
        return $configFile;
    }
}
