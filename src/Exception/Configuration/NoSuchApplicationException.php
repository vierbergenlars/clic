<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class NoSuchApplicationException extends ConfigurationException
{
    protected $template = 'Application "%s" is not configured.';
}
