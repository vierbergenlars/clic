<?php

namespace vierbergenlars\CliCentral\Exception\File;

use Exception;

abstract class FileException extends \RuntimeException
{
    protected $template = '%s';

    /**
     * @var string
     */
    private $filename;

    /**
     * FileException constructor.
     * @param string|\SplFileInfo $filename
     * @param Exception|null $previous
     */
    public function __construct($filename, Exception $previous = null, $extraArgs = [])
    {
        if($filename instanceof \SplFileInfo)
            $filename = $filename->getPathname();
        $this->filename = $filename;
        if($extraArgs === []) {
            parent::__construct(sprintf($this->template, $filename), 0, $previous);
        } else {
            array_unshift($extraArgs, $filename);
            array_unshift($extraArgs, $this->template);
            parent::__construct(call_user_func_array('sprintf', $extraArgs), 0, $previous);
        }
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
