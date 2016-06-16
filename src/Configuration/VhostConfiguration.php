<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchVhostException;
use vierbergenlars\CliCentral\Exception\Configuration\VhostExistsException;
use vierbergenlars\CliCentral\Exception\File\OutsideConfiguredRootDirectoryException;

class VhostConfiguration
{
    /**
     * @var GlobalConfiguration
     */
    private $globalConfiguration;

    /**
     * @var string
     */
    private $name;

    public function __construct(GlobalConfiguration $globalConfiguration, $name)
    {
        $this->globalConfiguration = $globalConfiguration;
        $this->name = $name;
    }

    public static function create(GlobalConfiguration $globalConfiguration, $name, ApplicationConfiguration $applicationConfiguration)
    {
        try {
            $globalConfiguration->getVhostConfiguration($name);
            throw new VhostExistsException($name);
        } catch(NoSuchVhostException $ex) {
            $config = new self($globalConfiguration, $name);
            $config->setApplication($applicationConfiguration->getName());
            $config->setDisabled(false);
            return $config;
        }
    }


    public function getApplication()
    {
        return $this->globalConfiguration->getConfigOption(['vhosts', $this->getName(), 'application']);
    }

    public function setApplication($application)
    {
        $this->globalConfiguration->setConfigOption(['vhosts', $this->getName(), 'application'], $application);
    }

    public function getTarget()
    {
        if($this->isDisabled())
            return $this->getLink();
        return $this->getOriginalTarget();
    }

    public function getOriginalTarget()
    {
        return new \SplFileInfo($this->globalConfiguration->getApplication($this->getApplication())->getWebDirectory());
    }

    public function getLink()
    {
        $link = new \SplFileInfo($this->globalConfiguration->getVhostsDirectory().'/'.$this->getName());
        OutsideConfiguredRootDirectoryException::assert($link->getPathname(), 'vhosts-dir', $this->globalConfiguration->getVhostsDirectory());
        return $link;
    }

    public function isDisabled()
    {
        try {
            return $this->globalConfiguration->getConfigOption(['vhosts', $this->getName(), 'disabled']);
        } catch(MissingConfigurationParameterException $ex) {
            return false;
        }
    }

    public function setDisabled($disabled)
    {
        if($disabled)
            $this->globalConfiguration->setConfigOption(['vhosts', $this->getName(), 'disabled'], true);
        else
            $this->globalConfiguration->removeConfigOption(['vhosts', $this->getName(), 'disabled']);
    }

    public function getStatusMessage()
    {
        $errorStatus = $this->getErrorStatus();
        if($errorStatus)
            return '<error>'.$errorStatus.'</error>';
        if ($this->isDisabled())
            return '<comment>Disabled</comment>';
        return '<info>OK</info>';
    }

    public function getErrorStatus()
    {
        $vhostLink = $this->getLink();
        $vhostTarget = $this->getTarget();
        if (!$vhostLink->isLink() && !$vhostLink->isDir() && !$vhostLink->isFile())
            return 'Does not exist';
        if (!$vhostLink->isLink())
            return sprintf('Vhost is a %s, not a link', $vhostLink->getType());
        if ($this->isDisabled() && $vhostLink->getLinkTarget() !== $vhostLink->getPathname())
            return 'Not disabled correctly';
        if (!$this->isDisabled() && $vhostLink->getLinkTarget() !== $vhostTarget->getPathname())
            return 'Link target does not match expected target';
        return null;
    }

    public function getName()
    {
        return $this->name;
    }

}
