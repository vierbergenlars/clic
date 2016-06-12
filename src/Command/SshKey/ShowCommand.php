<?php

namespace vierbergenlars\CliCentral\Command\SshKey;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class ShowCommand extends Command
{
    protected function configure()
    {
        $this->setName('sshkey:show')
            ->addArgument('repository', InputArgument::OPTIONAL, 'The repository to show the ssh information for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        if($input->getArgument('repository')) {
            $repositoryConfig = $configHelper->getConfiguration()->getRepositoryConfiguration($input->getArgument('repository'), true);

            if($output instanceof ConsoleOutputInterface) {
                $stderr = $output->getErrorOutput();
            } else {
                $stderr = $output;
            }
            $stderr->writeln(sprintf('Private key file: <info>%s</info>', $repositoryConfig->getIdentityFile()));
            $stderr->writeln(sprintf('Ssh alias: <comment>%s</comment>', $repositoryConfig->getSshAlias()), OutputInterface::VERBOSITY_VERBOSE);
            $fingerPrint = $this->getSshFingerprint($repositoryConfig->getIdentityFile());
            array_unshift($fingerPrint, '%s <info>%s</info>  <comment>%s</comment> %s');
            $stderr->writeln(call_user_func_array('sprintf', $fingerPrint));
            $output->write(file_get_contents($repositoryConfig->getIdentityFile().'.pub'), false, OutputInterface::OUTPUT_RAW|OutputInterface::VERBOSITY_QUIET);
        } else {
            $repositoryConfigs = $configHelper->getConfiguration()->getRepositoryConfigurations();
            $table = new Table($output);
            $table->setHeaders(['Repository', 'SSH key']);

            foreach($repositoryConfigs as $repository => $repositoryConfig) {
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
                    str_replace("\n", PHP_EOL, call_user_func_array('sprintf', $fingerPrint))
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

}
