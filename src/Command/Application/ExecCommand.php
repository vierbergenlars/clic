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

use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\NoScriptException;


class ExecCommand extends AbstractMultiApplicationsCommand
{
    protected function configure()
    {
        $this->setName('application:execute')
            ->addArgument('script', InputArgument::REQUIRED, 'The script to execute')
            ->addOption('skip-missing', null, InputOption::VALUE_NONE, 'Skips missing scripts instead of cancelling all scripts.')
            ->setDescription('Executes application scripts')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command executes scripts defined in the applications' <comment>.cliconfig.json</comment> file.

  <info>%command.full_name% update prod/authserver prod/dolibarr</info>

This executes the <comment>update</comment> script as defined in <comment>.cliconfig.json</comment> for both applications.
If the script does not exist in one of the applications, no scripts are executed for any application.
Errors that occur while running the script defined by an application will not affect scripts for other applications.
The scripts for the applications are executed in the order they are given on the commandline.

If non-existing scripts should not affect execution of scripts for other applications, use the <comment>--skip-missing</comment> option.
A message will be printed for the non-existing script, but execution of the scripts for other applications will continue:

  <info>%command.full_name% --skip-missing update prod/authserver prod/dolibarr</info>

<options=bold;underscore>Execution environment</>

The applications are executed with the application directory as working directory;
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
EOF
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apps = $input->getArgument('applications');

        $processes = array_filter(array_map(function(Application $app) use($input, $output){
            try {
                return $app->getScriptProcess($input->getArgument('script'));
            } catch(NoScriptException $ex) {
                if(!$input->getOption('skip-missing'))
                    throw $ex;
                $output->writeln(sprintf('<error>No "%s" script for application "%s"</error>', $input->getArgument('script'), $app->getName()));
            } catch(NotAFileException $ex) {
                if(!$input->getOption('skip-missing'))
                    throw $ex;
                $output->writeln(sprintf('<error>Application "%s" does not have a "%s" file</error>', $app->getName(), basename($ex->getFilename())));
            }
            return null;
        }, $apps));

        $processHelper =  $this->getHelper('process');
        /* @var $processHelper ProcessHelper */
        foreach($processes as $process) {
            /* @var $process Process */
            $processHelper->run($output, $process, null, null, OutputInterface::VERBOSITY_NORMAL);
        }

        return array_reduce($processes, function($val, Process $process) {
            return $val || !$process->isSuccessful();
        }, 0);
    }
}
