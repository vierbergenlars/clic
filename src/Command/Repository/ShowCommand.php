<?php

namespace vierbergenlars\CliCentral\Command\Repository;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;

class ShowCommand extends AbstractMultiRepositoriesCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('repository:show')
            ->setDescription('Shows repository information')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows information about one or more repositories:

  <info>%command.full_name% -A</info>

If more than one repository is passed on the commandline, a table with basic information is shown.
All details for an repository are shown if exactly one repository name is used as argument.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(count($input->getArgument('repositories')) == 1&&!$input->getOption('all')) {
            $repositoryConfig = current($input->getArgument('repositories'));

            $output->writeln(sprintf('Private key file: <info>%s</info>', $repositoryConfig->getIdentityFile()));
            $output->writeln(sprintf('Ssh alias: <comment>%s</comment>', $repositoryConfig->getSshAlias()), OutputInterface::VERBOSITY_VERBOSE);
            $fingerPrint = $this->getSshFingerprint($repositoryConfig->getIdentityFile());
            array_unshift($fingerPrint, '%s <info>%s</info>  <comment>%s</comment> %s');
            $output->writeln(call_user_func_array('sprintf', $fingerPrint));
            $output->write(file_get_contents($repositoryConfig->getIdentityFile().'.pub'), false, OutputInterface::OUTPUT_RAW|OutputInterface::VERBOSITY_QUIET);
            $output->writeln(sprintf('Status: %s', $this->getStatusMessage($repositoryConfig)));
        } else {
            $table = new Table($output);
            $table->setHeaders(['Repository', 'SSH key', 'Status']);

            foreach($input->getArgument('repositories') as $repository => $repositoryConfig) {
                try {
                    /* @var $repositoryConfig RepositoryConfiguration */
                    $fingerPrint = $this->getSshFingerprint($repositoryConfig->getIdentityFile());
                    array_unshift($fingerPrint, $repositoryConfig->getIdentityFile());
                    array_unshift($fingerPrint, "<info>%s</info>\n%s <info>%s</info> %5\$s\n<comment>%4\$s</comment>");
                } catch(ProcessFailedException $ex) {
                    $fingerPrint = explode("\n", trim($ex->getProcess()->getErrorOutput()));
                    array_unshift($fingerPrint, trim(str_repeat("<error>%s</error>\n", count($fingerPrint))));
                }
                $table->addRow([
                    sprintf('%s' . PHP_EOL . '<comment>%s</comment>', $repository, $repositoryConfig->getSshAlias()),
                    str_replace("\n", PHP_EOL, call_user_func_array('sprintf', $fingerPrint)),
                    $this->getStatusMessage($repositoryConfig),
                ]);
            }

            $table->render();
        }
    }

    /**
     * @param string $file
     * @return array
     * @throws ProcessFailedException
     */
    private function getSshFingerprint($file)
    {
        return preg_split('/[ ]+/', trim(ProcessBuilder::create([
            'ssh-keygen',
            '-l',
            '-f',
            $file,
        ])->getProcess()->mustRun()->getOutput()));
    }

    private function getStatusMessage(RepositoryConfiguration $repositoryConfiguration)
    {
        if(!is_file($repositoryConfiguration->getIdentityFile()))
            return '<error>Missing private key file</error>';
        if(!is_file($repositoryConfiguration->getIdentityFile().'.pub'))
            return '<comment>Missing public key file</comment>';
        return '<info>OK</info>';
    }

}
