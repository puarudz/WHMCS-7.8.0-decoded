<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class FooCommand extends Command
{
    public $input;
    public $output;
    protected function configure()
    {
        $this->setName('foo:bar')->setDescription('The foo:bar command')->setAliases(array('afoobar'));
    }
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('interact called');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $output->writeln('called');
    }
}

?>