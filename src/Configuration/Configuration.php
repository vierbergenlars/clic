<?php

namespace vierbergenlars\CliCentral\Configuration;

use vierbergenlars\CliCentral\Exception\JsonValidationException;
use vierbergenlars\CliCentral\Exception\NotAFileException;
use vierbergenlars\CliCentral\Exception\UnreadableFileException;
use JsonSchema\Validator;

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
        $validator->check($this->config, $this->getSchema());
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

    protected function getConfigOption(array $path, $default = null)
    {
        $conf = $this->getConfig();
        while(($part = array_shift($path)) !== null) {
            if(isset($conf->{$part})) {
                $conf = $conf->{$part};
            } else {
                return $default;
            }
        }
        return $conf;
    }

    protected function setConfigOption(array $path, $value)
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

    abstract protected function getSchema();

    public function write()
    {
        $this->validate();
        file_put_contents($this->getConfigFile()->getPathname(),json_encode($this->config, JSON_PRETTY_PRINT));
    }
}
