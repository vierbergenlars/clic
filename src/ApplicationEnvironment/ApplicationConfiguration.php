<?php

namespace vierbergenlars\CliCentral\ApplicationEnvironment;

use vierbergenlars\CliCentral\Configuration\Configuration;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\NoScriptException;

class ApplicationConfiguration extends Configuration
{
    protected function getSchema()
    {
        return json_decode(file_get_contents(__DIR__.'/../../res/clic-schema.json'));
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
