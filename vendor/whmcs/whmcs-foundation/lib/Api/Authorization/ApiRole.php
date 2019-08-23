<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\Authorization;

class ApiRole extends \WHMCS\Authorization\Rbac\AbstractRole
{
    protected $table = "tblapi_roles";
}

?>