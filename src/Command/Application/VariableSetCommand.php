<?php

namespace vierbergenlars\CliCentral\Command\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use vierbergenlars\CliCentral\Helper\GlobalConfigurationHelper;

class VariableSetCommand extends Command
{
    protected function configure()
    {
        $this->setName('application:variable:set')
            ->addArgument('application', InputArgument::REQUIRED)
            ->addArgument('variable', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->addOption('description', null, InputOption::VALUE_REQUIRED)
            ->addOption('default', null, InputOption::VALUE_REQUIRED)
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if(!$input->getArgument('application')||!$input->getArgument('variable'))
            return;
        if(!$input->getOption('description')) {
            $input->setOption('description', $input->getArgument('variable'));
        }
        if(!$input->getArgument('value')) {
            $questionHelper = $this->getHelper('question');
            /* @var $questionHelper QuestionHelper */
            $question = new Question($input->getOption('description'), $input->getOption('default'));
            $response = $questionHelper->ask($input, $output, $question);
            $input->setArgument('value', $response);
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configurationHelper = $this->getHelper('configuration');
        /* @var $configurationHelper GlobalConfigurationHelper */
        $application = $configurationHelper->getConfiguration()->getApplication($input->getArgument('application'));
        $application->setVariable($input->getArgument('variable'), $input->getArgument('value'));
        $configurationHelper->getConfiguration()->write();
    }

}
