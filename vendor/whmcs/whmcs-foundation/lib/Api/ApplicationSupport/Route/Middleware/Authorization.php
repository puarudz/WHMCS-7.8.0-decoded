<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Route\Middleware;

class Authorization extends \WHMCS\Security\Middleware\Authorization
{
    public function assertAuthorization(\WHMCS\Http\Message\ServerRequest $request, \WHMCS\User\UserInterface $user = NULL)
    {
        if (!$request instanceof \WHMCS\Api\ApplicationSupport\Http\ServerRequest) {
            throw new \WHMCS\Exception\HttpCodeException("Invalid server request instance", 500);
        }
        $baseCheck = parent::assertAuthorization($request, $user);
        if ($baseCheck instanceof \Psr\Http\Message\ResponseInterface) {
            return $baseCheck;
        }
        $action = $request->getAction();
        if (!$action) {
            throw new \WHMCS\Exception\HttpCodeException("Empty action request", 400);
        }
        $device = $request->getAttribute("authenticatedDevice", null);
        if ($device) {
            if (!$device->permissions()->isAllowed($action)) {
                return $this->responseActionNotAllowed($action);
            }
        } else {
            $admin = $request->getAttribute("authenticatedUser", null);
            if (!$admin || !$admin->hasPermission("API Access")) {
                throw new \WHMCS\Exception\Api\AuthException("Access Denied");
            }
        }
        return $request;
    }
    public function hasValidCsrfToken()
    {
        return true;
    }
    protected function responseActionNotAllowed($action)
    {
        throw new \WHMCS\Exception\Authorization\AccessDenied("Invalid Permissions: API action \"" . $action . "\" is not allowed");
    }
}

?>