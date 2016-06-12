<?php

namespace vierbergenlars\CliCentral\Exception\File;

class NotEmptyException extends FileException
{
    protected $template = '"%s" is not empty.';
}
