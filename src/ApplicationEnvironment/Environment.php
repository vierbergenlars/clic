<?php

namespace vierbergenlars\CliCentral\ApplicationEnvironment;

use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
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

    /**
     * @param string $appName
     * @return \SplFileInfo
     * @throws NotADirectoryException
     */
    public function getApplicationDirectory($appName)
    {
        $fileInfo = new \SplFileInfo($this->baseDir.'/'.$appName);
        if(!$fileInfo->isDir())
            throw new NotADirectoryException($fileInfo);
        return $fileInfo;
    }

    /**
     * @param string $appName
     * @return Application
     * @throws NotADirectoryException
     */
    public function getApplication($appName)
    {
        return new Application($this, $this->getApplicationDirectory($appName));
    }
}
