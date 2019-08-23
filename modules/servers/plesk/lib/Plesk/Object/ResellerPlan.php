<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Plesk_Object_ResellerPlan
{
    public $id = NULL;
    public $name = NULL;
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}

?>