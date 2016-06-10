<?php

namespace vierbergenlars\CliCentral\Exception;

class UnwritableFileException extends FileException
{
    protected $template = '"%s" is not writable.';
}
