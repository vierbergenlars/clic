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
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Helper\ProcessHelper;


class ExecCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:execute')
            ->addArgument('application', InputArgument::REQUIRED, 'Application name')
            ->addArgument('script', InputArgument::REQUIRED, 'The script to execute')
            ->setDescription('Executes application scripts')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command executes scripts defined in the applications' <comment>.cliconfig.json</comment> file.

  <info>%command.full_name% prod/authserver update</info>

This executes the <comment>update</comment> script as defined in <comment>.cliconfig.json</comment>.

<options=bold;underscore>Execution environment</>

The scripts are executed with the application directory as working directory;
all paths are relative to the application directory.

Environment variables are passed through, with following additions/changes:
  <info>CLIC</info>               Contains the commandline to execute the currenty running program.
  <info>CLIC_APPNAME</info>       Contains the name of the application as passed on the commandline.
  <info>CLIC_CONFIG</info>        The file where global configuration is stored.
  <info>CLIC_APPCONFIG_DIR</info> The directory that contains the applications <comment>.cliconfig.json</comment> file.

Standard in/out/err are not attached to the terminal, commands that require user input
have to explicitly use <comment>/dev/tty</comment> as in or output.

  {
    "scripts": {
      "info": "$CLIC application:show $CLIC_APPNAME",
      "shell": "bash <\/dev\/tty >\/dev\/tty 2>\/dev\/tty"
    }
  }

It is recommended to put more complex scripts in separate files and to reference
them from <comment>.cliconfig.json</comment> instead of defining them inline.

The scripts' exit code is used as exit code of this command.
EOF
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $globalConfiguration = $configHelper->getConfiguration();
        $application = $globalConfiguration->getApplication($input->getArgument('application'));

        $process = $application->getScriptProcess($input->getArgument('script'));

        $processHelper =  $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        $processHelper->mustRun($output, $process, null, null, OutputInterface::VERBOSITY_NORMAL, OutputInterface::VERBOSITY_NORMAL);

        return $process->getExitCode();
    }
}
