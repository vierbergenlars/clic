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


    protected function getConfigOption(array $path, $default = null, $throws = false)
    {
        $origPath = $path;
        $conf = $this->overrides;
        while(($part = array_shift($path)) !== null) {
            if(isset($conf->{$part})) {
                $conf = $conf->{$part};
            } else {
                return parent::getConfigOption($origPath, $default, $throws);
            }
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
