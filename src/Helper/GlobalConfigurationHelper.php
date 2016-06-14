<?php

namespace vierbergenlars\CliCentral\Helper;

use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use vierbergenlars\CliCentral\Configuration\GlobalConfiguration;

class GlobalConfigurationHelper extends Helper implements InputAwareInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var GlobalConfiguration
     */
    private $globalConfiguration;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'configuration';
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

    public function getConfiguration()
    {
        if(!$this->globalConfiguration) {
            $configFile = $this->input->getOption('config');
            if(!$configFile)
                throw new InvalidOptionException('Missing configuration file (--config|-c)');
            $this->globalConfiguration = new GlobalConfiguration(new \SplFileInfo($configFile));
        }
        return $this->globalConfiguration;
    }

    public function getDirectoryHelper()
    {
        if(!$this->directoryHelper)
            $this->directoryHelper = new DirectoryHelper($this->getConfiguration());
        return $this->directoryHelper;
    }
}
