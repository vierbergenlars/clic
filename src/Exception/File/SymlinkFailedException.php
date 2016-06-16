<?php

namespace vierbergenlars\CliCentral\Exception\File;

class SymlinkFailedException extends FilesystemOperationFailedException
{
    protected $template = 'symlink(%2$s, %1$s) failed: %3$s';

    /**
     * FilesystemOperationFailedException constructor.
     * @param \SplFileInfo|string $filename
     * @param \SplFileInfo|string $target
     * @param \Exception|null $previous
     */
    public function __construct($filename, $target, \Exception $previous = null)
    {
        parent::__construct($filename, 'symlink', $previous, [
            $target,
            error_get_last()['message'],
        ]);
    }
}
