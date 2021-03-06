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

namespace vierbergenlars\CliCentral;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use vierbergenlars\CliCentral\Command;
use vierbergenlars\CliCentral\Exception\File\FilesystemOperationFailedException;
use vierbergenlars\CliCentral\Helper\ExtractHelper;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use vierbergenlars\CliCentral\Helper\ProcessHelper;

class CliCentralApplication extends Application
{
    const PACKAGE_NAME = 'vierbergenlars/clic';

    public function __construct()
    {
        parent::__construct('clic', $this->getBuildVersion());
    }

    private function getBuildVersion()
    {
        $version = '@git-version@';
        if($version !== ('@'.'git-version'.'@'))
            return $version;
        try {
            // try to locate composers' installed.json file. (in case of global install)
            $packages = json_decode(FsUtil::file_get_contents(__DIR__.'/../../../composer/installed.json'), true);
            foreach($packages as $package) {
                if($package['name'] === self::PACKAGE_NAME)
                    return $package['version'];
            }
        } catch(FilesystemOperationFailedException $ex) {
            // no op
        }

        return 'UNKNOWN';
    }

    private function getBuildCommit()
    {
        $commit = '@git-commit@';
        if($commit !== ('@'.'git-commit'.'@'))
            return $commit;

        try {
            // try to locate composers' installed.json file. (in case of global install)
            $packages = json_decode(FsUtil::file_get_contents(__DIR__.'/../../../composer/installed.json'), true);
            foreach($packages as $package) {
                if($package['name'] === self::PACKAGE_NAME)
                    return $package['source']['reference'];
            }
        } catch(FilesystemOperationFailedException $ex) {
            // no op
        }

        return 'UNKNOWN';
    }

    public function getLongVersion()
    {
        $commit = $this->getBuildCommit();
        if($commit === 'UNKNOWN')
            return sprintf('<info>%s</info> (repo)', $this->getName());
        return sprintf('<info>%s</info> version <comment>%s</comment>; commit <comment>%s</comment>', $this->getName(), $this->getVersion(), $this->getBuildCommit());
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
            new ExtractHelper(),
        ]);
    }

    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            new Command\SelfUpdateCommand(),
            new Command\Config\InitCommand(),
            new Command\Config\SetCommand(),
            new Command\Config\UnsetCommand(),
            new Command\Config\GetCommand(),
            new Command\Config\RollbackCommand(),
            new Command\Repository\RemoveCommand(),
            new Command\Repository\AddCommand(),
            new Command\Repository\GenerateCommand(),
            new Command\Repository\ShowCommand(),
            new Command\Repository\ListCommand(),
            new Command\Vhost\AddCommand(),
            new Command\Vhost\RemoveCommand(),
            new Command\Vhost\ShowCommand(),
            new Command\Vhost\ListCommand(),
            new Command\Vhost\FixCommand(),
            new Command\Vhost\DisableCommand(),
            new Command\Vhost\EnableCommand(),
            new Command\Application\AddCommand(),
            new Command\Application\RemoveCommand(),
            new Command\Application\ShowCommand(),
            new Command\Application\ListCommand(),
            new Command\Application\CloneCommand(),
            new Command\Application\ExtractCommand(),
            new Command\Application\ExecCommand(),
            new Command\Application\VariableGetCommand(),
            new Command\Application\VariableSetCommand(),
            new Command\Application\OverrideCommand(),
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
