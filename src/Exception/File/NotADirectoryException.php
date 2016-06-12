<?php

namespace vierbergenlars\CliCentral\Exception\File;

class NotADirectoryException extends FileException
{
    protected $template = '"%s" is not a directory.';
}
