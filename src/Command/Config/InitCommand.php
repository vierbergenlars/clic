<?php

namespace vierbergenlars\CliCentral\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use vierbergenlars\CliCentral\Exception\Configuration\MissingConfigurationParameterException;
use vierbergenlars\CliCentral\Exception\File\FileException;
use vierbergenlars\CliCentral\Exception\File\FileExistsException;
use vierbergenlars\CliCentral\FsUtil;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class InitCommand extends Command
{
    protected function configure()
    {
        $this->setName('config:init')
            ->addOption('no-create-missing', null, InputOption::VALUE_NONE, 'Do not create missing directories')
            ->setDescription('Initialize configuration')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command presents a guide to do the initial configuration.
This command will ask interactive questions, unless <comment>--no-interaction|-n</comment> is used.

  <info>%command.full_name%</info>

It will help you to set up the configuration values <comment>applications-dir</comment>, <comment>vhosts-dir</comment>
and <comment>ssh-dir</comment>.

Non-existing directories will automatically be created, unless the <comment>--no-create-missing</comment> option is used.

This command can only be used to create the initial configuration file, it cannot be used to change configuration parameters.

To read configuration parameters, use the <info>config:get</info> command.
To set configuration parameters, use the <info>config:set</info> command.
To remove configuration parameters, use the <info>config:unset</info> command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configHelper = $this->getHelper('configuration');
        /* @var $configHelper GlobalConfigurationHelper */

        try {
            if (file_exists($configHelper->getConfiguration()->getConfigFile()))
                throw new FileExistsException($configHelper->getConfiguration()->getConfigFile());
        } catch(FileException $ex) {
            throw new \RuntimeException('The global configuration is already created.', 0, $ex);
        }

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
                if(!$input->getOption('no-create-missing')) {
                    FsUtil::mkdir($dir, true);
                    $output->writeln(sprintf('Created directory <info>%s</info>', $dir), OutputInterface::VERBOSITY_VERBOSE);
                }
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
