<?php

namespace vierbergenlars\CliCentral\Exception\File;

class FileExistsException extends FileException
{
    protected $template = '"%s" already exists.';
}
