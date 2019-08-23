<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\GrantType;

class SingleSignOn extends \OAuth2\GrantType\ClientCredentials
{
    public function getQuerystringIdentifier()
    {
        return "single_sign_on";
    }
}

?>