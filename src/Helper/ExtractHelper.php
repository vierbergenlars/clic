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

namespace vierbergenlars\CliCentral\Helper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\PathUtil;
use vierbergenlars\CliCentral\Util;

class ExtractHelper extends Helper
{
    /**
     * @param string $url
     * @param OutputInterface $output
     * @return string Location of the downloaded file
     */
    public function downloadFile($url, OutputInterface $output)
    {
        if($output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE)
            $output->write(sprintf('Downloading <comment>%s</comment>...', $url));

        $processHelper = $this->getHelperSet()->get('process');
        /* @var $processHelper ProcessHelper */
        $tempDir = $this->getTempDir($url);
        $outputFile = $tempDir.'/'.basename($url);
        $wgetProcess = ProcessBuilder::create([
            'wget',
            $url,
            '-O',
            $outputFile,
        ])->setTimeout(null)->getProcess();
        $processHelper->mustRun($output, $wgetProcess);

        if($output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE)
            $output->writeln('<info>OK</info>');

        return $outputFile;
    }

    /**
     * @param string $archiveFile
     * @param string $targetDir
     * @param OutputInterface $output
     */
    public function extractArchive($archiveFile, $targetDir, OutputInterface $output)
    {
        if($output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE)
            $output->write(sprintf('Extracting <comment>%s</comment> to <comment>%s</comment>...', $archiveFile, $targetDir));

        $processHelper = $this->getHelperSet()->get('process');
        /* @var $processHelper ProcessHelper */
        $tempDir = $this->getTempDir($archiveFile);
        $extractProcess = Util::getExtractProcess($archiveFile);
        if(!$extractProcess)
            throw new \RuntimeException(sprintf('Archive type of %s cannot be handled', $archiveFile));
        $extractProcess->setWorkingDirectory($tempDir);
        $processHelper->mustRun($output, $extractProcess);

        $commonPrefix = PathUtil::commonPrefix(Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->directories()
            ->in($tempDir));

        FsUtil::rename($commonPrefix, $targetDir);

        foreach(Finder::create()
                    ->ignoreDotFiles(false)
                    ->ignoreVCS(false)
                    ->directories()
                    ->in($tempDir) as $files)
            FsUtil::rmdir($files);

        if($output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE)
            $output->writeln('<info>OK</info>');

    }

    public function getName()
    {
        return 'extract';
    }

    public function getTempDir($seed)
    {
        $configHelper = $this->getHelperSet()->get('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $tempDir = $configHelper->getConfiguration()->getClicDirectory() . '/tmp/' . sha1(time().$seed);
        if (!is_dir($tempDir))
            FsUtil::mkdir($tempDir, true);
        return $tempDir;
    }
}

