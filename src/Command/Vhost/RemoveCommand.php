<?php
/**
 * clic, user-friendly PHP application deployment and set-up
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Lars Vierbergen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

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
            ->setDescription('Remove web-accessible entrypoint to an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command removes a web-accessible entrypoint to an application:

  <info>%command.full_name% auth.vbgn.be</info>

This command removes a symbolic link in the directory set by <comment>vhosts-dir</comment>.
This command does not modify webserver configuration to direct a domain to the symbolic link, this should be done separately.

To prevent accidental removal of an externally changed symlink, its status and target directory are first verified
to match its expected values. To override these checks, use the <comment>--force</comment> option.

  <info>%command.full_name% -A --force</info>

To add a vhost, use the <info>vhost:add</info> command.
To disable a vhost, use the <info>vhost:disable</info> command.
To enable a vhost, use the <info>vhost:enable</info> command.
EOF
            )
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
