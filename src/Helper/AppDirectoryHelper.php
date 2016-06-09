<?php

namespace vierbergenlars\CliCentral\Helper;

use vierbergenlars\CliCentral\ApplicationEnvironment\Environment;
use vierbergenlars\CliCentral\Configuration\GlobalConfiguration;
use vierbergenlars\CliCentral\Exception\NotADirectoryException;
use vierbergenlars\CliCentral\Exception\NotALinkException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;

class AppDirectoryHelper extends Helper implements InputAwareInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'app_directory';
    }

    /**
     * Sets the Console Input.
     *
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    public function getEnvironment()
    {
        $baseDir = $this->getConfiguration()->getApplicationsDirectory();
        if(!$baseDir)
            throw new InvalidArgumentException('The applications-dir option does not have a value.');
        $environment = $this->input->getOption('env');
        if(!$environment)
            throw new InvalidArgumentException('The environment option does not have a value.');
        if(!is_dir($baseDir))
            throw new NotADirectoryException($baseDir);
        $envDir = $baseDir.'/'.$environment;
        if(!is_dir($envDir))
            throw new NotADirectoryException($envDir);
        return new Environment($envDir);
    }

    public function getDirectoryForApplication($applicationName)
    {
        $baseDir = $this->getConfiguration()->getApplicationsDirectory();
        if(!$baseDir)
            throw new InvalidArgumentException('The applications-dir option does not have a value.');
        $environment = $this->input->getOption('env');
        if(!$environment)
            throw new InvalidArgumentException('The environment option does not have a value.');

        if(!is_dir($baseDir))
            throw new NotADirectoryException($baseDir);
        $envDir = $baseDir.'/'.$environment;
        if(!is_dir($envDir))
            throw new NotADirectoryException($envDir);
        $fullDir = $envDir.'/'.$applicationName;
        if(!is_dir($fullDir))
            throw new NotADirectoryException($fullDir);

        return $fullDir;
    }

    public function getLinkForVhost($vhostName)
    {
        $baseDir = $this->getConfiguration()->getVhostsDirectory();
        if(!$baseDir)
            throw new InvalidArgumentException('The vhosts-dir option does not have a value.');
        if(!is_dir($baseDir))
            throw new NotADirectoryException($baseDir);
        $linkName = $baseDir.'/'.$vhostName;
        if(!is_link($linkName)&&file_exists($linkName))
            throw new NotALinkException($linkName);
        return $linkName;
    }

    /**
     * @return GlobalConfiguration
     */
    private function getConfiguration()
    {
        return $this->getHelperSet()->get('configuration')->getConfiguration();
    }
}
