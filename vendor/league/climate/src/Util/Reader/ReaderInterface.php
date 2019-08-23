<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util\Reader;

interface ReaderInterface
{
    /**
     * @return string
     */
    public function line();
    /**
     * @return string
     */
    public function multiLine();
}

?>