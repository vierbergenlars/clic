<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\Configuration\ApplicationExistsException;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchApplicationException;
use vierbergenlars\CliCentral\Exception\File\OutsideConfiguredRootDirectoryException;

class ApplicationConfiguration
{
    /**
     * @var GlobalConfiguration
     */
    protected $globalConfiguration;
    private $name;

    public function __construct(GlobalConfiguration $globalConfiguration, $name)
    {
        $this->globalConfiguration = $globalConfiguration;
        $this->name = $name;
    }

    public static function create(GlobalConfiguration $globalConfiguration, $name)
    {
        try {
            $globalConfiguration->getApplication($name);
            throw new ApplicationExistsException($name);
        } catch(NoSuchApplicationException $ex) {
            $globalConfiguration->setConfigOption(['applications', $name], new \stdClass);
            return new static($globalConfiguration, $name);
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $path = $this->globalConfiguration->getApplicationsDirectory().'/'.$this->getName();
        OutsideConfiguredRootDirectoryException::assert($path, 'applications-dir', $this->globalConfiguration->getApplicationsDirectory());
        return $path;
    }

    /**
     * @return string|null
     */
    public function getRepository()
    {
        try {
            return $this->globalConfiguration->getConfigOption(['applications', $this->getName(), 'repository']);
        } catch(MissingConfigurationParameterException $ex) {
            return null;
        }
    }

    /**
     * @param string|null $repository
     */
    public function setRepository($repository)
    {
        if(!$repository)
            $this->globalConfiguration->removeConfigOption(['applications', $this->getName(), 'repository']);
        else
            $this->globalConfiguration->setConfigOption(['applications', $this->getName(), 'repository'], $repository);
    }

    /**
     * @return \SplFileInfo
     */
    public function getConfigurationFileOverride()
    {
        return new \SplFileInfo($this->globalConfiguration->getConfigOption(['applications', $this->getName(), 'cliconfig-override']));
    }

    public function setConfigurationFileOverride($configurationFileOverride)
    {
        if($configurationFileOverride)
            $this->globalConfiguration->setConfigOption(['applications', $this->getName(), 'cliconfig-override'], $configurationFileOverride);
        else
            $this->globalConfiguration->removeConfigOption(['applications', $this->getName(), 'cliconfig-override']);
    }

    /**
     * @return \stdClass
     */
    public function getOverrides()
    {
        try {
            return $this->globalConfiguration->getConfigOption(['applications', $this->getName(), 'overrides']);
        } catch(MissingConfigurationParameterException $ex) {
            return new \stdClass();
        }
    }

    /**
     * @param \stdClass|null $overrides
     */
    public function setOverrides(\stdClass $overrides = null)
    {
        if(!$overrides)
            $this->globalConfiguration->removeConfigOption(['applications', $this->getName(), 'overrides']);
        else
            $this->globalConfiguration->setConfigOption(['applications', $this->getName(), 'overrides'], $overrides);
    }

    public function setOverride(array $path, $value)
    {
        array_unshift($path, 'applications', $this->getName(), 'overrides');
        $this->globalConfiguration->setConfigOption($path, $value);
    }
}
