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

namespace vierbergenlars\CliCentral\Configuration;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;

class RepositoryConfiguration
{
    private $config;

    public function __construct(\stdClass $config = null)
    {
        if(!$config)
            $config = new \stdClass();
        $this->config = $config;
    }

    /**
     * @return \stdClass
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getIdentityFile()
    {
        return $this->config->{'identity-file'};
    }

    /**
     * @param string $identityFile
     */
    public function setIdentityFile($identityFile)
    {
        $this->config->{'identity-file'} = $identityFile;
    }

    /**
     * @return string
     */
    public function getSshAlias()
    {
        return $this->config->{'ssh-alias'};
    }

    /**
     * @param string $sshAlias
     */
    public function setSshAlias($sshAlias)
    {
        $this->config->{'ssh-alias'} = $sshAlias;
    }

    public function getSshFingerprint()
    {
        return trim(ProcessBuilder::create([
            'ssh-keygen',
            '-l',
            '-E',
            'md5',
            '-f',
            $this->getIdentityFile(),
        ])->getProcess()->mustRun()->getOutput());
    }

    public function getSshFingerprintMessage()
    {
        try {
            $fingerprintParts = preg_split('/[ ]+/', $this->getSshFingerprint());
            $bits = array_shift($fingerprintParts);
            $fingerprint = array_shift($fingerprintParts);
            $type = array_pop($fingerprintParts);
            $comment = implode(' ', $fingerprintParts);
            return sprintf('%s <info>%s</info> %s'.PHP_EOL.'<comment>%s</comment>', $bits, $fingerprint, $type, $comment);
        } catch(ProcessFailedException $ex) {
            $errorMessage = $this->getErrorMessage();
            if($errorMessage)
                return $errorMessage;
            $errorOutput = $ex->getProcess()->getErrorOutput();
            return '<error>'.str_replace(PHP_EOL, '</error>'.PHP_EOL.'<error>', $errorOutput).'</error>';
        }
    }

    public function getErrorMessage()
    {
        if(!is_file($this->getIdentityFile()))
            return '<error>Missing private key file</error>';
        return null;
    }

    public function getStatusMessage()
    {
        $errorMessage = $this->getErrorMessage();
        if($errorMessage)
            return $errorMessage;
        if(!is_file($this->getIdentityFile().'.pub'))
            return '<comment>Missing public key file</comment>';
        return '<info>OK</info>';
    }
}
