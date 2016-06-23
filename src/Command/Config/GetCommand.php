<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class GetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:get')
            ->addArgument('parameter', InputArgument::OPTIONAL, 'Configuration parameter to get')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Function(s) used to filter parameter values')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $config = $configHelper->getConfiguration();

        $config = $config->getConfigOption(Util::createPropertyPath($input->getArgument('parameter')));

        foreach($input->getOption('filter') as $fn) {
            try {
                if(!is_callable($fn))
                    throw new \InvalidArgumentException(sprintf('"%s" is not callable', $fn));
                if(!function_exists($fn))
                    throw new \InvalidArgumentException(sprintf('"%s" does not exist', $fn));
                $prevError = error_get_last();
                $config = @$fn($config);
                $currError = error_get_last();
                if($prevError !== $currError)
                    throw new \InvalidArgumentException(sprintf('"%s" failed: %s', $fn, error_get_last()['message']));
            } catch(\Exception $ex) {
                throw new \InvalidArgumentException(sprintf('Filtering with function "%s" did not work', $fn), 0, $ex);
            }
        }

        if(is_null($config)) {
            // noop
        } elseif(is_scalar($config)) {
            $output->writeln($config);
        } else {
            foreach(self::flatten($config) as $parameter=> $value) {
                $output->writeln(sprintf('%s: %s', $parameter, $value));
            }
        }
    }

    private static function flatten($source, array &$target = [], $prefix = null)
    {
        foreach($source as $key=>$value) {
            if(!is_scalar($value))
                self::flatten($value, $target, $prefix?($prefix.'['.$key.']'):$key);
            else
                $target[$prefix?($prefix.'['.$key.']'):$key] = $value;
        }
        return $target;
    }

}
