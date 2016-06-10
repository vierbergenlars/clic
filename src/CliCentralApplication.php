<?php

namespace vierbergenlars\CliCentral;

use vierbergenlars\CliCentral\Command;
use vierbergenlars\CliCentral\Helper\AppDirectoryHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputOption;

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
            new Command\ConfigureCommand(),
            new Command\CloneCommand(),
            new Command\ExecCommand(),
            new Command\SshKey\RemoveCommand(),
            new Command\SshKey\AddCommand(),
            new Command\SshKey\GenerateCommand(),
        ]);
    }
}
