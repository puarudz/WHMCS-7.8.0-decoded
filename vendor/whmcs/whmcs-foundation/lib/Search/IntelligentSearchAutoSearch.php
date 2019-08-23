<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Search;

class IntelligentSearchAutoSearch
{
    const SESSION_STORAGE_NAME = "intelligentSearchAutoSearch";
    public static function isEnabled()
    {
        if (\WHMCS\Session::exists(self::SESSION_STORAGE_NAME)) {
            return (bool) \WHMCS\Session::get(self::SESSION_STORAGE_NAME);
        }
        return true;
    }
    public static function setStatus($enabled)
    {
        \WHMCS\Session::set(self::SESSION_STORAGE_NAME, (bool) $enabled);
    }
}

?>