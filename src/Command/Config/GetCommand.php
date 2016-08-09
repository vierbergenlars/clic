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

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class GetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:get')
            ->addArgument('parameter', InputArgument::OPTIONAL, 'Configuration parameter to get')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Function(s) used to filter parameter values')
            ->setDescription('Shows configuration value')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows the configuration parameters from the global configuration file:

  <info>%command.full_name% config[applications-dir]</info>

Use the <comment>--filter</comment> option to process configuration parameters before outputting them.

  <info>%command.full_name% applications --filter=json_encode</info>

Multiple filters can be chained, and must accept the output of the previous function as first and only argument.
The first filter in the chain must accept the type of the variable (string, integer or \stdClass for objects).
The last filter in the chain must return a string, array, \stdClass or \Traversable of values to be printed, or null to print nothing.

A more advanced use case shows a list of all registered applications:

  <info>%command.full_name% applications --filter=get_object_vars --filter=array_keys --filter=json_encode</info>

To set configuration parameters, use the <info>config:set</info> command.
To remove configuration parameters, use the <info>config:unset</info> command.
EOF
            )
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
