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
use vierbergenlars\CliCentral\Configuration\GlobalConfiguration;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class RollbackCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:rollback')
            ->addArgument('version', InputArgument::OPTIONAL, 'The configuration version to roll back to', 'prev')
            ->addOption('clic-dir', null, InputOption::VALUE_REQUIRED, 'Path to directory where clic stores its files (in case config file is unreadable)')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command rolls back the global configuration file to a previous version.

  <info>%command.full_name%</info>

The <info>version</info> argument allows to specify a specific version to roll back to.
Versions are stored in <options=bold>${clic-dir}</options=bold>/settings-backup/<options=bold>sha1($configFile)</options=bold>,
filenames are the unix timestamp of the moment te backup is taken.

If the global configuration file is no longer readable, you have to specify the <comment>clic-dir</comment>
configuration parameter on the commandline with <comment>--clic-dir</comment> option.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $clicDir = $input->getOption('clic-dir');

        if (!$clicDir) {
            $clicDir = $configHelper->getConfiguration()->getClicDirectory();
        }

        $backupFile = new \SplFileInfo($clicDir.'/settings-backup/'.sha1($configHelper->getConfigurationFile()->getPathname()).'/'.$input->getArgument('version'));

        if(!$backupFile->isFile())
            throw new NotAFileException($backupFile);

        try {
            $config = new GlobalConfiguration($backupFile);
            $config->getConfig();
        } catch(\Exception $ex) {
            $output->writeln(sprintf('<error>Cannot restore %s: %s</error>', $backupFile, $ex->getMessage()));
            return 1;
        }

        FsUtil::file_put_contents($configHelper->getConfigurationFile(), FsUtil::file_get_contents($backupFile));

        $output->writeln(sprintf('Restored %s to version %s', $configHelper->getConfigurationFile(), $backupFile->getRealPath()));
    }
}
