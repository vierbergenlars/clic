<?php

namespace vierbergenlars\CliCentral\Exception;

use Exception;

class JsonValidationException extends \RuntimeException
{
    /**
     * @var string[]
     */
    private $errors = [];

    /**
     * JsonValidationException constructor.
     * @param string $message
     * @param string[] $errors
     * @param Exception|null $previous
     */
    public function __construct($message, $errors, Exception $previous = null)
    {
        $this->errors = $errors;
        foreach($errors as $error) {
            $message.=PHP_EOL.' * '.$error;
        }
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
