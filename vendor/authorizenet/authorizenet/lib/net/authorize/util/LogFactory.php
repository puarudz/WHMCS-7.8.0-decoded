<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\util;

class LogFactory
{
    private static $logger = NULL;
    public static function getLog($classType)
    {
        if (NULL == self::$logger) {
            self::$logger = new Log();
            if (defined('AUTHORIZENET_LOG_FILE')) {
                self::$logger->setLogFile(AUTHORIZENET_LOG_FILE);
            }
        }
        return self::$logger;
    }
}

?>