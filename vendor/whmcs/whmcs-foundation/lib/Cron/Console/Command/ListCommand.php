<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Command;

class ListCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName("list")->setDefinition($this->createDefinition())->setDescription("Lists commands")->setHelp("The <info>%command.name%</info> command lists all commands:\n\n  <info>php %command.full_name%</info>\n\nYou can also display the commands for a specific namespace:\n\n  <info>php %command.full_name% test</info>");
    }
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $helper = new \Symfony\Component\Console\Helper\DescriptorHelper();
        $helper->describe($output, $this->getApplication(), array("format" => "txt", "raw_text" => "", "namespace" => $input->getArgument("namespace")));
    }
    private function createDefinition()
    {
        return new \Symfony\Component\Console\Input\InputDefinition(array(new \Symfony\Component\Console\Input\InputArgument("namespace", \Symfony\Component\Console\Input\InputArgument::OPTIONAL, "The namespace name")));
    }
}

?>