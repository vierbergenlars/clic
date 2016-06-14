<?php

namespace vierbergenlars\CliCentral\Command\Vhost;

use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use vierbergenlars\CliCentral\Configuration\VhostConfiguration;
use vierbergenlars\CliCentral\Exception\File\FileException;
use vierbergenlars\CliCentral\Exception\File\FileExistsException;
use vierbergenlars\CliCentral\Exception\File\InvalidLinkTargetException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class FixCommand extends AbstractMultiVhostsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('vhost:fix')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Remove existing files if they prevent the vhost from being fixed.')
            ->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Remove existing directories recursively if they prevent vhost from being fixed.');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $processHelper = $this->getHelper('process');
        /* @var $processHelper ProcessHelper */


        $vhostsToFix = array_filter($input->getArgument('vhosts'), function(VhostConfiguration $vhostConfig) {
            if(!$vhostConfig->getLink()->isLink())
                return true;
            if($vhostConfig->isDisabled()&&$vhostConfig->getLink()->getLinkTarget() !== $vhostConfig->getLink()->getPathname())
                return true;
            if(!$vhostConfig->isDisabled()&&$vhostConfig->getLink()->getLinkTarget() !== $vhostConfig->getTarget()->getPathname())
                return true;
            return false;
        });

        $exitCode = 0;

        foreach($vhostsToFix as $vhost => $vhostConfig) {
            /* @var $vhostConfig VhostConfiguration */
            $output->writeln(sprintf('<bg=blue;fg=white> FIX </> <fg=blue>%s</>', $vhost));
            try {
                $vhostLink = $vhostConfig->getLink();
                if($input->getOption('force')) {
                    if($vhostLink->isLink()||$vhostLink->isFile()) {
                        if(!@unlink($vhostLink)) {
                            $output->writeln(sprintf('<bg=red;fg=white> ERR </> %s', error_get_last()['message']));
                            throw new FileExistsException($vhostLink);
                        } else {
                            $output->writeln(sprintf('<bg=green;fg=white> OUT </> Removed file "%s"', $vhostLink), OutputInterface::VERBOSITY_VERBOSE);
                        }
                    } elseif($vhostLink->isDir()) {
                        if(!@rmdir($vhostLink)) {
                            $output->writeln(sprintf('<bg=red;fg=white> ERR </> %s', error_get_last()['message']));
                            if(!$input->getOption('recursive'))
                                throw new FileExistsException($vhostLink);
                            $process = ProcessBuilder::create([
                                'rm',
                                '-rf',
                                $vhostLink->getPathname(),
                            ])->getProcess();
                            $processHelper->mustRun($output, $process);
                        } else {
                            $output->writeln(sprintf('<bg=green;fg=white> OUT </> Removed directory "%s"', $vhostLink), OutputInterface::VERBOSITY_VERBOSE);
                        }
                    }
                }
                if (!@symlink($vhostConfig->getTarget(), $vhostLink)) {
                    $output->writeln(sprintf('<bg=red;fg=white> ERR </> %s', error_get_last()['message']));
                    throw new InvalidLinkTargetException($vhostLink, $vhostConfig->getTarget());
                } else {
                    $output->writeln(sprintf('<bg=green;fg=white> OUT </> Relinked "%s" to "%s"', $vhostLink, $vhostConfig->getTarget()), OutputInterface::VERBOSITY_VERBOSE);
                }
                $output->writeln(sprintf('<bg=green;fg=white> RES </> <fg=green>Fixed vhost %s</>', $vhost));
            } catch(FileException $ex) {
                $output->writeln(sprintf('<bg=red;fg=white> RES </> <fg=red>%s</>', $ex->getMessage()));
                $exitCode = 1;
            }
        }
        return $exitCode;
    }
}
