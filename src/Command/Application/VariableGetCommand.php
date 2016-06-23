<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class VariableGetCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:variable:get')
            ->addArgument('application', InputArgument::REQUIRED)
            ->addArgument('variable', InputArgument::REQUIRED)
            ->addOption('filter', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Function(s) used to filter parameter values')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configurationHelper = $this->getHelper('configuration');
        /* @var $configurationHelper GlobalConfigurationHelper */
        $application = $configurationHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $variable = $application->getVariable($input->getArgument('variable'));
        foreach($input->getOption('filter') as $fn) {
            try {
                if(!is_callable($fn))
                    throw new \InvalidArgumentException(sprintf('"%s" is not callable', $fn));
                if(!function_exists($fn))
                    throw new \InvalidArgumentException(sprintf('"%s" does not exist', $fn));
                $prevError = error_get_last();
                $variable = @$fn($variable);
                $currError = error_get_last();
                if($prevError !== $currError)
                    throw new \InvalidArgumentException(sprintf('"%s" failed: %s', $fn, error_get_last()['message']));
            } catch(\Exception $ex) {
                throw new \InvalidArgumentException(sprintf('Filtering with function "%s" did not work', $fn), 0, $ex);
            }
        }
        if(!is_null($variable)) {
            $output->writeln($variable);
        }
    }

}
