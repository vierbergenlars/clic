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
use vierbergenlars\CliCentral\Configuration\ApplicationConfiguration;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class ShowCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:show')
            ->addArgument('application', InputArgument::REQUIRED, 'The application to show information for')
            ->setDescription('Shows application information')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows details about one application

  <info>%command.full_name% authserver</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $applicationConfig = $configHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $output->writeln(sprintf('Path: <comment>%s</comment>', $applicationConfig->getPath()));
        $output->writeln(sprintf('Repository: <info>%s</info>', $this->getRepositoryName($applicationConfig)));
        try {
            $output->writeln(sprintf('Configuration file override: <comment>%s</comment>', $applicationConfig->getConfigurationFileOverride()));
        } catch(MissingConfigurationParameterException $ex) {
            // noop
        }
        foreach($this->getLinkedVhosts($applicationConfig->getName()) as $vhostConfig) {
            /* @var $vhostConfig VhostConfiguration */
            $output->writeln(sprintf('Vhost: <info>%s</info> (%s)', $vhostConfig->getName(), $vhostConfig->getStatusMessage()));
        }
        $output->writeln(sprintf('Status: %s', $this->getStatusMessage($applicationConfig)));
    }

    /**
     * @param $applicationConfig
     * @return string
     */
    private function getStatusMessage(ApplicationConfiguration $applicationConfig)
    {
        $path = new \SplFileInfo($applicationConfig->getPath());
        if(!$path->isDir())
            $status = '<error>Not a directory</error>';
        else
            $status = '<info>OK</info>';
        try {
            $applicationConfig->getConfigurationFileOverride();
            $status.= ', <comment>Application config file override active</comment>';
        } catch(MissingConfigurationParameterException $ex) {
            // noop
        }
        return $status;
    }

    private function getLinkedVhosts($appName)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        return array_filter($configHelper->getConfiguration()->getVhostConfigurations(), function(VhostConfiguration $vhostConfiguration) use($appName) {
            return $vhostConfiguration->getApplication() === $appName;
        });
    }

    private function getRepositoryName(ApplicationConfiguration $applicationConfig)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $repoName = $applicationConfig->getRepository();
        if (!$repoName)
            return '<comment>None</comment>';
        if (!Util::isSshRepositoryUrl($repoName))
            return $repoName;
        try {
            $repoConfig = $configHelper->getConfiguration()->getRepositoryConfiguration($repoName);
            if(is_file($repoConfig->getIdentityFile()))
                return $repoName;
        } catch(NoSuchRepositoryException $ex) {
            // no op
        }
        return sprintf('<error>%s</error>', $repoName);
    }
}
