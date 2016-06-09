<?php

namespace vierbergenlars\CliCentral\Exception;

class NotEmptyException extends FileException
{
    protected $template = '"%s" is not empty.';
}
