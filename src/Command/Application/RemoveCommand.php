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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Helper\ProcessHelper;

class RemoveCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:remove')
            ->setAliases([
                'application:rm',
            ])
            ->addArgument('application', InputArgument::REQUIRED, 'Application name')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Permanently remove the application directory')
            ->addOption('remove-vhosts', null, InputOption::VALUE_NONE, 'Remove vhosts that point to the application')
            ->setDescription('Removes an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command removes an application to the list of managed applications:

  <info>%command.full_name% authserver</info>

To also remove the application directory and its contents, use the <comment>--purge</comment> option:

  <info>%command.full_name% authserver --purge</info>

Applications that still has vhosts attached to it cannot be removed, but vhosts can be removed together with
the application with the <comment>--remove-vhosts</comment> option.

  <info>%command.full_name% authserver --remove-vhosts</info>

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper = $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        $appConfig = $configHelper->getConfiguration()->getApplication($input->getArgument('application'));

        $applicationVhosts = $appConfig->getVhosts();

        if($input->getOption('remove-vhosts') && count($applicationVhosts) > 0) {
            $this->getApplication()
                ->find('vhost:remove')
                ->run(new ArrayInput([
                    'vhosts' => array_map(function(VhostConfiguration $vhostConfiguration) {
                        return $vhostConfiguration->getName();
                    }, $applicationVhosts)
                ]), $output);
            $applicationVhosts = $appConfig->getVhosts();
        }

        if(count($applicationVhosts) > 0) {
            throw new \RuntimeException(sprintf('This application has active vhosts: %s', implode(', ', array_map(function(VhostConfiguration $vhostConfig) {
                return $vhostConfig->getName();
            }, $applicationVhosts))));
        }


        $output->write(sprintf('Remove application <info>%s</info>...', $appConfig->getName()));
        if ($input->getOption('purge')) {
            $output->writeln(sprintf('Purging <comment>%s</comment>', $appConfig->getPath()));
            $processHelper->mustRun($output, ProcessBuilder::create(['rm', '-rf', $appConfig->getPath()])->getProcess());
        }
        $configHelper->getConfiguration()->removeApplication($appConfig->getName());
        $output->writeln('<info>OK</info>');

        $configHelper->getConfiguration()->write();
    }
}
