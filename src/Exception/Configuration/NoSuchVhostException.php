<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class NoSuchVhostException extends ConfigurationException
{
    protected $template = 'Vhost "%s" is not configured.';
}
