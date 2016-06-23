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
            ->setDescription('Shows variable value for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows the value of a variable for an application:

  <info>%command.full_name% prod/authserver database_name</info>

All variables contain plain text, arrays or objects are not permitted.
Variables are first looked up in the application's <comment>.cliconfig.json</comment> (taking into account overrides).
If the variable is not found there, the variable is looked up in the <info>global-vars</info> section of the global configuration file.

Use the <comment>--filter</comment> option to process variables before outputting them.

  <info>%command.full_name% prod/authserver database_name --filter=json_encode</info>

Multiple filters can be chained, and must accept the output of the previous function as first and only argument.
The first filter in the chain must accept the type of the variable (string, integer or \stdClass for objects).
The last filter in the chain must return a string, array, \stdClass or \Traversable of values to be printed, or null to print nothing.

To set variables, use the <info>application:variable:set</info> command.
EOF
            )
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
