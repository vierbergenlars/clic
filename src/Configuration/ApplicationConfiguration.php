<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\NoScriptException;

class ApplicationConfiguration extends Configuration
{
    /**
     * @var array
     */
    private $overrides;

    /**
     * @var \stdClass
     */
    private $mergedConfig;

    public function __construct(\SplFileInfo $configFile, \stdClass $overrides)
    {
        parent::__construct($configFile);

        $this->overrides = (array)$overrides;
    }

    protected function getSchema()
    {
        return json_decode(file_get_contents(__DIR__ . '/../../res/clic-schema.json'));
    }

    public function getConfig()
    {
        if(!$this->mergedConfig)
            $this->mergedConfig = (object)array_merge_recursive((array)parent::getConfig(), $this->overrides);
        return $this->mergedConfig;
    }

    protected function setConfigOption(array $path, $value)
    {
        $this->mergedConfig = null;
        parent::setConfigOption($path, $value);
    }

    protected function removeConfigOption(array $path)
    {
        $this->mergedConfig = null;
        parent::removeConfigOption($path);
    }

    /**
     * @param string $scriptName
     * @return string
     * @throws NoScriptException
     */
    public function getScriptCommand($scriptName)
    {
        try {
            $script = $this->getConfigOption(['scripts', $scriptName], null, true);
        } catch(MissingConfigurationParameterException $ex) {
            throw new NoScriptException($scriptName, $ex);
        }
        return $script;
    }

    public function getWebDir()
    {
        return $this->getConfigOption(['web-dir'], null, true);
    }

}
