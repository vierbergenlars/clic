<?php
/**
 * Created by PhpStorm.
 * User: Lars
 * Date: 8/06/2016
 * Time: 20:24
 */

namespace vierbergenlars\CliCentral\Exception;


class NoScriptException extends \RuntimeException
{
    public function __construct($scriptName, \Exception $previous = null)
    {
        parent::__construct(sprintf('Script "%s" does not exist.', $scriptName), 0, $previous);
    }
}