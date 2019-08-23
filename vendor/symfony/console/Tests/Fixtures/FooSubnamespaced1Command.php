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
class FooSubnamespaced1Command extends Command
{
    public $input;
    public $output;
    protected function configure()
    {
        $this->setName('foo:bar:baz')->setDescription('The foo:bar:baz command')->setAliases(array('foobarbaz'));
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}

?>