<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment;

class OperatingSystem
{
    public static function isWindows($phpOs = PHP_OS)
    {
        return in_array($phpOs, array("Windows", "WIN32", "WINNT"));
    }
    public function isOwnedByMe($path)
    {
        return fileowner($path) == Php::getUserRunningPhp();
    }
}

?>