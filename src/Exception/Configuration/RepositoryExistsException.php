<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class RepositoryExistsException extends ConfigurationException
{
    protected $template = 'Repository "%s" is already configured.';
}
