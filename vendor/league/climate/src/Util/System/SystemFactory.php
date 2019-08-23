<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util\System;

class SystemFactory
{
    /**
     * @var \League\CLImate\Util\System\System $instance
     */
    protected static $instance;
    /**
     * Get an instance of the appropriate System class
     *
     * @return \League\CLImate\Util\System\System
     */
    public static function getInstance()
    {
        if (static::$instance) {
            return static::$instance;
        }
        static::$instance = self::getSystem();
        return static::$instance;
    }
    /**
     * Set the $instance property to the appropriate system
     *
     * @return \League\CLImate\Util\System\System
     */
    protected static function getSystem()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return new Windows();
        }
        return new Linux();
    }
}

?>