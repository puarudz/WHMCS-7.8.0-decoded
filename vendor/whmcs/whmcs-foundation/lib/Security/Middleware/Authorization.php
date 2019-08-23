<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Security\Middleware;

class Authorization implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\DelegatingMiddlewareTrait;
    protected $request = NULL;
    protected $csrfRequestMethods = array();
    protected $csrfNamespace = "";
    protected $csrfCheckRequired = true;
    protected $requireAnyPermission = array();
    protected $requireAllPermission = array();
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $this->setRequest($request);
        return $this->delegateProcess($request, $delegate);
    }
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $user = $request->getAttribute("authenticatedUser");
        return $this->assertAuthorization($request, $user);
    }
    public function assertAuthorization(\WHMCS\Http\Message\ServerRequest $request, \WHMCS\User\UserInterface $user = NULL)
    {
        if (!$this->hasValidCsrfToken()) {
            return $this->responseInvalidCsrfToken();
        }
        $anyPermission = array_filter($this->getRequireAnyPermission());
        $allPermission = array_filter($this->getRequireAllPermission());
        if (empty($anyPermission) && empty($allPermission)) {
            return $request;
        }
        if (!$user instanceof \WHMCS\User\UserInterface) {
            throw new \WHMCS\Exception\Authorization\AccessDenied("Authentication Required");
        }
        try {
            foreach ($allPermission as $permissionName) {
                if (!$user->hasPermission($permissionName)) {
                    return $this->responseMissingMultiplePermissions($allPermission);
                }
            }
        } catch (\Exception $e) {
            return $this->responseMissingMultiplePermissions($allPermission);
        }
        if (empty($anyPermission)) {
            return $request;
        }
        $isAllowed = false;
        try {
            foreach ($anyPermission as $permissionName) {
                if ($user->hasPermission($permissionName)) {
                    $isAllowed = true;
                    break;
                }
            }
        } catch (\Exception $e) {
        }
        if (!$isAllowed) {
            return $this->responseMissingPermission($anyPermission);
        }
        return $request;
    }
    protected function responseInvalidCsrfToken()
    {
        throw new \WHMCS\Exception\Authorization\InvalidCsrfToken("Invalid CSRF Protection Token");
    }
    protected function responseMissingMultiplePermissions(array $permissionNames = array())
    {
        throw new \WHMCS\Exception\Authorization\AccessDenied("Invalid Permissions. Requires \"" . implode("\", \"", $permissionNames) . "\".");
    }
    protected function responseMissingPermission(array $permissionNames = array())
    {
        throw new \WHMCS\Exception\Authorization\AccessDenied("Invalid Permissions. Requires at least one of the following: \"" . implode("\", \"", $permissionNames) . "\".");
    }
    public function requireCsrfToken(array $csrfRequestMethods = NULL, $csrfNamespace = NULL)
    {
        $this->setCsrfCheckRequired(true);
        if (is_null($csrfRequestMethods)) {
            $csrfRequestMethods = $this->getDefaultCsrfRequestMethods();
        }
        $this->setCsrfRequestMethods($csrfRequestMethods);
        if (is_null($csrfNamespace)) {
            $csrfNamespace = $this->getDefaultCsrfNamespace();
        }
        $this->setCsrfNamespace($csrfNamespace);
        return $this;
    }
    public function hasValidCsrfToken()
    {
        if (!$this->isCsrfCheckRequired()) {
            return true;
        }
        $requestMethod = $this->getRequest()->getMethod();
        if (!in_array($requestMethod, $this->getCsrfRequestMethods())) {
            return true;
        }
        $token = $this->getRequest()->get("token");
        try {
            check_token($this->getCsrfNamespace(), $token);
        } catch (\WHMCS\Exception\ProgramExit $e) {
            return false;
        }
        return true;
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function setRequest(\WHMCS\Http\Message\ServerRequest $request)
    {
        $this->request = $request;
        return $this;
    }
    public function getDefaultCsrfNamespace()
    {
        return "WHMCS.default";
    }
    public function getDefaultCsrfRequestMethods()
    {
        return array("POST");
    }
    public function getCsrfRequestMethods()
    {
        return $this->csrfRequestMethods;
    }
    public function setCsrfRequestMethods(array $csrfRequestMethods)
    {
        $this->csrfRequestMethods = $csrfRequestMethods;
        return $this;
    }
    public function getCsrfNamespace()
    {
        return $this->csrfNamespace;
    }
    public function setCsrfNamespace($csrfNamespace)
    {
        $this->csrfNamespace = $csrfNamespace;
        return $this;
    }
    public function isCsrfCheckRequired()
    {
        return $this->csrfCheckRequired;
    }
    public function setCsrfCheckRequired($checkRequired)
    {
        $this->csrfCheckRequired = (bool) $checkRequired;
        return $this;
    }
    public function getRequireAnyPermission()
    {
        return $this->requireAnyPermission;
    }
    public function setRequireAnyPermission(array $permissions = array())
    {
        $this->requireAnyPermission = $permissions;
        return $this;
    }
    public function getRequireAllPermission()
    {
        return $this->requireAllPermission;
    }
    public function setRequireAllPermission(array $permissions = array())
    {
        $this->requireAllPermission = $permissions;
        return $this;
    }
}

?>