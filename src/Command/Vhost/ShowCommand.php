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

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;


class ShowCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:show')
            ->setDescription('Shows vhost information')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command shows information about one or more vhosts:

  <info>%command.full_name% -A</info>

If more than one vhost is passed on the commandline, a table with basic information is shown.
All details for an vhost are shown if exactly one vhost name is used as argument.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(count($input->getArgument('vhosts')) == 1&&!$input->getOption('all')) {
            $vhostConfig = current($input->getArgument('vhosts'));
            /* @var $vhostConfig VhostConfiguration */
            $output->writeln(sprintf('Application: <info>%s</info>', $vhostConfig->getApplication()));
            $vhostLink = $vhostConfig->getLink();
            $vhostTarget = $vhostConfig->getTarget();
            $messages = [sprintf('Link: %s', $vhostConfig->getLink())];
            if(!$vhostLink->isLink())
                $messages[] = '<error>(Not a link)</error>';
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

            foreach($vhostConfigs as $vhostConfig) {
                /* @var $vhostConfig VhostConfiguration */
                $table->addRow([
                    $vhostConfig->getName(),
                    $vhostConfig->getApplication(),
                    $vhostConfig->getStatusMessage(),
                ]);
            }

            $table->render();
        }
    }
}
