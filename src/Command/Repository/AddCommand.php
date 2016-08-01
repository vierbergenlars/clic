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
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\NoSshRepositoryException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Exception\Configuration\RepositoryExistsException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\Exception\File\UnwritableFileException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class AddCommand extends Command
{
    protected function configure()
    {
        $this->setName('repository:add')
            ->addArgument('repository', InputArgument::REQUIRED, 'The repository to add the ssh key to')
            ->addArgument('key', InputArgument::REQUIRED, 'The private key file to add to the repository')
            ->addOption('alias', null, InputOption::VALUE_REQUIRED, 'The alias to use for the repository')
            ->setDescription('Add deploy key to a repository')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command registers an existing ssh key as deploy key for a repository:

  <info>%command.full_name% prod/authserver ~/.ssh/id_rsa-authserver</info>

By default, the ssh alias is a randomly generated hexadecimal string, but it can be
set with the <comment>--alias</comment> option:

  <info>%command.full_name% prod/authserver ~/.ssh/id_rsa-authserver --alias=authserver-git</info>

To generate an ssh key for a repository, use the <info>repository:generate-key</info> command.
To remove an ssh key from a repository, use the <info>repository:remove</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        try {
            $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'));
            throw new RepositoryExistsException($input->getArgument('repository'));
        } catch(NoSuchRepositoryException $ex) {
            // no op
        }
        if(!Util::isSshRepositoryUrl($input->getArgument('repository')))
            throw new NoSshRepositoryException($input->getArgument('repository'));

        $repositoryConfig = new RepositoryConfiguration();
        $repositoryConfig->setSshAlias($input->getOption('alias')?:sha1($input->getArgument('repository').time()));
        $sshKeyFile = new \SplFileInfo($input->getArgument('key'));
        if(!$sshKeyFile->isFile())
            throw new NotAFileException($sshKeyFile);

        $repositoryConfig->setIdentityFile($sshKeyFile->getRealPath());

        $sshConfigFile = new \SplFileInfo($configHelper->getConfiguration()->getSshDirectory() . '/config');
        if(!file_exists($sshConfigFile->getPathname()))
            FsUtil::touch($sshConfigFile->getPathname());
        if (!$sshConfigFile->isFile())
            throw new NotAFileException($sshConfigFile);
        if (!$sshConfigFile->isReadable())
            throw new UnreadableFileException($sshConfigFile);
        if (!$sshConfigFile->isWritable())
            throw new UnwritableFileException($sshConfigFile);

        $repoParts = Util::parseRepositoryUrl($input->getArgument('repository'));
        $sshConfigFp = fopen($sshConfigFile, 'a');
        $lines = PHP_EOL.'Host '.$repositoryConfig->getSshAlias().PHP_EOL
            .'HostName '.$repoParts['host'].PHP_EOL
            .'User '.$repoParts['user'].PHP_EOL
            .'IdentityFile '.$repositoryConfig->getIdentityFile().PHP_EOL;

        if(fwrite($sshConfigFp, $lines) !== strlen($lines))
            throw new \RuntimeException(sprintf('Could not fully write ssh configuration to "%s"', $sshConfigFile));
        if(!fclose($sshConfigFp))
            throw new \RuntimeException(sprintf('Could not fully write ssh configuration to "%s"', $sshConfigFile));

        $output->writeln(sprintf('Added section <info>%s</info> to <info>%s</info>', 'Host '.$repositoryConfig->getSshAlias(), $sshConfigFile->getPathname()), OutputInterface::VERBOSITY_VERBOSE);

        $configHelper->getConfiguration()->setRepositoryConfiguration($input->getArgument('repository'), $repositoryConfig);
        $configHelper->getConfiguration()->write();
        $output->writeln(sprintf('Registered private key <info>%s</info> for repository <info>%s</info>', $repositoryConfig->getIdentityFile(), $input->getArgument('repository')));
        return 0;
    }

}
