<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

?>
#!/usr/local/bin/php
<?php 
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "init.php";
define("PROXY_FILE", true);
try {
    $path = WHMCS\Cron::getCronsPath(basename(__FILE__));
    require_once $path;
} catch (WHMCS\Exception\Fatal $e) {
    echo WHMCS\Cron::formatOutput(WHMCS\Cron::getCronRootDirErrorMessage());
    WHMCS\Terminus::getInstance()->doExit(1);
} catch (Exception $e) {
    echo WHMCS\Cron::formatOutput(WHMCS\Cron::getCronPathErrorMessage());
    WHMCS\Terminus::getInstance()->doExit(1);
}

?>