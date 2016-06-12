<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class NoSshRepositoryException extends ConfigurationException
{
    protected $template = 'Repository "%s" is not ssh-based.';
}
