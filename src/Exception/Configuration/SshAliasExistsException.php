<?php
/**
 * clic, user-friendly PHP application deployment and set-up
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Lars Vierbergen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */


namespace vierbergenlars\CliCentral\Exception\Configuration;

class SshAliasExistsException extends \RuntimeException
{
    /**
     * @var string
     */
    private $aliasName;
    /**
     * @var int
     */
    private $lineNumber;

    /**
     * @var string
     */
    private $fileName;

    /**
     * SshAliasExistsException constructor.
     * @param string $aliasName
     * @param int $lineNumber
     * @param null $fileName
     * @param \Exception|null $previous
     */
    public function __construct($aliasName, $lineNumber, $fileName = null, \Exception $previous = null)
    {
        parent::__construct(sprintf('Ssh alias "%s" already exists in %s at %d', $aliasName, $fileName, $lineNumber), 0, $previous);
        $this->aliasName = $aliasName;
        $this->lineNumber = $lineNumber;
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getAliasName()
    {
        return $this->aliasName;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}
