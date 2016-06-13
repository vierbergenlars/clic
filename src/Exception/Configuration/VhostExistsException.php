<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class VhostExistsException extends ConfigurationException
{
    protected $template = 'Vhost "%s" is already configured.';
}
