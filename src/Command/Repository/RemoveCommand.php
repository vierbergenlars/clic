<?php

namespace vierbergenlars\CliCentral\Command\Repository;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\File\FilesystemOperationFailedException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\Exception\File\UnwritableFileException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class RemoveCommand extends AbstractMultiRepositoriesCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('repository:remove')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $questionHelper = $this->getHelper('question');
        /* @var $questionHelper QuestionHelper */

        $sshConfig = new \SplFileInfo($configHelper->getConfiguration()->getSshDirectory() . '/config');
        if (!$sshConfig->isFile())
            throw new NotAFileException($sshConfig);
        if (!$sshConfig->isReadable())
            throw new UnreadableFileException($sshConfig);
        if (!$sshConfig->isWritable())
            throw new UnwritableFileException($sshConfig);

        $sshConfigLines = file($sshConfig->getPathname());

        foreach($input->getArgument('repositories') as $repoName => $repositoryConfig) {
            /* @var $repositoryConfig RepositoryConfiguration */
            if (!$questionHelper->ask($input, $output, new ConfirmationQuestion(sprintf('Are you sure you want to remove the ssh key for "%s"? This action is irreversible.', $repoName))))
                return 1;

            $sshKeyFile = $repositoryConfig->getIdentityFile();
            $this->unlinkFile($output, $sshKeyFile);
            $this->unlinkFile($output, $sshKeyFile . '.pub');

            for ($i = 0; $i < count($sshConfigLines); $i++) {
                if (rtrim($sshConfigLines[$i]) == 'Host ' . $repositoryConfig->getSshAlias()) {
                    do {
                        unset($sshConfigLines[$i++]);
                    } while (isset($sshConfigLines[$i]) && stripos($sshConfigLines[$i], 'Host ') !== 0 && stripos($sshConfigLines[$i], 'Match ') !== 0);
                    $output->writeln(sprintf('Removed section <info>Host %s</info> from <info>%s</info>', $repositoryConfig->getSshAlias(), $sshConfig->getPathname()), OutputInterface::VERBOSITY_VERBOSE);
                    break;
                }
            }
            $output->writeln(sprintf('Removed repository <info>%s</info>', $repoName));
            $configHelper->getConfiguration()->removeRepositoryConfiguration($repoName);
        }

        file_put_contents($sshConfig->getPathname(), implode('', $sshConfigLines));
        $configHelper->getConfiguration()->write();
        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param $file
     */
    private function unlinkFile(OutputInterface $output, $file)
    {
        try {
            FsUtil::unlink($file);
            $output->writeln(sprintf('Removed file <info>%s</info>', $file));
        } catch(FilesystemOperationFailedException $ex) {
            $output->writeln(sprintf('<error>Could not remove file %s: %s</error>', $ex->getFilename(), $ex->getMessage()));
        }
    }
}
