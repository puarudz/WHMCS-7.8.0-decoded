<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace PhpImap;

spl_autoload_register(function ($class) {
    if (strpos($class, __NAMESPACE__) === 0) {
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    }
});

?>