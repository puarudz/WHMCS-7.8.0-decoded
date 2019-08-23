<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Route\Middleware;

class Authentication implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\DelegatingMiddlewareTrait;
    protected $processLogin = true;
    public function disableProcessLogin()
    {
        $this->processLogin = false;
        return $this;
    }
    public function verifyDeviceCredentials($userProvidedIdentifier, $userProvidedSecret, $allowCompatVerification = false)
    {
        if (!$userProvidedIdentifier || !$userProvidedSecret) {
            throw new \WHMCS\Exception\Api\AuthException("Invalid or missing credentials");
        }
        $device = \WHMCS\Authentication\Device::byIdentifier($userProvidedIdentifier)->first();
        if (is_null($device)) {
            throw new \WHMCS\Exception\Api\AuthException("Invalid or missing credentials");
        }
        if (!$device->is_admin || !$device->admin instanceof \WHMCS\User\Admin) {
            throw new \WHMCS\Exception\Api\AuthException("Invalid administrative identifier");
        }
        if ($device->admin->disabled) {
            throw new \WHMCS\Exception\Api\AuthException("Administrator Account Disabled");
        }
        $isVerified = $device->verify($userProvidedSecret);
        if (!$isVerified && $allowCompatVerification) {
            $isVerified = $device->verifyCompat($userProvidedSecret);
        }
        if (!$isVerified) {
            $adminAuth = new \WHMCS\Auth();
            $adminAuth->getInfobyID($device->admin->id);
            $adminAuth->failedLogin();
            throw new \WHMCS\Exception\Authentication\InvalidSecret("Authentication Failed");
        }
        $device->last_access = \WHMCS\Carbon::now();
        $device->save();
        return $device;
    }
    public function verifyAdminCredentials($userProvidedUsername, $userProvidedPassword)
    {
        $adminAuth = new \WHMCS\Auth();
        $user = $adminAuth->getInfobyUsername($userProvidedUsername, false);
        if (!$user) {
            $adminAuth->failedLogin();
            throw new \WHMCS\Exception\Api\AuthException("Authentication Failed");
        }
        if (!$adminAuth->isActive()) {
            throw new \WHMCS\Exception\Api\AuthException("Administrator Account Disabled");
        }
        $hasher = new \WHMCS\Security\Hash\Password();
        try {
            $info = $hasher->getInfo($userProvidedPassword);
            if ($info["algoName"] != \WHMCS\Security\Hash\Password::HASH_MD5) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            logActivity("Unable to inspect user provided API password");
            throw new \WHMCS\Exception\Api\AuthException("Invalid password provided");
        }
        if (!$adminAuth->compareApiPassword($userProvidedPassword)) {
            $adminAuth->failedLogin();
            throw new \WHMCS\Exception\Authentication\InvalidSecret("Authentication Failed");
        }
        try {
            $needsRehash = $hasher->needsRehash($adminAuth->getLegacyAdminPW());
            if ($needsRehash) {
                $adminAuth->generateNewPasswordHashAndStoreForApi($userProvidedPassword);
            }
        } catch (\Exception $e) {
            logActivity("Failed to validate password rehash: " . $e->getMessage());
        }
        return \WHMCS\User\Admin::find($adminAuth->getAdminID());
    }
    protected function login(\WHMCS\User\Admin $admin)
    {
        $adminAuth = new \WHMCS\Auth();
        $adminAuth->getInfobyID($admin->id);
        $adminAuth->setSessionVars();
        $createLogEntry = \WHMCS\Config\Setting::getValue("LogAPIAuthentication") ? true : false;
        $adminAuth->processLogin($createLogEntry);
    }
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $device = null;
        if ($request->isDeviceAuthentication()) {
            $device = $this->verifyDeviceCredentials($request->getIdentifier(), $request->getSecret());
            $admin = $device->admin;
        } else {
            $username = $request->getUsername();
            $password = $request->getPassword();
            try {
                $device = $this->verifyDeviceCredentials($username, $password, true);
                $admin = $device->admin;
            } catch (\Exception $e) {
                $admin = $this->verifyAdminCredentials($username, $password);
            }
        }
        if ($admin) {
            if (!$admin->isAllowedToAuthenticate()) {
                throw new \WHMCS\Exception\Api\AuthException("Access Denied: Authentication not permitted");
            }
            $request = $request->withAttribute("authenticatedUser", $admin);
            if ($device) {
                $request = $request->withAttribute("authenticatedDevice", $device);
            } else {
                if (!$admin->hasPermission("API Access")) {
                    throw new \WHMCS\Exception\Api\AuthException("Access Denied");
                }
            }
            if ($this->processLogin) {
                $this->login($admin);
            }
            return $request;
        }
        throw new \WHMCS\Exception\Api\AuthException("Access Denied");
    }
}

?>