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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Exception\File\NotEmptyException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\ExtractHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class ExtractCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:extract')
            ->setAliases([
                'application:unzip',
                'application:unpack',
            ])
            ->addArgument('archive', InputArgument::REQUIRED, 'The archive to extract the application from')
            ->addArgument('application', InputArgument::OPTIONAL, 'The name of the application to extract to. (Defaults to archive name)')
            ->addOption('override', 'o', InputOption::VALUE_REQUIRED, 'Override for the application\'s configuration')
            ->addOption('override-type', null, InputOption::VALUE_REQUIRED, 'Type of the override for the application')
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

The <comment>--override|-o</comment> and <comment>--override-type</comment> options allow to immediately add a
configuration override for the application. (See <info>application:override</info>)

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
        $extractHelper = $this->getHelper('extract');
        /* @var $extractHelper ExtractHelper */

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
            $outputFile = $extractHelper->downloadFile($input->getArgument('archive'), $output);
            $extractHelper->extractArchive($outputFile, $appDir, $output);

            $this->getApplication()
                ->find('application:add')
                ->run(new ArrayInput([
                    'application' => $input->getArgument('application'),
                    '--archive-url' => $input->getArgument('archive'),
                ]), $output);
        } else {
            $extractHelper->extractArchive($input->getArgument('archive'), $application->getPath(), $output);

            $this->getApplication()
                ->find('application:add')
                ->run(new ArrayInput([
                    'application' => $input->getArgument('application'),
                ]), $output);
        }

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
             * Run post-extract script
             */
            return $this->getApplication()
                ->find('application:execute')
                ->run(new ArrayInput([
                    'script' => 'post-extract',
                    'application' => $input->getArgument('application'),
                ]), $output);
        }
        return 0;
    }
}
