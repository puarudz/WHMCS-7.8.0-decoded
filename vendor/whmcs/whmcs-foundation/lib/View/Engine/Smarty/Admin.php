<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Engine\Smarty;

class Admin extends \WHMCS\Smarty implements \WHMCS\View\Engine\VariableAccessorInterface
{
    public function __construct($admin = true, $policyName = NULL)
    {
        parent::__construct($admin, $policyName);
    }
}

?>