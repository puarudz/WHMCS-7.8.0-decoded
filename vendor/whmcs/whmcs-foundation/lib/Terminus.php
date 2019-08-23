<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Terminus
{
    private static $instance = NULL;
    protected static function setInstance(Terminus $terminus)
    {
        self::$instance = $terminus;
        return $terminus;
    }
    protected static function destroyInstance()
    {
        self::$instance = null;
    }
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::setInstance(new Terminus());
        }
        return self::$instance;
    }
    public function doExit($status = 0)
    {
        $status = (int) $status;
        exit($status);
    }
    public function doDie($msg = "")
    {
        if (!headers_sent()) {
            header("HTTP/1.1 500 Internal Server Error");
        }
        if (is_string($msg)) {
            exit($msg);
        }
        exit;
    }
}

?>