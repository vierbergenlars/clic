<?php

namespace vierbergenlars\CliCentral\Exception\File;

use Exception;
use vierbergenlars\CliCentral\PathUtil;

class OutsideConfiguredRootDirectoryException extends FileException
{
    protected $template = '"%s" is outside the directory set by configuration option %s (%s).';

    /**
     * OutsideConfiguredRootDirectoryException constructor.
     * @param \SplFileInfo|string $filename
     * @param string $configOption
     * @param \SplFileInfo|string $configValue
     * @param Exception|null $previous
     */
    public function __construct($filename, $configOption, $configValue, \Exception $previous = null)
    {
        if($configValue instanceof \SplFileInfo)
            $configValue = $configValue->getPath();
        parent::__construct($filename, $previous, [$configOption, $configValue]);
    }

    public static function assert($filename, $configOption, $configValue)
    {
        if(!PathUtil::isSubDirectory($filename, $configValue))
            throw new self($filename, $configOption, $configValue);
    }
}
