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

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

abstract class AbstractMultiVhostsCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('vhosts', InputArgument::IS_ARRAY)
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Use all vhosts')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $vhostConfigs = array_map(function($vhostName) use($configHelper) {
            return $configHelper->getConfiguration()->getVhostConfiguration($vhostName);
        }, array_combine($input->getArgument('vhosts'), $input->getArgument('vhosts')));
        if($input->getOption('all')) {
            $vhostConfigs = array_merge($vhostConfigs, $configHelper->getConfiguration()->getVhostConfigurations());
        }
        $input->setArgument('vhosts', $vhostConfigs);

        if(!count($input->getArgument('vhosts'))&&!$input->getOption('all'))
            throw new \RuntimeException('No vhosts specified. (Add some as arguments, or use --all|-A)');
    }
}
