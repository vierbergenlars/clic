<?php

namespace vierbergenlars\CliCentral\Helper;

use vierbergenlars\CliCentral\Configuration\GlobalConfiguration;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;

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
        if(!$this->globalConfiguration)
            $this->globalConfiguration = new GlobalConfiguration(new \SplFileInfo($this->input->getOption('config')));
        return $this->globalConfiguration;
    }
}
