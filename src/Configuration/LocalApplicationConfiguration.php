<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\NoScriptException;

class LocalApplicationConfiguration extends Configuration
{
    /**
     * @var \stdClass
     */
    private $overrides;

    public function __construct(\SplFileInfo $configFile, \stdClass $overrides)
    {
        parent::__construct($configFile);

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
