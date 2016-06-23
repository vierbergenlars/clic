<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\File\InvalidLinkTargetException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class DisableCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:disable')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip checks before overwriting symlink')
            ->setDescription('Disables one or more vhosts')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command disables access to a vhost:

  <info>%command.full_name% auth.vbgn.be</info>

This command changes the symbolic link of the vhost to an inaccessible location, so it can no longer be accessed by
the webserver.

To prevent accidental removal of an externally changed symlink, its status and target directory are first verified
to match its expected values. To override these checks, use the <comment>--force</comment> option.

  <info>%command.full_name% -A --force</info>

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


        $vhostConfigs = $input->getArgument('vhosts');
        if(!$input->getOption('force')) {
            $vhostConfigs = array_filter($vhostConfigs, function (VhostConfiguration $vhostConfiguration) {
                return !$vhostConfiguration->isDisabled();
            });
        }

        foreach($vhostConfigs as $vhostConfig) {
            /* @var $vhostConfig VhostConfiguration */
            if($input->getOption('force')||($vhostConfig->getLink()->isLink()&&$vhostConfig->getLink()->getLinkTarget() === $vhostConfig->getTarget()->getPathname())) {
                FsUtil::unlink($vhostConfig->getLink());
                $output->writeln(sprintf('Removed <info>%s</info>', $vhostConfig->getLink()), OutputInterface::VERBOSITY_VERBOSE);
            } else {
                throw new InvalidLinkTargetException($vhostConfig->getLink(), $vhostConfig->getTarget());
            }
            $vhostConfig->setDisabled(true);
            FsUtil::symlink($vhostConfig->getTarget(), $vhostConfig->getLink());
            $output->writeln(sprintf('Linked <info>%s</info> to itself', $vhostConfig->getLink()), OutputInterface::VERBOSITY_VERBOSE);
            $output->writeln(sprintf('Disabled vhost <info>%s</info>', $vhostConfig->getName()));
        }

        $configHelper->getConfiguration()->write();
    }
}
