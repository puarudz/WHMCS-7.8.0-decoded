<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2\TokenType;

use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
/**
* This is not yet supported!
*/
class Mac implements TokenTypeInterface
{
    public function getTokenType()
    {
        return 'mac';
    }
    public function getAccessTokenParameter(RequestInterface $request, ResponseInterface $response)
    {
        throw new \LogicException("Not supported");
    }
}

?>