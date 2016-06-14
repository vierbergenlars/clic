<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\File\InvalidLinkTargetException;
use vierbergenlars\CliCentral\Exception\File\UndeletableFileException;
use vierbergenlars\CliCentral\Exception\File\UnwritableFileException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class DisableCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:disable')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip checks before overwriting symlink')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */


        $vhostConfigs = $input->getArgument('vhosts');
        if(!$input->getOption('force')) {
            $vhostConfigs = array_filter($vhostConfigs, function (VhostConfiguration $vhostConfiguration) {
                return !$vhostConfiguration->isDisabled();
            });
        }

        foreach($vhostConfigs as $vhost => $vhostConfig) {
            /* @var $vhostConfig VhostConfiguration */
            if($vhostConfig->getLink()->isLink()&&$vhostConfig->getLink()->getLinkTarget() === $vhostConfig->getTarget()->getPathname()) {
                if (!@unlink($vhostConfig->getLink()))
                    throw new UndeletableFileException($vhostConfig->getLink());
                else
                    $output->writeln(sprintf('Removed <info>%s</info>', $vhostConfig->getLink()), OutputInterface::VERBOSITY_VERBOSE);
            } else {
                throw new InvalidLinkTargetException($vhostConfig->getLink(), $vhostConfig->getTarget());
            }
            if(!@symlink($vhostConfig->getLink(), $vhostConfig->getLink())) {
                throw new UnwritableFileException($vhostConfig->getLink());
            } else {
                $output->writeln(sprintf('Linked <info>%s</info> to itself', $vhostConfig->getLink()), OutputInterface::VERBOSITY_VERBOSE);
                $vhostConfig->setDisabled(true);
                $configHelper->getConfiguration()->setVhostConfiguration($vhost, $vhostConfig);
                $output->writeln(sprintf('Disabled vhost <info>%s</info>', $vhost));
            }
        }

        $configHelper->getConfiguration()->write();
    }
}