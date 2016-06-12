<?php

namespace vierbergenlars\CliCentral\Exception\File;

class NotAFileException extends FileException
{
    protected $template = '"%s" is not a file.';
}
