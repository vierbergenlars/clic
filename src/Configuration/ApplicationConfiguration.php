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
     * @return string|null
     */
    public function getArchiveUrl()
    {
        try {
            return $this->globalConfiguration->getConfigOption(['applications', $this->getName(), 'archive-url']);
        } catch(MissingConfigurationParameterException $ex) {
            return null;
        }
    }

    /**
     * @param string|null $archiveUrl
     */
    public function setArchiveUrl($archiveUrl)
    {
        if(!$archiveUrl)
            $this->globalConfiguration->removeConfigOption(['applications', $this->getName(), 'archive-url']);
        else
            $this->globalConfiguration->setConfigOption(['applications', $this->getName(), 'archive-url'], $archiveUrl);
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

    /**
     * @var VhostConfiguration[]
     */
    public function getVhosts()
    {
        return array_filter($this->globalConfiguration->getVhostConfigurations(), function(VhostConfiguration $vhostConfiguration) {
            return $vhostConfiguration->getApplication() === $this->getName();
        });
    }
}
