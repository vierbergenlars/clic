<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\NotAFileException;

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
     * @return string|null
     */
    public function getApplicationsDirectory()
    {
        return $this->getConfigOption(['config', 'applications-dir'], getenv('HOME')?getenv('HOME').'/apps': null);
    }

    public function setApplicationsDirectory($applicationsDir)
    {
        $this->setConfigOption(['config', 'applications-dir'], $applicationsDir);
    }

    /**
     * @return string|null
     */
    public function getVhostsDirectory()
    {
        return $this->getConfigOption(['config', 'vhosts-dir'], getenv('HOME')?getenv('HOME').'/public_html':null);
    }

    public function setVhostsDirectory($vhostsDir)
    {
        $this->setConfigOption(['config', 'vhosts-dir'], $vhostsDir);
    }

    /**
     * @return string|null
     */
    public function getSshDirectory()
    {
        return $this->getConfigOption(['config', 'ssh-dir'], getenv('HOME')?getenv('HOME').'/.ssh':null);
    }

    /**
     * @param $repositoryName
     * @return RepositoryConfiguration|null
     */
    public function getRepositoryConfiguration($repositoryName)
    {
        $config = $this->getConfigOption(['repositories', $repositoryName]);
        if(!$config)
            return null;
        return new RepositoryConfiguration($config);
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
