<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Command;

class HelpCommand extends \Symfony\Component\Console\Command\Command
{
    private $command = NULL;
    protected function configure()
    {
        $this->ignoreValidationErrors();
        $this->setName("help")->setDefinition($this->getDefaultInputDefinition())->setDescription("Displays help for a command")->setHelp("The <info>%command.name%</info> command displays help for a given command:\n\n  <info>php %command.full_name% list</info>\n\nTo display the list of available commands, please use the <info>list</info> command.");
    }
    public function getNativeDefinition()
    {
        return $this->getDefaultInputDefinition();
    }
    protected function getDefaultInputDefinition()
    {
        return new \Symfony\Component\Console\Input\InputDefinition(array(new \Symfony\Component\Console\Input\InputArgument("command_name", \Symfony\Component\Console\Input\InputArgument::OPTIONAL, "The command name", "help")));
    }
    public function setCommand(\Symfony\Component\Console\Command\Command $command)
    {
        $this->command = $command;
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (null === $this->command) {
            $this->command = $this->getApplication()->find($input->getArgument("command_name"));
        }
        $helper = new \Symfony\Component\Console\Helper\DescriptorHelper();
        $helper->describe($output, $this->command, array("format" => "txt", "raw_text" => ""));
        $this->command = null;
    }
}

?>