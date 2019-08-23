<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Basic;

class Clear extends BasicTerminalObject
{
    /**
     * Clear the terminal
     *
     * @return string
     */
    public function result()
    {
        return "\33[H\33[2J";
    }
    public function sameLine()
    {
        return true;
    }
}

?>