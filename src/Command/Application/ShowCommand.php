<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Configuration\ApplicationConfiguration;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class ShowCommand extends AbstractMultiApplicationsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('application:show')
            ->setDescription('Shows application information')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows information about one or more applications:

  <info>%command.full_name% -A</info>

If more than one application is passed on the commandline, a table with basic information is shown.
All details for an application are shown if exactly one application name is used as argument.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(count($input->getArgument('applications')) == 1&&!$input->getOption('all')) {
            $applicationConfig = current($input->getArgument('applications'));
            /* @var $applicationConfig Application */
            $output->writeln(sprintf('Path: <comment>%s</comment>', $applicationConfig->getPath()));
            $output->writeln(sprintf('Repository: <info>%s</info>', $this->getRepositoryName($applicationConfig)));
            try {
                $output->writeln(sprintf('Configuration file override: <comment>%s</comment>', $applicationConfig->getConfigurationFileOverride()));
            } catch(MissingConfigurationParameterException $ex) {
                // noop
            }
            foreach($this->getLinkedVhosts($applicationConfig->getName()) as $vhostConfig) {
                /* @var $vhostConfig VhostConfiguration */
                $output->writeln(sprintf('Vhost: <info>%s</info> (%s)', $vhostConfig->getName(), $vhostConfig->getStatusMessage()));
            }
            $output->writeln(sprintf('Status: %s', $this->getStatusMessage($applicationConfig)));
        } else {
            $applicationConfigs = $input->getArgument('applications');
            $table = new Table($output);
            $table->setHeaders(['Application', 'Repository', 'Vhosts', 'Status']);

            foreach($applicationConfigs as $applicationConfig) {
                /* @var $applicationConfig Application */
                $appName = $applicationConfig->getName();

                $vhostMessage = [];
                $vhosts = $this->getLinkedVhosts($appName);
                foreach($vhosts as $vhostConfig) {
                    /* @var $vhostConfig VhostConfiguration */
                    $vhostName = $vhostConfig->getName();
                    if($vhostConfig->getErrorStatus())
                        $style = 'error';
                    elseif($vhostConfig->isDisabled())
                        $style = 'comment';
                    else
                        $style = 'info';
                    $vhostMessage[] = sprintf('<%s>%s</%1$s>', $style, $vhostName);
                }

                $table->addRow([
                    $appName,
                    $this->getRepositoryName($applicationConfig),
                    implode(PHP_EOL, $vhostMessage),
                    $this->getStatusMessage($applicationConfig)
                ]);
            }

            $table->render();
        }
    }

    /**
     * @param $applicationConfig
     * @return string
     */
    private function getStatusMessage(ApplicationConfiguration $applicationConfig)
    {
        $path = new \SplFileInfo($applicationConfig->getPath());
        if(!$path->isDir())
            $status = '<error>Not a directory</error>';
        else
            $status = '<info>OK</info>';
        try {
            $applicationConfig->getConfigurationFileOverride();
            $status.= ', <comment>Application config file override active</comment>';
        } catch(MissingConfigurationParameterException $ex) {
            // noop
        }
        return $status;
    }

    private function getLinkedVhosts($appName)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        return array_filter($configHelper->getConfiguration()->getVhostConfigurations(), function(VhostConfiguration $vhostConfiguration) use($appName) {
            return $vhostConfiguration->getApplication() === $appName;
        });
    }

    private function getRepositoryName(ApplicationConfiguration $applicationConfig)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $repoName = $applicationConfig->getRepository();
        if (!$repoName)
            return '<comment>None</comment>';
        if (!Util::isSshRepositoryUrl($repoName))
            return $repoName;
        try {
            $repoConfig = $configHelper->getConfiguration()->getRepositoryConfiguration($repoName);
            if(is_file($repoConfig->getIdentityFile()))
                return $repoName;
        } catch(NoSuchRepositoryException $ex) {
            // no op
        }
        return sprintf('<error>%s</error>', $repoName);
    }
}
