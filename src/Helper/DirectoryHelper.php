<?php

namespace vierbergenlars\CliCentral\Helper;


use vierbergenlars\CliCentral\Configuration\GlobalConfiguration;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\File\NotALinkException;


class DirectoryHelper
{
    /**
     * @var GlobalConfiguration
     */
    private $globalConfiguration;

    public function __construct(GlobalConfiguration $globalConfiguration)
    {
        $this->globalConfiguration = $globalConfiguration;
    }

    public function getDirectoryForApplication($applicationName)
    {
        $baseDir = $this->globalConfiguration->getApplicationsDirectory();
        if(!is_dir($baseDir))
            throw new NotADirectoryException($baseDir);

        $appDir = $baseDir.'/'.$applicationName;
        if(!is_dir($appDir))
            throw new NotADirectoryException($appDir);
        return $appDir;
    }

    public function getLinkForVhost($vhostName)
    {
        $baseDir = $this->globalConfiguration->getVhostsDirectory();
        if(!is_dir($baseDir))
            throw new NotADirectoryException($baseDir);
        $linkName = $baseDir.'/'.$vhostName;
        if(!is_link($linkName)&&file_exists($linkName))
            throw new NotALinkException($linkName);
        return $linkName;
    }
}
