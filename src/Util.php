<?php

namespace vierbergenlars\CliCentral;

use vierbergenlars\CliCentral\Configuration\RepositoryConfiguration;

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
}
