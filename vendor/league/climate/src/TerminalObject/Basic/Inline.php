<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Basic;

class Inline extends Out
{
    /**
     * Check if this object requires a new line should be added after the output
     *
     * @return boolean
     */
    public function sameLine()
    {
        return true;
    }
}

?>