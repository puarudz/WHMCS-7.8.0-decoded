<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\util;

/**
 * A class defining helpers
 *
 * @package    AuthorizeNet
 * @subpackage net\authorize\util
 */
class Helpers
{
    private static $initialized = false;
    /**
     * @return string current date-time
     */
    public static function now()
    {
        //init only once
        if (!self::$initialized) {
            self::$initialized = true;
        }
        return date(DATE_RFC2822);
    }
}

?>