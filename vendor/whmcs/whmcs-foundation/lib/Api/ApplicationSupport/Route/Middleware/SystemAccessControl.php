<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Route\Middleware;

class SystemAccessControl implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\AssumingMiddlewareTrait;
    protected function getSystemAccessKey()
    {
        $config = \DI::make("config");
        return $config["api_access_key"];
    }
    protected function getAllowedIps()
    {
        $allowedIps = safe_unserialize(\WHMCS\Config\Setting::getValue("APIAllowedIPs"));
        $cleanedIps = array();
        foreach ($allowedIps as $key => $allowedIp) {
            if (!empty($allowedIp["ip"]) && trim($allowedIp["ip"])) {
                $cleanedIps[] = trim($allowedIp["ip"]);
            }
        }
        return $cleanedIps;
    }
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $accessKey = $request->getAccessKey();
        if (\App::isVisitorIPBanned()) {
            throw new \WHMCS\Exception\Api\AuthException("IP Banned");
        }
        $systemAccessKey = $this->getSystemAccessKey();
        if (!empty($systemAccessKey) && $accessKey) {
            if ($accessKey != $systemAccessKey) {
                throw new \WHMCS\Exception\Api\AuthException("Invalid Access Key");
            }
        } else {
            if (!in_array(\App::getRemoteIp(), $this->getAllowedIps())) {
                throw new \WHMCS\Exception\Api\AuthException("Invalid IP " . \App::getRemoteIp());
            }
        }
        return $delegate->process($request);
    }
}

?>