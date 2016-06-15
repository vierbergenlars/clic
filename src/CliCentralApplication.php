<?php

namespace vierbergenlars\CliCentral;

use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\Command;
use vierbergenlars\CliCentral\Helper\DirectoryHelper;
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
        $def = parent::getDefaultInputDefinition();
        $def->addOption(new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set configuration file to use.', getenv('HOME')?(getenv('HOME').'/.clic-settings.json'):null));
        return $def;
    }

    protected function getDefaultHelperSet()
    {
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
            new SymfonyQuestionHelper(),
            new GlobalConfigurationHelper(),
        ]);
    }

    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            new Command\Config\InitCommand(),
            new Command\Config\SetCommand(),
            new Command\Config\UnsetCommand(),
            new Command\Config\GetCommand(),
            new Command\CloneCommand(),
            new Command\ExecCommand(),
            new Command\SshKey\RemoveCommand(),
            new Command\SshKey\AddCommand(),
            new Command\SshKey\GenerateCommand(),
            new Command\SshKey\ShowCommand(),
            new Command\Vhost\AddCommand(),
            new Command\Vhost\RemoveCommand(),
            new Command\Vhost\ShowCommand(),
            new Command\Vhost\FixCommand(),
            new Command\Vhost\DisableCommand(),
            new Command\Vhost\EnableCommand(),
            new Command\Application\AddCommand(),
            new Command\Application\RemoveCommand(),
            new Command\Application\ShowCommand(),
        ]);
    }
}
