<?php

namespace vierbergenlars\CliCentral\Command\Config;

use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\NotADirectoryException;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InitCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:init');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$input->isInteractive())
            throw new InvalidOptionException('The --no-interaction|-n option cannot be used with this command, as it will ask interactive questions.');

        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */
        $questionHelper = $this->getHelper('question');
        /* @var $questionHelper QuestionHelper */


        /*
         * Applications directory
         */
        try {
            $appDir = $configHelper->getConfiguration()->getApplicationsDirectory();
        } catch(MissingConfigurationParameterException $ex) {
            $appDir = null;
        }
        $question = new Question('Where are your application environments located?', $appDir);
        $question->setValidator(function($dir) {
            if(!is_dir($dir))
                if(!@mkdir($dir, 0777, true))
                    throw new NotADirectoryException($dir);
            return $dir;
        });
        $configHelper->getConfiguration()->setApplicationsDirectory($questionHelper->ask($input, $output, $question));

        /*
         * Vhosts directory
         */
        try {
            $vhostsDir = $configHelper->getConfiguration()->getVhostsDirectory();
        } catch(MissingConfigurationParameterException $ex) {
            $vhostsDir = null;
        }
        $question = new Question('Where is your webserver root located?', $vhostsDir);
        $question->setValidator(function($dir) {
            if(!is_dir($dir))
                if(!@mkdir($dir, 0777, true))
                    throw new NotADirectoryException($dir);
            return $dir;
        });
        $configHelper->getConfiguration()->setVhostsDirectory($questionHelper->ask($input, $output, $question));

        $configHelper->getConfiguration()->write();

        $output->writeln(sprintf('<comment>Settings written to <info>%s</info></comment>', $configHelper->getConfiguration()->getConfigFile()));
    }
}
