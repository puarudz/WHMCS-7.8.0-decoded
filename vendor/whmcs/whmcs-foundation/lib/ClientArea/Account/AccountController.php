<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ClientArea\Account;

class AccountController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        return new \Zend\Diactoros\Response\RedirectResponse(\WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/clientarea.php?action=details");
    }
}

?>