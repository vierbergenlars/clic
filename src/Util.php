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
use vierbergenlars\CliCentral\Exception\Configuration\SshAliasExistsException;

final class Util
{
    static public function parseRepositoryUrl($repoUrl) {
        if(is_array($repoUrl))
            return $repoUrl;
        if(preg_match('/^(?:(?P<protocol>git|https?|ssh|ftps?|rsync):\\/\\/)?(?:(?P<user>[^@]+)@)?(?P<host>[a-z0-9A-Z-.]+)(?P<pathsep>:|\\/)(?P<repository>.+)$/', $repoUrl, $matches)) {
            return $matches;
        } else {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid repository URL', $repoUrl));
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

    static public function commonPrefix(\Traversable $filenames)
    {
        $commonPrefix = null;
        foreach($filenames as $filename) {
            if($commonPrefix === null) {
                $commonPrefix = $filename;
            } else {
                for ($i = 0; $i < min(strlen($commonPrefix), strlen($filename)); $i++) {
                    if ($commonPrefix[$i] !== $filename[$i]) {
                        $commonPrefix = substr($commonPrefix, 0, $i);
                        break;
                    }
                }
            }
        }
        return $commonPrefix;
    }

    static public function getExtractProcess($file)
    {
        $file = realpath($file);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext == 'zip')
            return ProcessBuilder::create([
                'unzip',
                $file,
            ])->setTimeout(null)->getProcess();
        if ($ext == 'tar' || preg_match('~\.tar\.(gz|bz2|xz|Z)$~i', $file))
            return ProcessBuilder::create([
                'tar',
                'xf',
                $file,
            ])->setTimeout(null)->getProcess();
        if ($ext == 'gz')
            return ProcessBuilder::create([
                'gunzip',
                $file,
            ])->setTimeout(null)->getProcess();
        return null;
    }

    static public function findSshAliasLines(array $sshConfigLines, $alias)
    {
        for ($i = 0; $i < count($sshConfigLines); $i++) {
            if (rtrim($sshConfigLines[$i]) == 'Host ' . $alias) {
                for($j = $i+1; $j < count($sshConfigLines); $j++) {
                    if(stripos($sshConfigLines[$j], 'Host ') === 0 || stripos($sshConfigLines[$j], 'Match ') === 0)
                        break;
                }
                return range($i, $j-1);
            }
        }
        return [];
    }

    static public function removeSshAliasLines(array &$sshConfigLines, RepositoryConfiguration $repositoryConfiguration)
    {
        $aliasLines = self::findSshAliasLines($sshConfigLines, $repositoryConfiguration->getSshAlias());
        foreach($aliasLines as $line) {
            unset($sshConfigLines[$line]);
        }
        return $aliasLines !== [];
    }

    static public function addSshAliasLines(array &$sshConfigLines, $repository, RepositoryConfiguration $repositoryConfig)
    {
        $repoParts = Util::parseRepositoryUrl($repository);
        $newConfigLines = [
            'Host '.$repositoryConfig->getSshAlias(),
            'HostName '.$repoParts['host'],
            'User '.$repoParts['user'],
            'IdentityFile '.$repositoryConfig->getIdentityFile(),
        ];
        $foundAliasLines = self::findSshAliasLines($sshConfigLines, $repositoryConfig->getSshAlias());
        if(!$foundAliasLines) {
            $sshConfigLines = array_merge($sshConfigLines, $newConfigLines);
            return true;
        } else {
            $presentAliasLines = array_map(function($i) use($sshConfigLines) {
                return $sshConfigLines[$i];
            }, $foundAliasLines);
            foreach($newConfigLines as $newConfigLine) {
                if(!in_array($newConfigLine, $presentAliasLines)) {
                    throw new SshAliasExistsException($repositoryConfig->getSshAlias(), $foundAliasLines[0]);
                }
            }
            return false;
        }
    }
}
