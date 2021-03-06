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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\File\FileException;
use vierbergenlars\CliCentral\Exception\File\FilesystemOperationFailedException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\ProcessHelper;

class FixCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:fix')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Remove existing files if they prevent the vhost from being fixed.')
            ->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Remove existing directories recursively if they prevent vhost from being fixed.')
            ->setDescription('Fixes one or more vhosts')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command fixes vhosts that do not match their configured settings:

  <info>%command.full_name% -A</info>

This command changes the symbolic link of the vhost to its correct location, so it can be accessed again by the webserver.

To prevent accidental modification of externally changed files (or directories that take the place of the original symlink),
no attempt is made to remove files or directories, unless the <comment>--force|-f</comment> option is used.

Directories containing files are only removed when also the <comment>--recursive|-r</comment> option is used.

  <info>%command.full_name% auth.vbgn.be --force --recursive</info>

To enable a vhost, use the <info>vhost:enable</info> command.
To remove a vhost, use the <info>vhost:remove</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processHelper = $this->getHelper('process');
        /* @var $processHelper ProcessHelper */


        $vhostsToFix = array_filter($input->getArgument('vhosts'), function(VhostConfiguration $vhostConfig) {
            return $vhostConfig->getErrorStatus() !== null;
        });

        $exitCode = 0;

        foreach($vhostsToFix as $vhostConfig) {
            /* @var $vhostConfig VhostConfiguration */
            $output->writeln(sprintf('<bg=blue;fg=white> FIX </> <fg=blue>%s</>', $vhostConfig->getName()));
            try {
                $vhostLink = $vhostConfig->getLink();
                if($input->getOption('force')) {
                    if($vhostLink->isLink()||$vhostLink->isFile()) {
                        FsUtil::unlink($vhostLink);
                        $output->writeln(sprintf('<bg=green;fg=white> OUT </> Removed file "%s"', $vhostLink), OutputInterface::VERBOSITY_VERBOSE);
                    } elseif($vhostLink->isDir()) {
                        try {
                            FsUtil::rmdir($vhostLink);
                            $output->writeln(sprintf('<bg=green;fg=white> OUT </> Removed directory "%s"', $vhostLink), OutputInterface::VERBOSITY_VERBOSE);
                        } catch(FilesystemOperationFailedException $ex) {
                            if(!$input->getOption('recursive'))
                                throw $ex;
                            $process = ProcessBuilder::create([
                                'rm',
                                '-rf',
                                $vhostLink->getPathname(),
                            ])->getProcess();
                            $processHelper->mustRun($output, $process);
                        }
                    }
                }
                FsUtil::symlink($vhostConfig->getTarget(), $vhostLink);
                $output->writeln(sprintf('<bg=green;fg=white> OUT </> Relinked "%s" to "%s"', $vhostLink, $vhostConfig->getTarget()), OutputInterface::VERBOSITY_VERBOSE);
                $output->writeln(sprintf('<bg=green;fg=white> RES </> <fg=green>Fixed vhost %s</>', $vhostConfig->getName()));
            } catch(FileException $ex) {
                $output->writeln(sprintf('<bg=red;fg=white> RES </> <fg=red>%s</>', $ex->getMessage()));
                $exitCode = 1;
            }
        }
        return $exitCode;
    }
}
