<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchApplicationException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchVhostException;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;

class GlobalConfiguration extends Configuration
{
    public function __construct(\SplFileInfo $configFile)
    {
        try {
            parent::__construct($configFile);
        } catch(NotAFileException $e) {
            $this->config = new \stdClass();
            $this->setConfigOption(['config'], new \stdClass());
        }
    }

    public function getGlobalVariables()
    {
        return (array)$this->getConfigOption(['global-vars']);
    }

    public function getGlobalVariable($varName)
    {
        return $this->getConfigOption(['global-vars', $varName]);
    }


    protected function getSchema()
    {
        return json_decode(file_get_contents(__DIR__.'/../../res/clic-settings-schema.json'));
    }

    /**
     * @return string
     * @throws MissingConfigurationParameterException
     * @throws NotADirectoryException
     */
    public function getApplicationsDirectory()
    {
        try {
            $appDir = $this->getConfigOption(['config', 'applications-dir']);
        } catch(MissingConfigurationParameterException $ex) {
            $appDir =  getenv('HOME') ? getenv('HOME') . '/apps' : null;
        }
        if(!is_dir($appDir))
            throw new NotADirectoryException($appDir);
        return $appDir;
    }

    public function setApplicationsDirectory($applicationsDir)
    {
        $this->setConfigOption(['config', 'applications-dir'], $applicationsDir);
    }

    /**
     * @return string
     * @throws MissingConfigurationParameterException
     * @throws NotADirectoryException
     */
    public function getVhostsDirectory()
    {
        try {
            $vhostDir = $this->getConfigOption(['config', 'vhosts-dir']);
        } catch(MissingConfigurationParameterException $ex) {
            $vhostDir = getenv('HOME') ? getenv('HOME') . '/public_html' : null;
        }
        if(!is_dir($vhostDir))
            throw new NotADirectoryException($vhostDir);
        return $vhostDir;
    }

    public function setVhostsDirectory($vhostsDir)
    {
        $this->setConfigOption(['config', 'vhosts-dir'], $vhostsDir);
    }

    /**
     * @return string
     * @throws NotADirectoryException
     * @throws MissingConfigurationParameterException
     */
    public function getSshDirectory()
    {
        try {
            $sshDir = $this->getConfigOption(['config', 'ssh-dir']);
        } catch(MissingConfigurationParameterException $ex) {
            $sshDir =  getenv('HOME') ? getenv('HOME') . '/.ssh' : null;
        }
        if(!is_dir($sshDir))
            throw new NotADirectoryException($sshDir);
        return $sshDir;
    }

    /**
     * @param string $sshDir
     */
    public function setSshDirectory($sshDir)
    {
        $this->setConfigOption(['config', 'ssh-dir'], $sshDir);
    }

    /**
     * @return RepositoryConfiguration[]
     */
    public function getRepositoryConfigurations()
    {
        try {
            return array_map(function ($config) {
                return new RepositoryConfiguration($config);
            }, (array)$this->getConfigOption(['repositories']));
        } catch(MissingConfigurationParameterException $ex) {
            return [];
        }
    }

    /**
     * @param $repositoryName
     * @return RepositoryConfiguration
     */
    public function getRepositoryConfiguration($repositoryName)
    {
        try {
            $config = $this->getConfigOption(['repositories', $repositoryName]);
            return new RepositoryConfiguration($config);
        } catch(MissingConfigurationParameterException $ex) {
            throw new NoSuchRepositoryException($repositoryName, $ex);
        }
    }

    public function setRepositoryConfiguration($repositoryName, RepositoryConfiguration $conf)
    {
        $this->setConfigOption(['repositories', $repositoryName], $conf->getConfig());
    }

    public function removeRepositoryConfiguration($repositoryName)
    {
        $this->removeConfigOption(['repositories', $repositoryName]);
    }

    public function getVhostConfigurations()
    {
        try {
            $vhosts = array_keys((array)$this->getConfigOption(['vhosts']));
            return array_map(function ($name) {
                return new VhostConfiguration($this, $name);
            }, $vhosts);
        } catch(MissingConfigurationParameterException $ex) {
            return [];
        }
    }

    public function getVhostConfiguration($vhostName)
    {
        try {
            $this->getConfigOption(['vhosts', $vhostName]);
            return new VhostConfiguration($this, $vhostName);
        } catch(MissingConfigurationParameterException $ex) {
            throw new NoSuchVhostException($vhostName, $ex);
        }
    }

    public function removeVhostConfiguration($vhostName)
    {
        $this->removeConfigOption(['vhosts', $vhostName]);
    }

    public function getApplications()
    {
        try {
            $applications = array_keys((array)$this->getConfigOption(['applications']));
            return array_map(function ($appName) {
                return new Application($this, $appName);
            }, $applications);
        } catch(MissingConfigurationParameterException $ex) {
            return [];
        }
    }

    public function getApplication($appName)
    {
        try {
            $this->getConfigOption(['applications', $appName]);
            return new Application($this, $appName);
        } catch(MissingConfigurationParameterException $ex) {
            throw new NoSuchApplicationException($appName, $ex);
        }
    }

    public function removeApplication($appName)
    {
        $this->removeConfigOption(['applications', $appName]);
    }
}
