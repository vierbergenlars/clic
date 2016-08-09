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

namespace vierbergenlars\CliCentral\Configuration;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\NoScriptException;

class Application extends ApplicationConfiguration
{
    /**
     * @var LocalApplicationConfiguration
     */
    private $configuration;

    public static function fromConfig(GlobalConfiguration $globalConfiguration, ApplicationConfiguration $config)
    {
        return new self($globalConfiguration, $config->getName());
    }

    protected function getConfigurationFile()
    {
        try {
            return $this->getConfigurationFileOverride();
        } catch(MissingConfigurationParameterException $ex) {
            return new \SplFileInfo($this->getPath() . '/.cliconfig.json');
        }
    }

    protected function getConfiguration()
    {
        if(!$this->configuration)
            $this->configuration = new LocalApplicationConfiguration($this->getConfigurationFile(), $this->getOverrides());
        return $this->configuration;
    }

    public function getProcessBuilder(array $arguments = [])
    {
        $env = [];
        foreach($_SERVER as $key=>$value) {
            $ev = getenv($key);
            if($ev)
                $env[$key] = $ev;
        }

        return ProcessBuilder::create($arguments)
            ->setWorkingDirectory($this->getPath())
            ->addEnvironmentVariables($env)
            ->setTimeout(null)
        ;
    }

    public function getScriptProcess($scriptName)
    {
        try {
            $phpFinder = new PhpExecutableFinder();
            $env = [
            ];
            foreach($_SERVER as $key=>$value) {
                $ev = getenv($key);
                if($ev)
                    $env[$key] = $ev;
            }
            $env['CLIC_APPNAME'] = $this->getName();
            $env['CLIC'] = implode(' ', [
                $phpFinder->find(),
                $_SERVER['argv'][0],
            ]);
            $env['CLIC_CONFIG'] = realpath($this->globalConfiguration->getConfigFile());
            $env['CLIC_APPCONFIG_DIR'] = realpath($this->getConfigurationFile()->getPath());
            return new Process($this->getConfiguration()->getScriptCommand($scriptName), $this->getPath(), $env, null, null);
        } catch(NoScriptException $ex) {
            throw new NoScriptException($this->getName().':'.$scriptName);
        }
    }

    public function getWebDirectory()
    {
        $webDir = new \SplFileInfo($this->getPath().'/'.$this->getConfiguration()->getWebDir());
        if(!$webDir->isDir())
            throw new NotADirectoryException($webDir);
        return $webDir;
    }

    public function getVariable($variableName)
    {
        try {
            return $this->getConfiguration()->getVariable($variableName);
        } catch(MissingConfigurationParameterException $ex) {
            try {
                return $this->globalConfiguration->getGlobalVariable($variableName);
            } catch(MissingConfigurationParameterException $ex2) {
                throw $ex;
            }
        }
    }

    public function setVariable($variableName, $value)
    {
        $this->setOverride(['vars', $variableName], $value);
    }

    public function getVariables()
    {
        return array_merge($this->globalConfiguration->getGlobalVariables(), $this->getConfiguration()->getVariables());
    }
}
