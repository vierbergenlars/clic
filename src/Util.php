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

use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;
use vierbergenlars\CliCentral\Exception\File\FilesystemOperationFailedException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use wapmorgan\UnifiedArchive\UnifiedArchive;

final class Util
{
    static public function parseRepositoryUrl($repoUrl) {
        if(is_array($repoUrl))
            return $repoUrl;
        if(preg_match('/^(?:(?P<protocol>git|https?|ssh|ftps?|rsync):\\/\\/)?(?:(?P<user>[^@]+)@)?(?P<host>[a-z0-9A-Z-.]+)(?P<pathsep>:|\\/)(?P<repository>.+)$/', $repoUrl, $matches)) {
            return $matches;
        } else {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid repository URL'));
        }
    }

    static public function isSshRepositoryUrl($repoUrl) {
        $repoParts = self::parseRepositoryUrl($repoUrl);
        return $repoParts['pathsep'] === ':'||$repoParts['protocol'] === 'ssh';
    }

    static public function replaceRepositoryUrl($repoUrl, RepositoryConfiguration $configuration = null)
    {
        $repoParts = self::parseRepositoryUrl($repoUrl);
        if(self::isSshRepositoryUrl($repoParts)&&$configuration)
            return $configuration->getSshAlias().':'.$repoParts['repository'];
        return $repoParts[0];
    }

    static public function createPropertyPath($key)
    {
        $propertyPath = [];
        $remaining = $key;
        // first element is evaluated differently - no leading dot for properties
        $pattern = '/^([^[]+)((\\[.*)|$)/';

        while (preg_match($pattern, $remaining, $matches)) {
            if($matches[1] != '')
                $propertyPath[] = $matches[1];
            $remaining = $matches[2];
            $pattern = '/^\\[([^]]+)\\](.*)$/';
        }

        if(strlen($remaining) > 0)
            throw new \InvalidArgumentException(sprintf(
                'Could not parse property path "%s". Unexpected token "%s"',
                $key,
                $remaining[0]
            ));

        return $propertyPath;
    }

    static public function extractArchive($archiveFile, $targetDirectory)
    {
        $archive = UnifiedArchive::open($archiveFile);
        if(!$archive)
            throw new UnreadableFileException($archiveFile);
        $filenames = $archive->getFileNames();
        $commonPrefix = Util::commonPrefix($filenames);
        foreach($filenames as $filename) {
            $contents = $archive->getFileContent($filename);
            if($contents) {
                $fullFilename = $targetDirectory . '/' . substr($filename, strlen($commonPrefix));
                if(!is_dir(dirname($fullFilename)))
                    FsUtil::mkdir(dirname($fullFilename), true);
                FsUtil::file_put_contents($fullFilename, $contents);
            }
        }
    }

    static public function commonPrefix(array $filenames)
    {
        $commonPrefix = current($filenames);
        if(!$commonPrefix)
            return null;
        foreach($filenames as $filename) {
            for($i=0; $i < min(strlen($commonPrefix), strlen($filename)); $i++) {
                if($commonPrefix[$i] !== $filename[$i]) {
                    $commonPrefix = substr($commonPrefix, 0, $i);
                    break;
                }
            }
        }
        return $commonPrefix;
    }

}
