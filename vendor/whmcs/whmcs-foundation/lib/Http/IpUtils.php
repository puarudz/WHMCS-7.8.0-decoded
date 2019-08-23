<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http;

class IpUtils
{
    private function __construct()
    {
    }
    public static function checkIp($requestIp, $ips)
    {
        if (!is_array($ips)) {
            $ips = array($ips);
        }
        $method = false !== strpos($requestIp, ":") ? "checkIp6" : "checkIp4";
        foreach ($ips as $ip) {
            if (self::$method($requestIp, $ip)) {
                return true;
            }
        }
        return false;
    }
    public static function checkIp4($requestIp, $ip)
    {
        if (false !== strpos($ip, "/")) {
            list($address, $netmask) = explode("/", $ip, 2);
            if ($netmask < 1 || 32 < $netmask) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }
        return 0 === substr_compare(sprintf("%032b", ip2long($requestIp)), sprintf("%032b", ip2long($address)), 0, $netmask);
    }
    public static function checkIp6($requestIp, $ip)
    {
        if (!(extension_loaded("sockets") && defined("AF_INET6") || @inet_pton("::1"))) {
            throw new \RuntimeException("Unable to check Ipv6. Check that PHP was not compiled with option \"disable-ipv6\".");
        }
        if (false !== strpos($ip, "/")) {
            list($address, $netmask) = explode("/", $ip, 2);
            if ($netmask < 1 || 128 < $netmask) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 128;
        }
        $bytesAddr = unpack("n*", inet_pton($address));
        $bytesTest = unpack("n*", inet_pton($requestIp));
        $i = 1;
        for ($ceil = ceil($netmask / 16); $i <= $ceil; $i++) {
            $left = $netmask - 16 * ($i - 1);
            $left = $left <= 16 ? $left : 16;
            $mask = ~(65535 >> $left) & 65535;
            if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
                return false;
            }
        }
        return true;
    }
}

?>