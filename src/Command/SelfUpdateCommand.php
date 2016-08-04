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

namespace vierbergenlars\CliCentral\Command;

use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\VersionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\CliCentral\CliCentralApplication;

class SelfUpdateCommand extends Command
{
    const FILE_NAME = 'clic.phar';

    protected function configure()
    {
        $this->setName('self-update')
            ->setDescription('Update clic to the most recent stable or pre-release build.')
            ->addOption('pre', 'p', InputOption::VALUE_NONE, 'Update to most recent pre-release version tagged on Github')
            ->addOption('stable', 's', InputOption::VALUE_NONE, 'Update to the most recent stable version tagged on Github')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Checks the latest version available in the selected release channel')
            ->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Rollback to the previous version, if available on the filesystem')
        ;
    }

    public function isEnabled()
    {
        return stripos(__FILE__, 'phar://') === 0;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('check') && $input->getOption('rollback'))
            throw new \InvalidArgumentException('The --check option is not compatible with --rollback');
        if($input->getOption('rollback') && ($input->getOption('pre') || $input->getOption('stable')))
            throw new InvalidArgumentException('The --rollback option is not compatible with --pre or --stable');
        if($input->getOption('pre') && $input->getOption('stable'))
            throw new InvalidArgumentException('The --pre and --stable options are mutually exclusive');
        if(!$input->getOption('pre') && !$input->getOption('stable')) {
            $versionParser = new VersionParser();
            $input->setOption('stable', $versionParser->isStable($this->getApplication()->getVersion()));
            $input->setOption('pre', !$input->getOption('stable'));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('stable'))
            $updater = $this->getStableUpdater();
        if($input->getOption('pre'))
            $updater = $this->getPrereleaseUpdater();
        if(!isset($updater))
            throw new \UnexpectedValueException;

        if($input->getOption('check')) {
            $this->printAvailableVersion($updater, $output);
        } elseif($input->getOption('rollback')) {
            if($updater->rollback()) {
                $output->writeln('Clic has been rolled back to a previous version.');
            } else {
                $output->writeln('<error>Rollback failed for unknown reasons.</error>');
            }
        } else {
            $output->write('Updating...');
            try {
                $result = $updater->update();

                $newVersion = $updater->getNewVersion();
                $oldVersion = $updater->getOldVersion();

                $output->writeln('<info>OK</info>');
                if ($result) {
                    $output->writeln(sprintf('Clic has been updated from <info>%s</info> to <info>%s</info>', $oldVersion, $newVersion));
                } else {
                    $output->writeln(sprintf('Clic is up-to-date (version <info>%s</info>', $oldVersion));
                }
            } catch (\Exception $ex) {
                $output->writeln('<error>Failed</error>');
                $output->writeln(sprintf('Error: <error>%s</error>', $ex->getMessage()));
            }
        }
    }

    private function getUpdater()
    {
        $updater = new Updater(null, false);
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $strategy = $updater->getStrategy();
        /* @var $strategy GithubStrategy */
        $strategy->setPackageName(CliCentralApplication::PACKAGE_NAME);
        $strategy->setPharName(self::FILE_NAME);
        $strategy->setCurrentLocalVersion($this->getApplication()->getVersion());
        return $updater;
    }

    private function getStableUpdater()
    {
        $updater = $this->getUpdater();
        $strategy = $updater->getStrategy();
        /* @var $strategy GithubStrategy */
        $strategy->setStability(GithubStrategy::STABLE);
        return $updater;
    }

    private function getPrereleaseUpdater()
    {
        $updater = $this->getUpdater();
        $strategy = $updater->getStrategy();
        /* @var $strategy GithubStrategy */
        $strategy->setStability(GithubStrategy::UNSTABLE);
        return $updater;

    }

    private function printAvailableVersion(Updater $updater, OutputInterface $output)
    {
        $strategy = $updater->getStrategy();
        if(!$strategy instanceof GithubStrategy)
            throw new \UnexpectedValueException('Unexpected strategy type');
        /* @var $strategy GithubStrategy */
        $stability = $strategy->getStability() === GithubStrategy::STABLE?'stable':'pre-release';

        try {
            if($updater->hasUpdate())  {
                $output->writeln(sprintf('The current %s build available is: <options=bold>%s</options=bold>', $stability, $updater->getNewVersion()));
            } elseif($updater->getNewVersion() === false) {
                $output->writeln(sprintf('There are no %s builds available.', $stability));
            } else {
                $output->writeln(sprintf('You have the current %s build installed.', $stability));
            }

        } catch(\Exception $ex) {
            $output->writeln(sprintf('Error: <error>%s</error>', $ex->getMessage()));

        }
    }
}

