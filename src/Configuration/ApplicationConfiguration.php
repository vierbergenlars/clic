<?php

namespace vierbergenlars\CliCentral\Configuration;

class ApplicationConfiguration
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
    public function getPath()
    {
        return $this->config->path;
    }

    /**
     * @param \SplFileInfo|string $path
     */
    public function setPath($path)
    {
        if($path instanceof \SplFileInfo)
            $path = $path->getPathname();
        $this->config->path = $path;
    }

    /**
     * @return string|null
     */
    public function getRepository()
    {
        return @$this->config->repository?:null;
    }

    /**
     * @param string|null $repository
     */
    public function setRepository($repository)
    {
        if(!$repository)
            unset($this->config->repository);
        else
            $this->config->repository = $repository;
    }

    /**
     * @return \stdClass
     */
    public function getOverrides()
    {
        return @$this->config->overrides?:new \stdClass();
    }

    /**
     * @param \stdClass|null $overrides
     */
    public function setOverrides(\stdClass $overrides = null)
    {
        if(!$overrides)
            unset($this->config->overrides);
        else
            $this->config->overrides = $overrides;
    }
}
