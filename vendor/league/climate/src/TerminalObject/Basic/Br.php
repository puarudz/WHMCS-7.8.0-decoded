<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Basic;

class Br extends Repeatable
{
    /**
     * Return an empty string
     *
     * @return string
     */
    public function result()
    {
        return array_fill(0, $this->count, '');
    }
}

?>