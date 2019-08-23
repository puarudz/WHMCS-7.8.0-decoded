<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class Binary
{
    public static function strlen($binary_string)
    {
        if (function_exists("mb_strlen")) {
            return mb_strlen($binary_string, "8bit");
        }
        return strlen($binary_string);
    }
    public static function substr($binary_string, $start, $length)
    {
        if (function_exists("mb_substr")) {
            return mb_substr($binary_string, $start, $length, "8bit");
        }
        return substr($binary_string, $start, $length);
    }
}

?>