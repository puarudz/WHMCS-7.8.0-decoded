<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http;

class Request
{
    protected static $trustedProxies = array();
    protected static $trustedHostPatterns = array();
    protected static $trustedHosts = array();
    protected static $trustedHeaders = NULL;
    protected $headers = array();
    protected $server = array();
    const HEADER_CLIENT_IP = "client_ip";
    const HEADER_CLIENT_HOST = "client_host";
    const HEADER_CLIENT_PROTO = "client_proto";
    const HEADER_CLIENT_PORT = "client_port";
    public function __construct($server = array())
    {
        if (!isset($server["REMOTE_ADDR"])) {
            $server["REMOTE_ADDR"] = "";
        }
        foreach ($server as $key => $value) {
            if (strpos($key, "HTTP") === 0) {
                $key = substr($key, 5);
                $this->headers[$key] = $value;
            }
            $this->server[$key] = $value;
        }
    }
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
    }
    public static function getTrustedProxies()
    {
        return self::$trustedProxies;
    }
    public static function setTrustedHeaderName($key, $value)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf("Unable to set the trusted header name for key \"%s\".", $key));
        }
        self::$trustedHeaders[$key] = $value;
    }
    public static function getTrustedHeaderName($key)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf("Unable to get the trusted header name for key \"%s\".", $key));
        }
        return self::$trustedHeaders[$key];
    }
    public function getClientIps()
    {
        $ip = $this->server["REMOTE_ADDR"];
        if (!self::$trustedProxies) {
            return array($ip);
        }
        if (!isset(self::$trustedHeaders[self::HEADER_CLIENT_IP]) || empty($this->headers[self::$trustedHeaders[self::HEADER_CLIENT_IP]])) {
            return array($ip);
        }
        $clientIps = array_map("trim", explode(",", $this->headers[self::$trustedHeaders[self::HEADER_CLIENT_IP]]));
        $clientIps[] = $ip;
        $ip = $clientIps[0];
        foreach ($clientIps as $key => $clientIp) {
            if (IpUtils::checkIp($clientIp, self::$trustedProxies)) {
                unset($clientIps[$key]);
            }
        }
        return $clientIps ? array_reverse($clientIps) : array($ip);
    }
    public function getClientIp()
    {
        $ipAddresses = $this->getClientIps();
        return $ipAddresses[0];
    }
    public static function defineProxyTrustFromApplication(\WHMCS\Application $whmcs)
    {
        $trustedIps = array();
        $proxyHeader = $whmcs->get_config("proxyHeader");
        $trustedHeader = $proxyHeader ? $proxyHeader : "X_FORWARDED_FOR";
        self::setTrustedHeaderName(Request::HEADER_CLIENT_IP, $trustedHeader);
        $adminDefinedProxies = $whmcs->get_config("trustedProxyIps");
        $adminDefinedProxies = json_decode($adminDefinedProxies, true);
        if (!is_array($adminDefinedProxies)) {
            $adminDefinedProxies = array();
        }
        foreach ($adminDefinedProxies as $proxyDefinition) {
            $trustedIps[] = $proxyDefinition["ip"];
        }
        self::setTrustedProxies($trustedIps);
    }
}

?>