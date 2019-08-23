<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Filter;

class Input
{
    public static function url($url)
    {
        if (function_exists("filter_var")) {
            return filter_var($url, FILTER_VALIDATE_URL);
        }
        $streamPattern = "/^[a-zA-Z0-9]+\\s?:\\s?\\//";
        if (preg_match($streamPattern, $url)) {
            return $url;
        }
        return false;
    }
}

?>