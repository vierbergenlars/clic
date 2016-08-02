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

final class PathUtil
{
    static public function canonicalizePath($path)
    {
        if(DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace('\\', '/', $path);
        }
        $parts = explode('/', $path);
        $i=0;
        while(isset($parts[$i])) {
            if($parts[$i] === '..') {
                $parts[$i] = '';
                if(isset($parts[$i-1]))
                    $parts[$i-1] = '';
            }
            $i++;
        }
        return implode(DIRECTORY_SEPARATOR, array_filter($parts, function($v) {
            return $v !== '' && $v!=='.';
        }));
    }

    static public function isSubDirectory($directory, $baseDirectory)
    {
        $directory = explode(DIRECTORY_SEPARATOR, self::canonicalizePath($directory));
        $baseDirectory = explode(DIRECTORY_SEPARATOR, self::canonicalizePath($baseDirectory));

        foreach($baseDirectory as $i => $item)
        {
            if(!isset($directory[$i]))
                return false;
            if($directory[$i] !== $item)
                return false;
        }
        return true;
    }

    static public function commonPrefix(\Traversable $filenames)
    {
        $commonParts = null;
        foreach($filenames as $filename) {
            $explodedFilename = preg_split('=/|\\\\=', $filename);
            if($commonParts === null) {
                $commonParts = $explodedFilename;
            } else {
                for ($i = 0; $i < min(count($commonParts), count($explodedFilename)); $i++) {
                    if ($commonParts[$i] !== $explodedFilename[$i]) {
                        $commonParts = array_slice($commonParts, 0, $i);

                        break;
                    }
                }
            }
        }
        return implode(DIRECTORY_SEPARATOR, $commonParts);
    }
}
