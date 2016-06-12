<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class NoSuchRepositoryException extends ConfigurationException
{
    protected $template = 'Repository "%s" is not configured.';
}
