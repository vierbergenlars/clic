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

namespace vierbergenlars\CliCentral\Command\Repository;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

abstract class AbstractMultiRepositoriesCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('repositories', InputArgument::IS_ARRAY)
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Use all repositories')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $repoConfigs = array_map(function($repoName) use($configHelper) {
            return $configHelper->getConfiguration()->getRepositoryConfiguration($repoName);
        }, array_combine($input->getArgument('repositories'), $input->getArgument('repositories')));
        if($input->getOption('all')) {
            $repoConfigs = array_merge($repoConfigs, $configHelper->getConfiguration()->getRepositoryConfigurations());
        }
        $input->setArgument('repositories', $repoConfigs);

        if(!count($input->getArgument('repositories'))&&!$input->getOption('all'))
            throw new \RuntimeException('No repositories specified. (Add some as arguments, or use --all|-A)');
    }
}
