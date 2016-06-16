<?php

namespace vierbergenlars\CliCentral\Exception\File;

class FilesystemOperationFailedException extends FileException
{
    protected $template = '%2$s(%s) failed: %3$s';

    /**
     * FilesystemOperationFailedException constructor.
     * @param \SplFileInfo|string $filename
     * @param string $function
     * @param \Exception|null $previous
     */
    public function __construct($filename, $function, \Exception $previous = null, array $extraArgs = [])
    {
        if(!$extraArgs) {
            $extraArgs = [
                $function,
                error_get_last()['message']
            ];
        }
        parent::__construct($filename, $previous, $extraArgs);
    }
}
