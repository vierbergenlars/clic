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
            $this->globalConfiguration = new GlobalConfiguration($this->getConfigurationFile());
        }
        return $this->globalConfiguration;
    }

    public function getConfigurationFile()
    {
        $configFile = $this->input->getOption('config');
        if(!$configFile)
            throw new InvalidOptionException('Missing configuration file (--config|-c)');
        return new \SplFileInfo($configFile);
    }

    public function getDirectoryHelper()
    {
        if(!$this->directoryHelper)
            $this->directoryHelper = new DirectoryHelper($this->getConfiguration());
        return $this->directoryHelper;
    }
}
