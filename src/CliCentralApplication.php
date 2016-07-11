<?php

namespace vierbergenlars\CliCentral;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use vierbergenlars\CliCentral\Command;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class CliCentralApplication extends Application
{
    public function __construct()
    {
        parent::__construct('clic');
    }

    protected function getDefaultInputDefinition()
    {
        $def = parent::getDefaultInputDefinition();
        $default = $this->getConfigDefault();
        $def->addOption(new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set configuration file to use.', $default));
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
            new Command\Repository\RemoveCommand(),
            new Command\Repository\AddCommand(),
            new Command\Repository\GenerateCommand(),
            new Command\Repository\ShowCommand(),
            new Command\Vhost\AddCommand(),
            new Command\Vhost\RemoveCommand(),
            new Command\Vhost\ShowCommand(),
            new Command\Vhost\FixCommand(),
            new Command\Vhost\DisableCommand(),
            new Command\Vhost\EnableCommand(),
            new Command\Application\AddCommand(),
            new Command\Application\RemoveCommand(),
            new Command\Application\ShowCommand(),
            new Command\Application\CloneCommand(),
            new Command\Application\ExtractCommand(),
            new Command\Application\ExecCommand(),
            new Command\Application\VariableGetCommand(),
            new Command\Application\VariableSetCommand(),
            new Command\Application\OverrideConfigCommand(),
        ]);
    }

    /**
     * @return null|string
     */
    private function getConfigDefault()
    {
        if(getenv('CLIC_CONFIG'))
            return getenv('CLIC_CONFIG');
        if(getenv('HOME'))
            return getenv('HOME').'/.clic-settings.json';
        return null;
    }
}
