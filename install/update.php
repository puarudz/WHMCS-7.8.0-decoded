<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_REQUEST["token"])) {
    header("Location: install.php");
    exit;
}
define("ROOTDIR", dirname(__DIR__));
define("INSTALLER_DIR", __DIR__);
if (file_exists(ROOTDIR . DIRECTORY_SEPARATOR . "c3.php")) {
    include ROOTDIR . DIRECTORY_SEPARATOR . "c3.php";
}
ini_set("eaccelerator.enable", 0);
ini_set("eaccelerator.optimizer", 0);
require_once ROOTDIR . "/vendor/autoload.php";
require_once ROOTDIR . "/includes/functions.php";
require_once ROOTDIR . "/includes/dbfunctions.php";
$debugErrorLevel = 32767 ^ 8 ^ 2048 ^ 8192;
$errorLevel = basename(INSTALLER_DIR) == "install2" ? $debugErrorLevel : 0;
$errMgmt = WHMCS\Utility\ErrorManagement::boot();
if (empty($errorLevel)) {
    $errMgmt::disableIniDisplayErrors();
} else {
    $errMgmt::enableIniDisplayErrors();
}
$errMgmt::setErrorReportingLevel($errorLevel);
set_time_limit(0);
$runtimeStorage = new WHMCS\Config\RuntimeStorage();
$runtimeStorage->errorManagement = $errMgmt;
WHMCS\Utility\Bootstrap\Installer::boot($runtimeStorage);
$errMgmt->loadApplicationHandlers()->loadDeferredHandlers();
try {
    DI::make("db")->getSqlVersion();
} catch (Exception $e) {
}
Log::pushHandler(WHMCS\Installer\LogServiceProvider::getUpdateLogHandler());
Log::debug("Updater bootstrapped");
$whmcsInstaller = new WHMCS\Installer\Installer(new WHMCS\Version\SemanticVersion(WHMCS\Installer\Installer::DEFAULT_VERSION), new WHMCS\Version\SemanticVersion(WHMCS\Application::FILES_VERSION));
$updaterUpdateToken = WHMCS\Config\Setting::getValue("UpdaterUpdateToken");
try {
    if ($whmcsInstaller->isUpToDate()) {
        throw new Exception("Files and database are already up to date");
    }
    if (!(0 < strlen($updaterUpdateToken) && $updaterUpdateToken == $_REQUEST["token"])) {
        throw new Exception("Invalid token");
    }
    $whmcsInstaller->runUpgrades();
    if (basename(INSTALLER_DIR) == "install") {
        try {
            $file = new WHMCS\Utility\File();
            $file->recursiveDelete(INSTALLER_DIR, array(), true);
        } catch (Exception $e) {
            throw new Exception("Database update completed successfully but was unable to remove the install directory post completion");
        }
    }
    $updater = new WHMCS\Installer\Update\Updater();
    $updater->disableAutoUpdateMaintenanceMsg();
    $updateCount = (int) WHMCS\Config\Setting::getValue("AutoUpdateCountSuccess");
    $updateCount += 1;
    WHMCS\Config\Setting::setValue("AutoUpdateCountSuccess", $updateCount);
    $response = array("success" => true);
} catch (Exception $e) {
    $response = array("success" => false, "errorMessage" => $e->getMessage());
}
WHMCS\Config\Setting::setValue("UpdaterUpdateToken", "");
echo json_encode($response);

?>