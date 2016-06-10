<?php

namespace vierbergenlars\CliCentral\Exception;

class FileExistsException extends FileException
{
    protected $template = '"%s" already exists.';
}
