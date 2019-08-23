<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility\Environment;

class CurrentUser
{
    public static function getIP()
    {
        $config = \DI::make("config");
        $useLegacyIpLogic = !empty($config["use_legacy_client_ip_logic"]) ? true : false;
        if ($useLegacyIpLogic) {
            $ip = self::getForwardedIpWithoutTrust();
        } else {
            $request = new \WHMCS\Http\Request($_SERVER);
            $ip = (string) filter_var($request->getClientIp(), FILTER_VALIDATE_IP);
        }
        return $ip;
    }
    public static function getForwardedIpWithoutTrust()
    {
        if (function_exists("apache_request_headers")) {
            $headers = apache_request_headers();
            if (array_key_exists("X-Forwarded-For", $headers)) {
                $userip = explode(",", $headers["X-Forwarded-For"]);
                $ip = trim($userip[0]);
                if (self::isIpv4AndPublic($ip)) {
                    return $ip;
                }
            }
        } else {
            $ip_array = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]) : array();
            if (count($ip_array)) {
                $ip = trim($ip_array[count($ip_array) - 1]);
                if (self::isIpv4AndPublic($ip)) {
                    return $ip;
                }
            }
        }
        if (isset($_SERVER["HTTP_X_FORWARDED"]) && self::isIpv4AndPublic($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        }
        if (isset($_SERVER["HTTP_FORWARDED_FOR"]) && self::isIpv4AndPublic($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        }
        if (isset($_SERVER["HTTP_FORWARDED"]) && self::isIpv4AndPublic($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        }
        if (isset($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return "";
    }
    public static function getIPHost()
    {
        $usersIP = self::getIP();
        $fullhost = gethostbyaddr($usersIP);
        return $fullhost ? $fullhost : "Unable to resolve hostname";
    }
    public static function isIpv4AndPublic($ip)
    {
        if (!empty($ip) && ip2long($ip) != -1 && ip2long($ip) != false) {
            $private_ips = array(array("0.0.0.0", "2.255.255.255"), array("10.0.0.0", "10.255.255.255"), array("127.0.0.0", "127.255.255.255"), array("169.254.0.0", "169.254.255.255"), array("172.16.0.0", "172.31.255.255"), array("192.0.2.0", "192.0.2.255"), array("192.168.0.0", "192.168.255.255"), array("255.255.255.0", "255.255.255.255"));
            foreach ($private_ips as $r) {
                $min = ip2long($r[0]);
                $max = ip2long($r[1]);
                if ($min <= ip2long($ip) && ip2long($ip) <= $max) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }
}

?>