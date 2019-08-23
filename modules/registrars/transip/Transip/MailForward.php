<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Transip_MailForward
{
    public $name = NULL;
    public $targetAddress = NULL;
    public function __construct($name, $targetAddress)
    {
        $this->name = $name;
        $this->targetAddress = $targetAddress;
    }
}

?>