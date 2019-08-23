<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Transip_Nameserver
{
    public $hostname = "";
    public $ipv4 = "";
    public $ipv6 = "";
    public function __construct($hostname, $ipv4 = "", $ipv6 = "")
    {
        $this->hostname = $hostname;
        $this->ipv4 = $ipv4;
        $this->ipv6 = $ipv6;
    }
}

?>