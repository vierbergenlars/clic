<?php

namespace vierbergenlars\CliCentral;

use vierbergenlars\CliCentral\Command\CloneCommand;
use vierbergenlars\CliCentral\Command\DeployCommand;
use vierbergenlars\CliCentral\Command\DeployShCommand;
use vierbergenlars\CliCentral\Command\DisableMaintenanceCommand;
use vierbergenlars\CliCentral\Command\EnableMaintenanceCommand;
use vierbergenlars\CliCentral\Command\ExecCommand;
use vierbergenlars\CliCentral\Command\ConfigureCommand;
use vierbergenlars\CliCentral\Configuration\GlobalConfiguration;
use vierbergenlars\CliCentral\Helper\AppDirectoryHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CliCentralApplication extends Application
{
    public function __construct()
    {
        parent::__construct('clic');
    }

    protected function getDefaultInputDefinition()
    {
        $def =parent::getDefaultInputDefinition();
        $def->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'Set environment to run in.', 'staging'));
        $def->addOption(new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'Set configuration file to use.', getenv('HOME')?(getenv('HOME').'/.clic-settings.json'):null));
        return $def;
    }

    protected function getDefaultHelperSet()
    {
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
            new SymfonyQuestionHelper(),
            new AppDirectoryHelper(),
            new GlobalConfigurationHelper(),
        ]);
    }

    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            new ConfigureCommand(),
            new CloneCommand(),
            new ExecCommand(),
        ]);
    }
}
