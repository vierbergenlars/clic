<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

abstract class ConfigurationException extends \RuntimeException
{
    protected $template = '%s';
    private $repository;
    public function __construct($repository, \Exception $previous = null)
    {
        $this->repository = $repository;
        parent::__construct(sprintf($this->template, $repository), 0, $previous);
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
