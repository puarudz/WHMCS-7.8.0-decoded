<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console;

class Application extends \Symfony\Component\Console\Application
{
    protected function getDefaultInputDefinition()
    {
        return new \Symfony\Component\Console\Input\InputDefinition(array(new \Symfony\Component\Console\Input\InputArgument("command", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "The command to execute"), new \Symfony\Component\Console\Input\InputOption("--help", "-h", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Display this help message"), new \Symfony\Component\Console\Input\InputOption("--verbose", "-v|vv|vvv", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug"), new \Symfony\Component\Console\Input\InputOption("--version", "-V", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Display this application version"), new \Symfony\Component\Console\Input\InputOption("--ansi", "", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Force ANSI output"), new \Symfony\Component\Console\Input\InputOption("--no-ansi", "", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Disable ANSI output")));
    }
    protected function getDefaultCommands()
    {
        $allCmds = array(new Command\ListCommand(), new Command\HelpCommand());
        $all = new Command\AllCommand();
        $allCmds[] = $all;
        $customCmds = array(new Command\DoCommand(), new Command\SkipCommand());
        foreach ($customCmds as $cmd) {
            $this->getHelperSet()->get("task-collection")->addTasksAsOptions($cmd);
            $allCmds[] = $cmd;
        }
        $this->setDefaultCommand($all->getName());
        return $allCmds;
    }
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new Helper\TaskCollectionHelper());
        return $helperSet;
    }
    protected function getCommandName(\Symfony\Component\Console\Input\InputInterface $input)
    {
        $normalName = parent::getCommandName($input);
        if (!$normalName && true !== $input->hasParameterOption(array("--help", "-h"))) {
            return "all";
        }
        return $normalName;
    }
}

?>