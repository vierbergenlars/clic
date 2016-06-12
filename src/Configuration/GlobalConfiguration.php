<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\Configuration\NoSuchRepositoryException;
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
        }
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
        $appDir = $this->getConfigOption(['config', 'applications-dir'], getenv('HOME')?getenv('HOME').'/apps': null, true);
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
        $vhostDir = $this->getConfigOption(['config', 'vhosts-dir'], getenv('HOME')?getenv('HOME').'/public_html':null, true);
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
        $sshDir = $this->getConfigOption(['config', 'ssh-dir'], getenv('HOME')?getenv('HOME').'/.ssh':null, true);
        if(!is_dir($sshDir))
            throw new NotADirectoryException($sshDir);
        return $sshDir;
    }

    /**
     * @return RepositoryConfiguration[]
     */
    public function getRepositoryConfigurations()
    {
        return array_map(function($config) {
            return new RepositoryConfiguration($config);
        }, (array)$this->getConfigOption(['repositories'], []));

    }

    /**
     * @param $repositoryName
     * @return RepositoryConfiguration|null
     *
     */
    public function getRepositoryConfiguration($repositoryName, $throws = false)
    {
        $config = $this->getConfigOption(['repositories', $repositoryName]);
        if($config)
            return new RepositoryConfiguration($config);
        if($throws)
            throw new NoSuchRepositoryException($repositoryName);
        return null;
    }

    public function setRepositoryConfiguration($repositoryName, RepositoryConfiguration $conf)
    {
        $this->setConfigOption(['repositories', $repositoryName], $conf->getConfig());
    }

    public function removeRepositoryConfiguration($repositoryName)
    {
        $this->removeConfigOption(['repositories', $repositoryName]);
    }
}
