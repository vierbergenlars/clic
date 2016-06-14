<?php

namespace vierbergenlars\CliCentral\Configuration;

use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\NoScriptException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use Symfony\Component\Process\Process;

class Application
{
    /**
     * @var string
     */
    private $appName;

    /**
     * Base directory to the environment
     * @var \SplFileInfo
     */
    private $baseDir;

    /**
     * @var ApplicationConfiguration
     */
    private $configuration;

    /**
     * @var \stdClass
     */
    private $overrides;

    /**
     * Application constructor.
     * @param string $appName
     * @param \SplFileInfo $baseDir
     * @param \stdClass|null $overrides
     */
    public function __construct($appName, \SplFileInfo $baseDir, \stdClass $overrides = null)
    {
        $this->appName = $appName;
        $this->baseDir = $baseDir;
        $this->overrides = $overrides?:new \stdClass();
    }

    public function getName()
    {
        return $this->appName;
    }

    protected function getConfigurationFile()
    {
        $file = new \SplFileInfo($this->baseDir.'/.cliconfig.json');
        if(!$file->isFile())
            throw new NotAFileException($file);
        if(!$file->isReadable())
            throw new UnreadableFileException($file);
        return $file;
    }

    protected function getConfiguration()
    {
        if(!$this->configuration)
            $this->configuration = new ApplicationConfiguration($this->getConfigurationFile(), $this->overrides);
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
            ->setWorkingDirectory($this->baseDir->getPathname())
            ->addEnvironmentVariables($env)
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
            return new Process($this->getConfiguration()->getScriptCommand($scriptName), $this->baseDir->getPathname(), array_merge($env, [
                'CLIC_NONINTERACTIVE' => '1',
            ]), null, null);
        } catch(NoScriptException $ex) {
            throw new NoScriptException($this->getName().':'.$scriptName);
        }
    }

    public function getWebDirectory()
    {
        $webDir = new \SplFileInfo($this->baseDir->getPathname().'/'.$this->getConfiguration()->getWebDir());
        if(!$webDir->isDir())
            throw new NotADirectoryException($webDir);
        return $webDir;
    }

}
