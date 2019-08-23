<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Security;

class Environment
{
    public static function setHttpProxyHeader($userConfiguredProxy = "")
    {
        if ($userConfiguredProxy) {
            $envProxy = "";
            if (isset($_SERVER["http_proxy"]) && !empty($_SERVER["http_proxy"])) {
                $_SERVER["HTTP_PROXY"] = $_SERVER["http_proxy"];
                putenv("HTTP_PROXY=" . $_SERVER["http_proxy"]);
                unset($_SERVER["http_proxy"]);
                putenv("http_proxy");
            }
            if (isset($_SERVER["HTTP_PROXY"]) && !empty($_SERVER["HTTP_PROXY"])) {
                $envProxy = $_SERVER["HTTP_PROXY"];
            }
            if ($envProxy && $envProxy == $userConfiguredProxy) {
                putenv("HTTP_PROXY=" . $userConfiguredProxy);
                $_SERVER["HTTP_PROXY"] = $envProxy;
                return NULL;
            }
        }
        putenv("http_proxy");
        unset($_SERVER["http_proxy"]);
        putenv("HTTP_PROXY");
        unset($_SERVER["HTTP_PROXY"]);
    }
}

?>