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

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class VariableSetCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:variable:set')
            ->addArgument('application', InputArgument::REQUIRED)
            ->addArgument('variable', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->addOption('description', null, InputOption::VALUE_REQUIRED)
            ->addOption('default', null, InputOption::VALUE_REQUIRED, 'Default value')
            ->addOption('default-existing-value', null, InputOption::VALUE_NONE, 'Default to the existing value')
            ->addOption('if-not-exists', null, InputOption::VALUE_NONE, 'Do not set the variable if it already is set')
            ->addOption('if-not-global-exists', null, InputOption::VALUE_NONE, 'Do not set the variable if a global value is already set')
            ->setDescription('Sets variable value for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command sets the value of a variable for an application:

  <info>%command.full_name% prod/authserver database_name authserver</info>

All variables contain plain text, arrays or objects are not permitted.
Variables are stored in the overrides definition for the application.

To only set the variable if it does not exist yet, use the <comment>--if-not-exists</comment> option.
To only set the variable if the global variable does not exist yet, use the <comment>--if-not-global-exists</comment> option.

If the <comment>value</comment> argument is not filled and the command is run in interactive mode,
a value for the variable will be asked interactively to the user.

When asking for a value interactively:
 * the prompt to the user can be modified with the <comment>--description</comment> option.
 * the default value for the value can be set with the <comment>--default</comment> option.
 * the default can be set to the existing value with the <comment>--default-existing-value</comment> option, if there
   is no existing value, the value given with <comment>--default</comment> will be used.

  <info>%command.full_name% prod/authserver database_name --description="Application database name" --default=authserver</info>

To read variables, use the <info>application:variable:get</info> command.
EOF
            )
        ;
    }

    private function checkIfNotExists(InputInterface $input)
    {
        if(!$input->getOption('if-not-exists'))
            return false;
        $configurationHelper = $this->getHelper('configuration');
        /* @var $configurationHelper GlobalConfigurationHelper */
        $application = $configurationHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $overrides = $application->getOverrides();
        if(!isset($overrides->vars))
            return false;
        return isset($overrides->vars->{$input->getArgument('variable')});
    }

    private function checkIfNotGlobalExists(InputInterface $input)
    {
        if(!$input->getOption('if-not-global-exists'))
            return false;
        $configurationHelper = $this->getHelper('configuration');
        /* @var $configurationHelper GlobalConfigurationHelper */
        try {
            $configurationHelper->getConfiguration()->getGlobalVariable($input->getArgument('variable'));
            return true;
        } catch(MissingConfigurationParameterException $ex) {
            return false;
        }
    }

    private function updateDefaultValue(InputInterface $input)
    {
        if($input->getOption('default-existing-value')) {
            $configurationHelper = $this->getHelper('configuration');
            /* @var $configurationHelper GlobalConfigurationHelper */
            $application = $configurationHelper->getConfiguration()->getApplication($input->getArgument('application'));
            try {
                $input->setOption('default', $application->getVariable($input->getArgument('variable')));
            } catch(MissingConfigurationParameterException $ex) {
                // noop
            }
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if($this->checkIfNotExists($input)||$this->checkIfNotGlobalExists($input)) {
            $input->setArgument('value', '');
            return;
        }
        if(!$input->getArgument('application')||!$input->getArgument('variable'))
            return;
        if(!$input->getOption('description')) {
            $input->setOption('description', $input->getArgument('variable'));
        }
        if(!$input->getArgument('value')) {
            $questionHelper = $this->getHelper('question');
            /* @var $questionHelper QuestionHelper */
            $this->updateDefaultValue($input);
            $question = new Question($input->getOption('description'), $input->getOption('default'));
            $response = $questionHelper->ask($input, $output, $question);
            $input->setArgument('value', $response);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($this->checkIfNotExists($input)||$this->checkIfNotGlobalExists($input))
            return;
        $configurationHelper = $this->getHelper('configuration');
        /* @var $configurationHelper GlobalConfigurationHelper */
        $application = $configurationHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $application->setVariable($input->getArgument('variable'), $input->getArgument('value'));
        $configurationHelper->getConfiguration()->write();
    }

}
