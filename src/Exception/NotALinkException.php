<?php

namespace vierbergenlars\CliCentral\Exception;

class NotALinkException extends FileException
{
    protected $template = '"%s" is not a symlink.';
}
