<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2\OpenID\Controller;

interface AuthorizeControllerInterface
{
    const RESPONSE_TYPE_ID_TOKEN = 'id_token';
    const RESPONSE_TYPE_ID_TOKEN_TOKEN = 'id_token token';
    const RESPONSE_TYPE_CODE_ID_TOKEN = 'code id_token';
}

?>