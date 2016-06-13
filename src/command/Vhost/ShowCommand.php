<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class ShowCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:show')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        if(count($input->getArgument('vhosts')) == 1) {
            $vhostConfig = current($input->getArgument('vhosts'));
            /* @var $vhostConfig VhostConfiguration */
            $output->writeln(sprintf('Application: <info>%s</info>', $vhostConfig->getApplication()));
            $output->writeln(sprintf('Environment: <comment>%s</comment>', $vhostConfig->getEnvironment()));
            $vhostLink = $vhostConfig->getLink();
            $vhostTarget = $vhostConfig->getTarget();
            $messages = [sprintf('Link: %s', $vhostConfig->getLink())];
            if(!$vhostLink->isLink())
                $messages[] = '<error>(Not a link)</error>';
            $output->writeln(implode(' ', $messages));
            if($vhostLink->isLink()&&!$vhostConfig->isDisabled()&&$vhostLink->getLinkTarget() !== $vhostTarget->getPathname()) {
                    $output->writeln(sprintf('Target: <error>%s</error> (Should be <info>%s</info>)', $vhostLink->getLinkTarget(), $vhostTarget->getPathname()));
            } else {
                $output->writeln(sprintf('Target: %s', $vhostTarget));
            }
            $output->writeln(sprintf('Status: %s', $this->getStatusMessage($vhostConfig)));
        } else {
            $vhostConfigs = $input->getArgument('vhosts');
            $table = new Table($output);
            $table->setHeaders(['Vhost', 'Application', 'Status']);

            foreach($vhostConfigs as $vhost => $vhostConfig) {
                /* @var $vhostConfig VhostConfiguration */
                $table->addRow([
                    $vhost,
                    sprintf('%s (<comment>%s</comment>)', $vhostConfig->getApplication(), $vhostConfig->getEnvironment()),
                    $this->getStatusMessage($vhostConfig)
                ]);
            }

            $table->render();
        }
    }

    /**
     * @param $vhostConfig
     * @return string
     */
    private function getStatusMessage(VhostConfiguration $vhostConfig)
    {
        $vhostLink = $vhostConfig->getLink();
        $vhostTarget = $vhostConfig->getTarget();
        if (!$vhostLink->isLink() && !$vhostLink->isDir() && !$vhostLink->isFile())
            return '<error>Does not exist</error>';
        if (!$vhostLink->isLink())
            return sprintf('<error>Vhost is a %s, not a link</error>', $vhostLink->getType());
        if ($vhostConfig->isDisabled() && $vhostLink->getLinkTarget() !== $vhostLink->getPathname())
            return '<error>Not disabled correctly</error>';
        if (!$vhostConfig->isDisabled() && $vhostLink->getLinkTarget() !== $vhostTarget->getPathname())
            return '<error>Link target does not match expected target</error>';
        if ($vhostConfig->isDisabled())
            return '<comment>Disabled</comment>';
        return '<info>OK</info>';
    }
}
