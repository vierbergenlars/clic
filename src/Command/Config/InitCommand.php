<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Input\InputOption;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\FileException;
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
        $this->setName('config:init')
            ->addOption('no-create-missing', null, InputOption::VALUE_NONE, 'Do not create missing directories')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        $this->askDirectoryQuestion(
            [$configHelper->getConfiguration(), 'getApplicationsDirectory'],
            [$configHelper->getConfiguration(), 'setApplicationsDirectory'],
            'Where are your application environments located?',
            $input,
            $output
        );

        $this->askDirectoryQuestion(
            [$configHelper->getConfiguration(), 'getVhostsDirectory'],
            [$configHelper->getConfiguration(), 'setVhostsDirectory'],
            'Where is your webserver root located?',
            $input,
            $output
        );

        $this->askDirectoryQuestion(
            [$configHelper->getConfiguration(), 'getSshDirectory'],
            [$configHelper->getConfiguration(), 'setSshDirectory'],
            'Where is your .ssh directory located?',
            $input,
            $output
        );

        $configHelper->getConfiguration()->write();

        $output->writeln(sprintf('<comment>Settings written to <info>%s</info></comment>', $configHelper->getConfiguration()->getConfigFile()));
    }

    private function askDirectoryQuestion(callable $getter, callable $setter, $questionText, InputInterface $input, OutputInterface $output)
    {
        try {
            $directory = $getter();
        } catch(MissingConfigurationParameterException $ex) {
            $directory = null;
        } catch(FileException $ex) {
            $directory = $ex->getFilename();
        }
        $question = new Question($questionText, $directory);
        $question->setValidator(function($dir) use ($input, $output) {
            if(!is_dir($dir))
                if($input->getOption('no-create-missing')||!@mkdir($dir, 0777, true))
                    throw new NotADirectoryException($dir);
                else
                    $output->writeln(sprintf('Created directory <info>%s</info>', $dir), OutputInterface::VERBOSITY_VERBOSE);
            return $dir;
        });
        $questionHelper = $this->getHelper('question');
        /* @var $questionHelper QuestionHelper */
        $dirAnswer = $questionHelper->ask($input, $output, $question);
        $validator = $question->getValidator();
        $dirAnswer = $validator($dirAnswer);
        $setter($dirAnswer);
    }
}
