<?php

namespace vierbergenlars\CliCentral\Exception;

class UnreadableFileException extends FileException
{
    protected $template = '"%s" is not readable.';
}
