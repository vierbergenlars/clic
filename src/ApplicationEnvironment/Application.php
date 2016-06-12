<?php

namespace vierbergenlars\CliCentral\ApplicationEnvironment;

use vierbergenlars\CliCentral\Exception\NoScriptException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use Symfony\Component\Process\Process;

class Application
{
    /**
     * Base directory to the environment
     * @var \SplFileInfo
     */
    private $baseDir;

    /**
     * Environment of the application
     * @var Environment
     */
    private $environment;

    /**
     * @var ApplicationConfiguration
     */
    private $configuration;

    /**
     * Application constructor.
     * @param Environment $env
     * @param \SplFileInfo $baseDir
     */
    public function __construct(Environment $env, \SplFileInfo $baseDir)
    {
        $this->environment = $env;
        $this->baseDir = $baseDir;
    }

    public function getName()
    {
        return $this->baseDir->getFilename();
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
            $this->configuration = new ApplicationConfiguration($this->getConfigurationFile());
        return $this->configuration;
    }

    public function getScriptProcess($scriptName)
    {
        try {
            return new Process($this->getConfiguration()->getScriptCommand($scriptName), $this->baseDir->getPathname(), [
                'CLIC_ENV' => $this->environment->getName(),
                'CLIC_NONINTERACTIVE' => '1',
                'PATH' => getenv('PATH'),
            ], null, null);
        } catch(NoScriptException $ex) {
            throw new NoScriptException($this->getName().':'.$scriptName);
        }
    }

}
