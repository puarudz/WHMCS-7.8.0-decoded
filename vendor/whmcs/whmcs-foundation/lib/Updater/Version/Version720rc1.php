<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version720rc1 extends IncrementalVersion
{
    protected $updateActions = array("addSystemURLIfNotDefined");
    protected function addSystemURLIfNotDefined()
    {
        if (\WHMCS\Config\Setting::getValue("SystemURL")) {
            return $this;
        }
        if (!isset($_SERVER["SERVER_NAME"]) && !isset($_SERVER["HTTP_HOST"])) {
            return $this;
        }
        $prefix = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] ? "https" : "http";
        $url = $prefix . "://" . $_SERVER["SERVER_NAME"] . preg_replace("#/[^/]*\\.php\$#simU", "/", $_SERVER["PHP_SELF"]);
        $url = str_replace("/" . "install/", "/", $url);
        $url = str_replace("/" . "install2/", "/", $url);
        \WHMCS\Config\Setting::setValue("SystemURL", $url);
        $updater = new Version720alpha1($this->version);
        $updater->detectAndSetUriPathMode();
        return $this;
    }
}

?>