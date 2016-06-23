<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchVhostException;
use vierbergenlars\CliCentral\Exception\Configuration\VhostExistsException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;


class AddCommand extends Command
{
    protected function configure()
    {
        $this->setName('vhost:add')
            ->addArgument('vhost', InputArgument::REQUIRED, 'Vhost to add')
            ->addArgument('application', InputArgument::REQUIRED, 'Application to link to vhost')
            ->setDescription('Add web-accessible entrypoint to an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command adds a web-accessible entrypoint to the application:

  <info>%command.full_name% auth.vbgn.be prod/authserver</info>

This command adds a symbolic link in the directory set by <comment>vhosts-dir</comment>,
which links to the <comment>web-dir</comment> configured in the applications' <comment>.cliconfig.json</comment>.

This command does not modify webserver configuration to direct a domain to the symbolic link, this should be done separately.

To disable a vhost, use the <info>vhost:disable</info> command.
To enable a vhost, use the <info>vhost:enable</info> command.
To remove a vhost, use the <info>vhost:remove</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        try {
            $configHelper->getConfiguration()->getVhostConfiguration($input->getArgument('vhost'));
            throw new VhostExistsException($input->getArgument('vhost'));
        } catch(NoSuchVhostException $ex) {
            // noop
        }
        $application = $configHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $vhostConfig = VhostConfiguration::create($configHelper->getConfiguration(), $input->getArgument('vhost'), $application);

        if(!is_dir($vhostConfig->getLink()->getPath())) {
            FsUtil::mkdir($vhostConfig->getLink()->getPath(), true);
            $output->writeln(sprintf('Created directory <info>%s</info>', $vhostConfig->getLink()->getPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        FsUtil::symlink($vhostConfig->getTarget(), $vhostConfig->getLink());

        $output->writeln(sprintf('Created vhost <info>%s</info> for <info>%s</info> (<comment>%s</comment>)', $input->getArgument('vhost'), $vhostConfig->getApplication(), $vhostConfig->getTarget()));

        $configHelper->getConfiguration()->write();
    }

}
