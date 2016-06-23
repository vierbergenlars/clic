<?php

namespace vierbergenlars\CliCentral\Configuration;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
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
