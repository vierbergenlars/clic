<?php
/**
 * clic, user-friendly PHP application deployment and set-up
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Lars Vierbergen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace vierbergenlars\CliCentral;

use vierbergenlars\CliCentral\Exception\File\FilesystemOperationFailedException;
use vierbergenlars\CliCentral\Exception\File\RenameFailedException;
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

    public static function rename($source, $target)
    {
        if(!@rename($source, $target))
            throw new RenameFailedException($source, $target);
    }

    public static function file_get_contents($filename)
    {
        $contents = @file_get_contents($filename);
        if($contents === false)
            throw new FilesystemOperationFailedException($filename, 'file_get_contents');
        return $contents;
    }
}
