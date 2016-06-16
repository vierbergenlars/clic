<?php

namespace vierbergenlars\CliCentral\Exception\File;

class NotEmptyException extends FileException
{
    protected $template = '"%s" is not empty.';

    public static function assert($directory)
    {
        if(count(scandir($directory)) > 2)
            throw new self($directory);
    }
}
