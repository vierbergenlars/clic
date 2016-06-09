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

    public function getIdentityFile()
    {
        return $this->config->{'identity-file'};
    }

    public function setIdentityFile($identityFile)
    {
        $this->config->{'identity-file'} = $identityFile;
    }

    public function getSshAlias()
    {
        return $this->config->{'ssh-alias'};
    }

    public function setSshAlias($sshAlias)
    {
        $this->config->{'ssh-alias'} = $sshAlias;
    }
}
