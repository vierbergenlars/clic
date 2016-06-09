<?php

namespace vierbergenlars\CliCentral\ApplicationEnvironment;

use vierbergenlars\CliCentral\Exception\NotADirectoryException;
use Symfony\Component\Finder\Finder;

class Environment
{
    /**
     * Base directory to the environment
     * @var \SplFileInfo
     */
    private $baseDir;

    /**
     * Environment constructor.
     * @param string|\SplFileInfo $baseDir Directory to the environment
     */
    public function __construct($baseDir)
    {
        if(!($baseDir instanceof \SplFileInfo))
            $baseDir = new \SplFileInfo($baseDir);

        $this->baseDir = $baseDir;
    }

    public function getName()
    {
        return $this->baseDir->getFilename();
    }

    /**
     * Get the application directories
     * @return Finder|\Symfony\Component\Finder\SplFileInfo[]
     */
    public function getApplicationDirectories()
    {
        return Finder::create()
            ->in($this->baseDir->getPathname())
            ->directories()
            ->depth(0)
            ;
    }

    /**
     * @return Application[]
     */
    public function getApplications()
    {
        $applications = [];
        foreach($this->getApplicationDirectories() as $applicationDirectory) {
            $applications[] = new Application($this, $applicationDirectory);
        }
        return $applications;
    }

    public function getApplicationDirectory($appName)
    {
        return new \SplFileInfo($this->baseDir.'/'.$appName);
    }

    public function getApplication($appName)
    {
        $appDir = $this->getApplicationDirectory($appName);
        if(!$appDir->isDir())
            throw new NotADirectoryException($appDir);
        return new Application($this, $appDir);
    }
}
