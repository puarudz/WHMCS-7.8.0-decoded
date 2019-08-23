<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "functions.php";
if (!defined("PROXY_FILE")) {
    try {
        $path = getWhmcsInitPath();
    } catch (Exception $e) {
        echo cronsFormatOutput(getInitPathErrorMessage());
        exit(1);
    }
    require_once $path;
}

?>