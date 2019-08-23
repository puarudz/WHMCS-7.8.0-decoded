<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\Server;

interface ApplicationLinkServerInterface extends \OAuth2\Controller\TokenControllerInterface, \OAuth2\Controller\ResourceControllerInterface
{
    public function postAccessTokenResponseAction(\OAuth2\RequestInterface $request, \OAuth2\ResponseInterface $response);
}

?>