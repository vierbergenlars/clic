<?php

namespace vierbergenlars\CliCentral\Exception\File;

class NotALinkException extends FileException
{
    protected $template = '"%s" is not a symlink.';
}
