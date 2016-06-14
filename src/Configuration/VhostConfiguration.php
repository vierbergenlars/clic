<?php

namespace vierbergenlars\CliCentral\Configuration;

class VhostConfiguration
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

    public function getApplication()
    {
        return $this->config->application;
    }

    public function setApplication($application)
    {
        $this->config->application = $application;
    }

    public function getTarget()
    {
        return new \SplFileInfo($this->config->target);
    }

    public function setTarget($target)
    {
        if($target instanceof \SplFileInfo)
            $target = $target->getPathname();
        $this->config->target = $target;
    }

    public function getLink()
    {
        return new \SplFileInfo($this->config->link);
    }

    public function setLink($link)
    {
        if($link instanceof \SplFileInfo)
            $link = $link->getPathname();
        $this->config->link = $link;
    }

    public function isDisabled()
    {
        if(!isset($this->config->disabled))
            return false;
        return $this->config->disabled?true:false;
    }

    public function setDisabled($disabled)
    {
        $this->config->disabled = $disabled;
    }

}
