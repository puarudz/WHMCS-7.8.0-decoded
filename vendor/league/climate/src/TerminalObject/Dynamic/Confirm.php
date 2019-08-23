<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Dynamic;

class Confirm extends Input
{
    /**
     * Let us know if the user confirmed
     *
     * @return boolean
     */
    public function confirmed()
    {
        $this->accept(['y', 'n'], true);
        $this->strict();
        return $this->prompt() == 'y';
    }
}

?>