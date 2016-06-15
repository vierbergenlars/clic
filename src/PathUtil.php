<?php

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
}
