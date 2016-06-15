<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class ApplicationExistsException extends ConfigurationException
{
    protected $template = 'Application "%s" is already configured.';
}
