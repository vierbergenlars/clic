<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\File\FileException;
use vierbergenlars\CliCentral\Exception\File\InvalidLinkTargetException;
use vierbergenlars\CliCentral\Exception\File\NotALinkException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class RemoveCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:remove')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force remove vhost, even if link target does not match expected target.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $exitCode = 0;

        foreach($input->getArgument('vhosts') as $vhostConfig) {
            /* @var $vhostConfig VhostConfiguration */
            try {
                if (!$vhostConfig->getLink()->isLink())
                    throw new NotALinkException($vhostConfig->getLink());
                if (!$input->getOption('force')) {
                    if ($vhostConfig->getTarget()->getPathname() !== $vhostConfig->getLink()->getLinkTarget())
                        throw new InvalidLinkTargetException($vhostConfig->getLink(), $vhostConfig->getTarget());
                }
                FsUtil::unlink($vhostConfig->getLink());
                $output->writeln(sprintf('Removed vhost <info>%s</info>', $vhostConfig->getName()));
                $configHelper->getConfiguration()->removeVhostConfiguration($vhostConfig->getName());
            } catch(FileException $ex) {
                $output->writeln(sprintf('<error>Could not remove vhost "%s": %s</error>', $vhostConfig->getName(), $ex->getMessage()));
                $exitCode = 1;
            }
        }
        $configHelper->getConfiguration()->write();
        return $exitCode;
    }

}
