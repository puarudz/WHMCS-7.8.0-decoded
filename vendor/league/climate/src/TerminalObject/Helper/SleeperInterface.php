<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Helper;

interface SleeperInterface
{
    /**
     * @param int|float $percentage
     */
    public function speed($percentage);
    public function sleep();
}

?>