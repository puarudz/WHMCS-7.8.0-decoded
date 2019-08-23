<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use Symfony\Component\Console\Command\Command;
class BarBucCommand extends Command
{
    protected function configure()
    {
        $this->setName('bar:buc');
    }
}

?>