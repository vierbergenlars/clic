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
}
