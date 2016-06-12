<?php

namespace vierbergenlars\CliCentral\Configuration;

class RepositoryConfiguration
{
    private $config;

    public function __construct(\stdClass $config = null)
    {
        if(!$config)
            $config = new \stdClass();
        $this->config = $config;
    }

    /**
     * @return \stdClass
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getIdentityFile()
    {
        return $this->config->{'identity-file'};
    }

    /**
     * @param string $identityFile
     */
    public function setIdentityFile($identityFile)
    {
        $this->config->{'identity-file'} = $identityFile;
    }

    /**
     * @return string
     */
    public function getSshAlias()
    {
        return $this->config->{'ssh-alias'};
    }

    /**
     * @param string $sshAlias
     */
    public function setSshAlias($sshAlias)
    {
        $this->config->{'ssh-alias'} = $sshAlias;
    }
}
