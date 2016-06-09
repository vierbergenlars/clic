<?php

namespace vierbergenlars\CliCentral\Exception;

use Exception;

class FileException extends \RuntimeException
{
    protected $template = '"%s"';

    /**
     * @var string
     */
    private $filename;

    /**
     * FileException constructor.
     * @param string|\SplFileInfo $filename
     * @param Exception|null $previous
     */
    public function __construct($filename, Exception $previous = null)
    {
        if($filename instanceof \SplFileInfo)
            $filename = $filename->getPathname();
        $this->filename = $filename;
        parent::__construct(sprintf($this->template, $filename), 0, $previous);
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
