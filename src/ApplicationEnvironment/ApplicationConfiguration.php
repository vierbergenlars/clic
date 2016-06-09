<?php

namespace vierbergenlars\CliCentral\ApplicationEnvironment;

use vierbergenlars\CliCentral\Configuration\Configuration;
use vierbergenlars\CliCentral\Exception\JsonValidationException;
use vierbergenlars\CliCentral\Exception\NoScriptException;
use vierbergenlars\CliCentral\Exception\NotAFileException;
use vierbergenlars\CliCentral\Exception\UnreadableFileException;
use JsonSchema\Validator;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class ApplicationConfiguration extends Configuration
{
    protected function getSchema()
    {
        return json_decode(file_get_contents(__DIR__.'/../../res/clic-schema.json'));
    }

    /**
     * @param string $scriptName
     * @return string
     */
    public function getScriptCommand($scriptName)
    {
        $script = $this->getConfigOption(['scripts', $scriptName]);
        if($script === null)
            throw new NoScriptException($scriptName);
        return $script;
    }
}
