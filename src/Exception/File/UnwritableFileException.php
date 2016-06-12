<?php

namespace vierbergenlars\CliCentral\Exception\File;

class UnwritableFileException extends FileException
{
    protected $template = '"%s" is not writable.';
}
