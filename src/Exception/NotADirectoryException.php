<?php

namespace vierbergenlars\CliCentral\Exception;

class NotADirectoryException extends FileException
{
    protected $template = '"%s" is not a directory.';
}
