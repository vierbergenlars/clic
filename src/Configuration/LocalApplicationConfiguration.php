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

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\FileException;
use vierbergenlars\CliCentral\Exception\JsonValidationException;
use vierbergenlars\CliCentral\Exception\NoScriptException;

class LocalApplicationConfiguration extends Configuration
{
    /**
     * @var \stdClass
     */
    private $overrides;

    public function __construct(\SplFileInfo $configFile, \stdClass $overrides)
    {
        try {
            parent::__construct($configFile);
        } catch(FileException $fileException) {
            $this->config = $overrides;
            try {
                $this->validate();
            } catch(JsonValidationException $ex) {
                throw $fileException;
            }
        }

        $this->overrides = $overrides;
    }

    protected function getSchema()
    {
        return json_decode(file_get_contents(__DIR__ . '/../../res/clic-schema.json'));
    }


    public function getConfigOption(array $path)
    {
        $origPath = $path;
        $conf = $this->overrides;
        while(($part = array_shift($path)) !== null) {
            if(isset($conf->{$part})) {
                $conf = $conf->{$part};
            } else {
                return parent::getConfigOption($origPath);
            }
        }
        if(is_array($conf)||$conf instanceof \stdClass) {
            $conf = array_merge((array)$conf, (array)parent::getConfigOption($origPath));
            if($conf instanceof \stdClass)
                $conf = (object)$conf;
        }
        return $conf;
    }

    /**
     * @param string $scriptName
     * @return string
     * @throws NoScriptException
     */
    public function getScriptCommand($scriptName)
    {
        try {
            $script = $this->getConfigOption(['scripts', $scriptName]);
        } catch(MissingConfigurationParameterException $ex) {
            throw new NoScriptException($scriptName, $ex);
        }
        return $script;
    }

    public function getScripts()
    {
        return array_keys(get_object_vars($this->getConfigOption(['scripts'])));
    }

    public function getWebDir()
    {
        return $this->getConfigOption(['web-dir']);
    }

    public function getVariables()
    {
        try {
            return (array)$this->getConfigOption(['vars']);
        } catch(MissingConfigurationParameterException $ex) {
            return [];
        }
    }

    public function getVariable($variableName)
    {
        return $this->getConfigOption(['vars', $variableName]);
    }

}
