<?php

namespace vierbergenlars\CliCentral\Command\Repository;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\Configuration\NoSshRepositoryException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Exception\Configuration\RepositoryExistsException;
use vierbergenlars\CliCentral\Exception\File\FileExistsException;
use vierbergenlars\CliCentral\Exception\File\OutsideConfiguredRootDirectoryException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class GenerateCommand extends Command
{
    protected function configure()
    {
        $this->setName('repository:generate-key')
            ->addArgument('key', InputArgument::OPTIONAL, 'The key file to generate')
            ->addOption('comment', 'C', InputOption::VALUE_REQUIRED, 'Comment to add to the generated key file')
            ->addOption('target-repository', 'R', InputOption::VALUE_REQUIRED, 'Repository to link the generated key to')
            ->addOption('print-public-key', 'P', InputOption::VALUE_NONE, 'Show the generated public key')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('target-repository')) {
            $configHelper = $this->getHelper('configuration');
            /* @var $configHelper GlobalConfigurationHelper */
            try {
                $configHelper->getConfiguration()->getRepositoryConfiguration($input->getOption('target-repository'));
                throw new RepositoryExistsException($input->getOption('target-repository'));
            } catch(NoSuchRepositoryException $ex) {
                // no op
            }
            if(!Util::isSshRepositoryUrl($input->getOption('target-repository')))
                throw new NoSshRepositoryException($input->getOption('target-repository'));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper =  $this->getHelper('process');
        /* @var $processHelper ProcessHelper */
        if($output instanceof ConsoleOutput)
            $stderr = $output->getErrorOutput();
        else
            $stderr = $output;

        $sshDir = $configHelper->getConfiguration()->getSshDirectory();

        $keyFile = new \SplFileInfo($input->getArgument('key')?:($sshDir.'/id_rsa-'.sha1(mt_rand().time())));
        // Check if file already exists
        try {
            $keyFile->getType(); // Throws if file does not exist.
            throw new FileExistsException($keyFile);
        } catch(\RuntimeException $ex) {
            // noop
        }

        OutsideConfiguredRootDirectoryException::assert($keyFile, 'ssh-dir', $configHelper->getConfiguration()->getSshDirectory());

        $sshKeygen = ProcessBuilder::create([
            'ssh-keygen',
            '-q',
            '-f',
            $keyFile,
            '-C',
            $input->getOption('comment'),
            '-N',
            ''
        ])->setTimeout(null)->getProcess();

        $processHelper->mustRun($output, $sshKeygen);
        $stderr->writeln(sprintf('Generated key <info>%s</info>', $keyFile->getPathname()), OutputInterface::VERBOSITY_VERBOSE);
        if($input->getOption('print-public-key')) {
            $output->write(file_get_contents($keyFile->getPathname().'.pub'), false, OutputInterface::OUTPUT_RAW|OutputInterface::VERBOSITY_QUIET);
        }


        if($input->getOption('target-repository')) {
            $this->getApplication()->find('repository:add')
                ->run(new ArrayInput([
                    'repository' => $input->getOption('target-repository'),
                    'key' => $keyFile->getPathname(),
                    '--alias' => preg_replace('/^id_rsa-/', '', $keyFile->getFilename()),
                ]), $output);
        }
    }

}
