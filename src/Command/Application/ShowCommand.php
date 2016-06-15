<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\Application;
use vierbergenlars\CliCentral\Configuration\ApplicationConfiguration;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class ShowCommand extends AbstractMultiApplicationsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('application:show')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(count($input->getArgument('applications')) == 1&&!$input->getOption('all')) {
            $applicationConfig = current($input->getArgument('applications'));
            /* @var $applicationConfig Application */
            $output->writeln(sprintf('Path: <comment>%s</comment>', $applicationConfig->getPath()));
            $output->writeln(sprintf('Repository: <info>%s</info>', $this->getRepositoryName($applicationConfig)));
            foreach($this->getLinkedVhosts($applicationConfig->getName()) as $vhostName => $vhostConfig) {
                /* @var $vhostConfig VhostConfiguration */
                $output->writeln(sprintf('Vhost: <info>%s</info> (%s)', $vhostName, $vhostConfig->getStatusMessage()));
            }
            $output->writeln(sprintf('Status: %s', $this->getStatusMessage($applicationConfig)));
        } else {
            $applicationConfigs = $input->getArgument('applications');
            $table = new Table($output);
            $table->setHeaders(['Application', 'Repository', 'Vhosts', 'Status']);

            foreach($applicationConfigs as $appName => $applicationConfig) {
                /* @var $applicationConfig Application */

                $vhostMessage = [];
                $vhosts = $this->getLinkedVhosts($appName);
                foreach($vhosts as $vhostName => $vhostConfig) {
                    /* @var $vhostConfig VhostConfiguration */
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
            return '<error>Not a directory</error>';
        if(!$this->isAppInAppdir($applicationConfig))
            return '<comment>Outside application directory</comment>';
        return '<info>OK</info>';
    }

    private function isAppInAppdir(ApplicationConfiguration $applicationConfiguration)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $appDir = $configHelper->getConfiguration()->getApplicationsDirectory();
        $path = realpath($applicationConfiguration->getPath());
        while(($parentPath = dirname($path)) !== $path) {
            if($parentPath === $appDir)
                return true;
            $path = $parentPath;
        }
        return false;
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
            $repoConfig = $configHelper->getConfiguration()->getRepositoryConfiguration($repoName, true);
            if(is_file($repoConfig->getIdentityFile()))
                return $repoName;
        } catch(NoSuchRepositoryException $ex) {
            // no op
        }
        return sprintf('<error>%s</error>', $repoName);
    }
}