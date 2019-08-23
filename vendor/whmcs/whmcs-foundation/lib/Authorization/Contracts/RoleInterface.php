<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authorization\Contracts;

interface RoleInterface
{
    public function getId();
    public function allow(array $itemsToAllow);
    public function deny(array $itemsToDeny);
    public function getData();
    public function setData(array $data);
}

?>