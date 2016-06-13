<?php

namespace vierbergenlars\CliCentral\Exception\File;

class UndeletableFileException extends FileException
{
    protected $template = '"%s" could not be removed : %s';

    public function __construct($filename, \Exception $previous = null)
    {
        parent::__construct($filename, $previous, [
            error_get_last()['message']
        ]);
    }
}
