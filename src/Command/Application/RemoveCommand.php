<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class RemoveCommand extends AbstractMultiApplicationsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('application:remove')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Permanently remove the application directory')
            ->setDescription('Removes an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command removes an application to the list of managed applications:

  <info>%command.full_name% authserver</info>

To also remove the application directory and its contents, use the <comment>--purge</comment> option:

  <info>%command.full_name% authserver --purge</info>

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper = $this->getHelper('process');
        /* @var $processHelper ProcessHelper */

        foreach ($input->getArgument('applications') as $appConfig) {
            /* @var $appConfig Application */
            $output->write(sprintf('Remove application <info>%s</info>...', $appConfig->getName()));
            if ($input->getOption('purge')) {
                $output->writeln(sprintf('Purging <comment>%s</comment>', $appConfig->getPath()));
                $processHelper->mustRun($output, ProcessBuilder::create(['rm', '-rf', $appConfig->getPath()])->getProcess());
            }
            $configHelper->getConfiguration()->removeApplication($appConfig->getName());
            $output->writeln('<info>OK</info>');
        }

        $configHelper->getConfiguration()->write();
    }
}
