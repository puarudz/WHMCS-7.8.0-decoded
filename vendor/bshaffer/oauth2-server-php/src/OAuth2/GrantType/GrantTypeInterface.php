<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2\GrantType;

use OAuth2\ResponseType\AccessTokenInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
/**
 * Interface for all OAuth2 Grant Types
 */
interface GrantTypeInterface
{
    public function getQuerystringIdentifier();
    public function validateRequest(RequestInterface $request, ResponseInterface $response);
    public function getClientId();
    public function getUserId();
    public function getScope();
    public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope);
}

?>