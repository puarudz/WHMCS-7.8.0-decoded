<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Authentication;

abstract class AbstractAuthenticationException extends \WHMCS\Exception\HttpCodeException
{
    const DEFAULT_HTTP_CODE = 403;
}

?>