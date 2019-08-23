<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Account;

class TwoFactorController extends \WHMCS\Authentication\TwoFactor\TwoFactorController
{
    protected $inAdminArea = true;
    protected $userIdSessionVariableName = "adminid";
}

?>