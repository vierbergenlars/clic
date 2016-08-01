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

namespace vierbergenlars\CliCentral\Configuration;

use JsonSchema\Validator;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\NotAFileException;
use vierbergenlars\CliCentral\Exception\File\UnreadableFileException;
use vierbergenlars\CliCentral\Exception\JsonValidationException;

abstract class Configuration
{
    protected $config;
    private $configFile;

    /**
     * ApplicationConfiguration constructor.
     * @param \SplFileInfo $configFile
     */
    public function __construct(\SplFileInfo $configFile)
    {
        $this->configFile = $configFile;
        if(!$this->configFile->isFile())
            throw new NotAFileException($configFile);
        if(!$this->configFile->isReadable())
            throw new UnreadableFileException($configFile);
    }

    public function validate()
    {
        $this->initConfig();
        $validator = new Validator();
        $schema = $this->getSchema();
        if(!$schema)
            throw new \InvalidArgumentException('JSON schema is invalid.');
        $validator->check($this->config, $schema);
        if(!$validator->isValid()) {
            $errors = array();
            foreach ($validator->getErrors() as $error) {
                $errors[] = ($error['property'] ? $error['property'].' : ' : '').$error['message'];
            }
            throw new JsonValidationException(sprintf('"%s" does not match the expected schema.', $this->getConfigFile()->getPathname()), $errors);
        }
    }

    public function getConfigFile()
    {
        return $this->configFile;
    }

    private function initConfig()
    {
        if(!$this->config) {
            $this->config = json_decode(file_get_contents($this->getConfigFile()->getPathname()));
            if($this->config === null)
                throw new JsonValidationException(sprintf('"%s" is not valid JSON.', $this->getConfigFile()->getPathname()), [
                    json_last_error_msg()
                ]);
            $this->validate();
        }
    }

    public function getConfig()
    {
        $this->initConfig();
        return $this->config;
    }

    /**
     * @param array $path
     * @return mixed
     * @throws MissingConfigurationParameterException
     */
    public function getConfigOption(array $path)
    {
        $conf = $this->getConfig();
        $origPath = $path;
        while(($part = array_shift($path)) !== null) {
            if(isset($conf->{$part})) {
                $conf = $conf->{$part};
            } else {
                throw new MissingConfigurationParameterException(implode('.', $origPath));
            }
        }
        return $conf;
    }

    public function setConfigOption(array $path, $value)
    {
        $conf = $this->getConfig();
        $c=&$conf;
        while(($part = array_shift($path)) !== null) {
            if(!isset($c->{$part}))
                $c->{$part} = new \stdClass();
            $c = &$c->{$part};
        }
        $c = $value;
        $this->config = $conf;
    }

    public function removeConfigOption(array $path)
    {
        $conf = $this->getConfig();
        $c=&$conf;
        $lastPart = array_pop($path);
        while(($part = array_shift($path)) !== null) {
            if(!isset($c->{$part}))
                return;
            $c = &$c->{$part};
        }
        unset($c->{$lastPart});
        $this->config = $conf;
    }

    abstract protected function getSchema();

    public function write()
    {
        $this->validate();
        file_put_contents($this->getConfigFile()->getPathname(),json_encode($this->config, JSON_PRETTY_PRINT));
    }
}
