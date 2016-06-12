<?php

namespace vierbergenlars\CliCentral\Exception\File;

class UnreadableFileException extends FileException
{
    protected $template = '"%s" is not readable.';
}
