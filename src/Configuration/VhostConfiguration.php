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
        if($this->isDisabled())
            return $this->getLink();
        return $this->getOriginalTarget();
    }

    public function getOriginalTarget()
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

}
