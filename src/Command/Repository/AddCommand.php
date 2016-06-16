<?php

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
            ->addArgument('repository', InputArgument::REQUIRED, 'The repository to add the ssh key and alias to')
            ->addArgument('key', InputArgument::REQUIRED, 'The private key file to add to the repository')
            ->addOption('alias', null, InputOption::VALUE_REQUIRED, 'The alias to use for the repository')
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
