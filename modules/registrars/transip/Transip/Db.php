<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Transip_Db
{
    public $name = NULL;
    public $username = NULL;
    public $maxDiskUsage = NULL;
    public function __construct($name, $username = "", $maxDiskUsage = 100)
    {
        $this->name = $name;
        $this->username = $username;
        $this->maxDiskUsage = $maxDiskUsage;
    }
}

?>