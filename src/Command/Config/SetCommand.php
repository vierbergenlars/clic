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

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Util;

class SetCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:set')
            ->addArgument('parameter', InputArgument::REQUIRED, 'Configuration parameter to set')
            ->addArgument('value', InputArgument::REQUIRED, 'Value to set configuration parameter to')
            ->setDescription('Sets configuration values')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command sets the configuration parameters from the global configuration file:

  <info>%command.full_name% config[ssh-dir] ~/.ssh</info>

<fg=red><options=underscore;bold>WARNING:</> This is a very powerful command, misconfiguration of parameters may break other commands.
Using this command is almost certainly not the right way to change settings; have another look at the other commands.</>

To read configuration parameters, use the <info>config:get</info> command.
To remove configuration parameters, use the <info>config:unset</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $configHelper->getConfiguration()->setConfigOption(Util::createPropertyPath($input->getArgument('parameter')), $input->getArgument('value'));
        $configHelper->getConfiguration()->write();
    }

}
