<?php

namespace vierbergenlars\CliCentral\Configuration;

use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\NoScriptException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use Symfony\Component\Process\Process;

class Application extends ApplicationConfiguration
{
    /**
     * @var string
     */
    private $appName;

    /**
     * @var LocalApplicationConfiguration
     */
    private $configuration;

    /**
     * Application constructor.
     * @param string $appName
     * @param \stdClass $config
     */
    public function __construct($appName, \stdClass $config)
    {
        if(!isset($config->path))
            throw new \LogicException('Cannot create application without path');
        parent::__construct($config);
        $this->appName = $appName;
    }

    public static function fromConfig($appName, ApplicationConfiguration $config)
    {
        return new self($appName, $config->getConfig());
    }

    public function getName()
    {
        return $this->appName;
    }

    protected function getConfigurationFile()
    {
        $file = new \SplFileInfo($this->getPath().'/.cliconfig.json');
        if(!$file->isFile())
            throw new NotAFileException($file);
        if(!$file->isReadable())
            throw new UnreadableFileException($file);
        return $file;
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
            $env = [];
            foreach($_SERVER as $key=>$value) {
                $ev = getenv($key);
                if($ev)
                    $env[$key] = $ev;
            }
            return new Process($this->getConfiguration()->getScriptCommand($scriptName), $this->getPath(), array_merge($env, [
                'CLIC_NONINTERACTIVE' => '1',
            ]), null, null);
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

}
