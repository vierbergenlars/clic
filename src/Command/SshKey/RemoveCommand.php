<?php

namespace vierbergenlars\CliCentral\Command\SshKey;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\Exception\File\UnwritableFileException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class RemoveCommand extends Command
{
    protected function configure()
    {
        $this->setName('sshkey:remove')
            ->addArgument('repository', InputArgument::REQUIRED, 'The repository to remove the ssh key and alias from')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $questionHelper = $this->getHelper('question');
        /* @var $questionHelper QuestionHelper */

        $repositoryConfig = $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'), true);

        if (!$questionHelper->ask($input, $output, new ConfirmationQuestion(sprintf('Are you sure you want to remove the ssh key for "%s"? This action is irreversible.', $input->getArgument('repository')))))
            return 1;

        $sshKeyFile = $repositoryConfig->getIdentityFile();
        $this->unlinkFile($output, $sshKeyFile);
        $this->unlinkFile($output, $sshKeyFile.'.pub');

        $sshConfig = new \SplFileInfo($configHelper->getConfiguration()->getSshDirectory() . '/config');
        if (!$sshConfig->isFile())
            throw new NotAFileException($sshConfig);
        if (!$sshConfig->isReadable())
            throw new UnreadableFileException($sshConfig);
        if (!$sshConfig->isWritable())
            throw new UnwritableFileException($sshConfig);

        $sshConfigLines = file($sshConfig->getPathname());

        for ($i = 0; $i < count($sshConfigLines); $i++) {
            if (rtrim($sshConfigLines[$i]) == 'Host ' . $repositoryConfig->getSshAlias()) {
                do {
                    unset($sshConfigLines[$i++]);
                } while (isset($sshConfigLines[$i]) && stripos($sshConfigLines[$i], 'Host ') !== 0 && stripos($sshConfigLines[$i], 'Match ') !== 0);
                $output->writeln(sprintf('Removed section <info>Host %s</info> from <info>%s</info>',$repositoryConfig->getSshAlias(), $sshConfig->getPathname()));
                break;
            }
        }

        file_put_contents($sshConfig->getPathname(), implode('', $sshConfigLines));

        $configHelper->getConfiguration()->removeRepositoryConfiguration($input->getArgument('repository'));
        $configHelper->getConfiguration()->write();
        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param $file
     */
    private function unlinkFile(OutputInterface $output, $file)
    {
        if (@unlink($file))
            $output->writeln(sprintf('Removed file <info>%s</info>', $file));
        else
            $output->writeln(sprintf('<error>Could not remove file %s</error>', $file));
    }
}
