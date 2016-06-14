<?php

namespace vierbergenlars\CliCentral\Exception\File;

class InvalidLinkTargetException extends FileException
{
    protected $template = '"%s" is a symlink to "%s", but was expected to link to "%s".';

    /**
     * InvalidLinkTargetException constructor.
     * @param \SplFileInfo|string $linkName
     * @param \SplFileInfo|string $expectedTarget
     * @param \Exception|null $previous
     */
    public function __construct($linkName, $expectedTarget, \Exception $previous = null)
    {
        if(!is_link($linkName))
            throw new NotALinkException($linkName);
        $linkTarget = readlink($linkName);
        if($expectedTarget instanceof \SplFileInfo)
            $expectedTarget = $expectedTarget->getPathname();
        parent::__construct($linkName, $previous, [$linkTarget, $expectedTarget]);
    }
}
