<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\PathUtil;

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

        if(count($input->getArgument('vhosts')) == 1&&!$input->getOption('all')) {
            $vhostConfig = current($input->getArgument('vhosts'));
            /* @var $vhostConfig VhostConfiguration */
            $output->writeln(sprintf('Application: <info>%s</info>', $vhostConfig->getApplication()));
            $vhostLink = $vhostConfig->getLink();
            $vhostTarget = $vhostConfig->getTarget();
            $messages = [sprintf('Link: %s', $vhostConfig->getLink())];
            if(!$vhostLink->isLink())
                $messages[] = '<error>(Not a link)</error>';
            if(!PathUtil::isSubDirectory($vhostConfig->getLink()->getPath(), $configHelper->getConfiguration()->getVhostsDirectory()))
                $messages[] = '<error>Outside configured vhosts-dir</error>';
            $output->writeln(implode(' ', $messages));
            if($vhostLink->isLink()&&$vhostLink->getLinkTarget() !== $vhostTarget->getPathname()) {
                    $output->writeln(sprintf('Target: <error>%s</error> (Should be <info>%s</info>)', $vhostLink->getLinkTarget(), $vhostTarget->getPathname()));
            } else {
                $output->writeln(sprintf('Target: %s', $vhostConfig->getOriginalTarget()));
            }
            $output->writeln(sprintf('Status: %s', $vhostConfig->getStatusMessage()));
        } else {
            $vhostConfigs = $input->getArgument('vhosts');
            $table = new Table($output);

            $table->setHeaders(['Vhost', 'Application', 'Status']);

            foreach($vhostConfigs as $vhost => $vhostConfig) {
                /* @var $vhostConfig VhostConfiguration */
                if(!PathUtil::isSubDirectory($vhostConfig->getLink()->getPath(), $configHelper->getConfiguration()->getVhostsDirectory()))
                    $vhost = sprintf('<error>%s</error>', $vhost);
                $table->addRow([
                    $vhost,
                    $vhostConfig->getApplication(),
                    $vhostConfig->getStatusMessage(),
                ]);
            }

            $table->render();
        }
    }
}
