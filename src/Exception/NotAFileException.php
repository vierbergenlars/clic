<?php

namespace vierbergenlars\CliCentral\Exception;

class NotAFileException extends FileException
{
    protected $template = '"%s" is not a file.';
}
