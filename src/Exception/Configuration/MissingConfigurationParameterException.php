<?php

namespace vierbergenlars\CliCentral\Exception\Configuration;

class MissingConfigurationParameterException extends ConfigurationException
{
    protected $template = 'The "%s" parameter does not have a value.';
}
