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
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class ShowCommand extends Command
{
    protected function configure()
    {
        $this->setName('repository:show')
            ->addArgument('repository', InputArgument::REQUIRED, 'The repository to show information for')
            ->setDescription('Shows repository information')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows information about a repository:

  <info>%command.full_name% authserver</info>

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $repositoryConfig = $configHelper->getConfiguration()
            ->getRepositoryConfiguration($input->getArgument('repository'));

        $output->writeln(sprintf('Private key file: <info>%s</info>', $repositoryConfig->getIdentityFile()));
        $output->writeln(sprintf('Ssh alias: <comment>%s</comment>', $repositoryConfig->getSshAlias()), OutputInterface::VERBOSITY_VERBOSE);
        try {
            $output->writeln(sprintf('Fingerprint: <comment>%s</comment>', $repositoryConfig->getSshFingerprint()));
            $output->write(FsUtil::file_get_contents($repositoryConfig->getIdentityFile() . '.pub'), false, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
        } finally {
            $output->writeln(sprintf('Status: %s', $repositoryConfig->getStatusMessage()));
        }
    }

}
