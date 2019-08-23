<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

spl_autoload_register(function ($class) {
    if (strpos($class, 'Punic\\') !== 0) {
        return;
    }
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'code' . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('Punic'))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

?>