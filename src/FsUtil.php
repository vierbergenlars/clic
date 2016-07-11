<?php

namespace vierbergenlars\CliCentral;

use vierbergenlars\CliCentral\Exception\File\FilesystemOperationFailedException;
use vierbergenlars\CliCentral\Exception\File\SymlinkFailedException;

final class FsUtil
{
    public static function mkdir($dirname, $recursive = false)
    {
        if(!@mkdir($dirname, 0777, $recursive))
            throw new FilesystemOperationFailedException($dirname, 'mkdir');
    }

    public static function symlink($target, $link)
    {
        if(!@symlink($target, $link))
            throw new SymlinkFailedException($link, $target);
    }

    public static function rmdir($dirname)
    {
        if(!@rmdir($dirname))
            throw new FilesystemOperationFailedException($dirname, 'rmdir');
    }

    public static function unlink($filename)
    {
        if(!@unlink($filename))
            throw new FilesystemOperationFailedException($filename, 'unlink');
    }

    public static function touch($filename)
    {
        if(!@touch($filename))
            throw new FilesystemOperationFailedException($filename, 'touch');
    }

    public static function file_put_contents($filename, $contents)
    {
        if(!@file_put_contents($filename, $contents))
            throw new FilesystemOperationFailedException($filename, 'file_put_contents');
    }
}
