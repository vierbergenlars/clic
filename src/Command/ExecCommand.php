<?php

namespace vierbergenlars\CliCentral\Command;

use vierbergenlars\CliCentral\ApplicationEnvironment\Application;
use vierbergenlars\CliCentral\Exception\NoScriptException;
use vierbergenlars\CliCentral\Exception\NotAFileException;
use vierbergenlars\CliCentral\Helper\AppDirectoryHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class ExecCommand extends Command
{
    protected function configure()
    {
        $this->setName('exec')
            ->addOption('all-apps', 'A', InputOption::VALUE_NONE, 'Execute the script on all applications')
            ->addArgument('script', InputArgument::REQUIRED, 'The script to execute')
            ->addArgument('apps', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'Applications to execute the script on')
            ->addOption('skip-missing', null, InputOption::VALUE_NONE, 'Skips missing scripts.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appDirectoryHelper = $this->getHelper('app_directory');
        /* @var $appDirectoryHelper AppDirectoryHelper */
        $env = $appDirectoryHelper->getEnvironment();
        $apps = $input->getOption('all-apps')?$env->getApplications(): [];
        $apps = array_merge($apps, array_map(function($appName) use($env) {
            return $env->getApplication($appName);
        }, $input->getArgument('apps')));

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
