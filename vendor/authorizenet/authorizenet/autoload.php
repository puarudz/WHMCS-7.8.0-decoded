<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Custom SPL autoloader for the AuthorizeNet SDK
 *
 * @package AuthorizeNet
 */
spl_autoload_register(function ($className) {
    static $classMap;
    if (!isset($classMap)) {
        $classMap = (require __DIR__ . DIRECTORY_SEPARATOR . 'classmap.php');
    }
    if (isset($classMap[$className])) {
        include $classMap[$className];
    }
});

?>