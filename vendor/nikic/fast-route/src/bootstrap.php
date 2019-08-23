<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace FastRoute;

require __DIR__ . '/functions.php';
spl_autoload_register(function ($class) {
    if (strpos($class, 'FastRoute\\') === 0) {
        $name = substr($class, strlen('FastRoute'));
        require __DIR__ . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});

?>