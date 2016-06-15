<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use vierbergenlars\CliCentral\Command\Application\AbstractMultiApplicationsCommand;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\NoScriptException;
use vierbergenlars\CliCentral\Helper\DirectoryHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class ExecCommand extends AbstractMultiApplicationsCommand
{
    protected function configure()
    {
        $this->setName('application:execute')
            ->addArgument('script', InputArgument::REQUIRED, 'The script to execute')
            ->addOption('skip-missing', null, InputOption::VALUE_NONE, 'Skips missing scripts.')
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
