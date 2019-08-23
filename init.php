<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

ini_set("eaccelerator.enable", 0);
ini_set("eaccelerator.optimizer", 0);
$systemErrorReportingLevel = error_reporting();
if (function_exists("gracefulCoreRequiredFileInclude")) {
    exit("Detected attempt to include init.php for a second time. Unable to continue." . PHP_EOL);
}
function gracefulCoreRequiredFileInclude($path)
{
    $fullpath = ROOTDIR . $path;
    if (file_exists($fullpath)) {
        include_once $fullpath;
    } else {
        echo WHMCS\View\Helper::applicationError("Down for Maintenance", "One or more required files are missing. " . "If an install or upgrade is not currently in progress, " . "please contact the system administrator.");
        exit;
    }
}
if (defined("WHMCS_LICENSE_DOMAIN") || defined("WHMCS_LICENSE_IP") || defined("WHMCS_LICENSE_DIR")) {
    exit("Unable to initialise license validation. Please contact support." . PHP_EOL);
}
$installIp = "";
if (isset($_SERVER["SERVER_ADDR"])) {
    $installIp = $_SERVER["SERVER_ADDR"];
} else {
    if (isset($_SERVER["LOCAL_ADDR"])) {
        $installIp = $_SERVER["LOCAL_ADDR"];
    } else {
        if (function_exists("gethostname") && function_exists("gethostbyname")) {
            $installIp = gethostbyname(gethostname());
        }
    }
}
define("WHMCS_LICENSE_DOMAIN", isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "");
define("WHMCS_LICENSE_IP", $installIp);
define("WHMCS_LICENSE_DIR", realpath(dirname(__FILE__)));
if (!defined("ROOTDIR")) {
    define("ROOTDIR", realpath(dirname(__FILE__)));
}
if (file_exists(ROOTDIR . DIRECTORY_SEPARATOR . "c3.php")) {
    include ROOTDIR . DIRECTORY_SEPARATOR . "c3.php";
}
if (!defined("WHMCS")) {
    define("WHMCS", true);
}
gracefulCoreRequiredFileInclude("/vendor/autoload.php");
if (function_exists("stream_get_wrappers") && function_exists("stream_wrapper_unregister") && in_array("phar", stream_get_wrappers())) {
    stream_wrapper_unregister("phar");
}
$errMgmt = WHMCS\Utility\ErrorManagement::boot();
$errMgmt::disableIniDisplayErrors();
$errMgmt::setErrorReportingLevel($systemErrorReportingLevel);
gracefulCoreRequiredFileInclude("/includes/dbfunctions.php");
gracefulCoreRequiredFileInclude("/includes/functions.php");
if (defined("CLIENTAREA")) {
    gracefulCoreRequiredFileInclude("/includes/clientareafunctions.php");
}
if (defined("ADMINAREA") || defined("MOBILEEDITION")) {
    gracefulCoreRequiredFileInclude("/includes/adminfunctions.php");
}
try {
    $runtimeStorage = new WHMCS\Config\RuntimeStorage();
    $runtimeStorage->errorManagement = $errMgmt;
    WHMCS\Utility\Bootstrap\Application::boot($runtimeStorage);
    $errMgmt::setErrorReportingLevel($errMgmt::ERROR_LEVEL_ERRORS_VALUE);
    $errMgmt->loadApplicationHandlers()->loadDeferredHandlers();
    $whmcs = App::self();
    $currentErrorReportingLevel = error_reporting();
    if (DI::make("config")->error_reporting_level === $errMgmt::ERROR_LEVEL_INHERIT_VALUE && $currentErrorReportingLevel !== $systemErrorReportingLevel) {
        $errMgmt::setErrorReportingLevel($systemErrorReportingLevel);
    }
    WHMCS\Application\ApplicationServiceProvider::checkVersion();
    WHMCS\Security\Environment::setHttpProxyHeader(DI::make("config")->outbound_http_proxy);
    WHMCS\Utility\Bootstrap\Application::verifyInstallerIsAbsent();
    WHMCS\Utility\Bootstrap\Application::persistSession();
    if (!defined("WHMCSLIVECHAT")) {
        DI::make("lang");
        if (defined("CLIENTAREA")) {
            WHMCS\Language\ClientLanguage::getLanguages();
        }
    }
} catch (Exception $e) {
    Log::debug($e->getMessage(), array("trace" => $e->getTrace()));
    if ($e instanceof WHMCS\Exception\Application\Configuration\FileNotFound || $e instanceof WHMCS\Exception\Application\Configuration\LicenseKeyNotDefined) {
        echo WHMCS\View\Helper::applicationError("Welcome to WHMCS!", "Before you can begin using WHMCS you need to perform the installation procedure. " . "<a href=\"" . (file_exists("install/install.php") ? "" : "../") . "install/install.php\" style=\"color:#000;\">Click here to begin...</a>", $e);
        exit;
    }
    if ($e instanceof WHMCS\Exception\Application\InstallationVersionMisMatch) {
        if (WHMCS\Installer\Update\Updater::isAutoUpdateInProgress() && !WHMCS\Installer\Update\Updater::isAutoUpdateInProgressByCurrentAdminUser()) {
            $updater = new WHMCS\Installer\Update\Updater();
            $updaterMaintenanceMsg = $updater->getMaintenanceMessage();
            if (!empty($updaterMaintenanceMsg)) {
                echo WHMCS\View\Helper::applicationError("Down for Maintenance", $updaterMaintenanceMsg, $e);
            } else {
                echo WHMCS\View\Helper::applicationError("Down for Maintenance (Err 3)", "An upgrade is currently in progress... Please come back soon...", $e);
            }
            exit;
        }
        if (file_exists("../install/install.php")) {
            header("Location: ../install/install.php");
            exit;
        }
        echo WHMCS\View\Helper::applicationError("Down for Maintenance (Err 2)", "An upgrade is currently in progress... Please come back soon...", $e);
        exit;
    }
    if ($e instanceof WHMCS\Exception\Application\InstallerExists) {
        echo WHMCS\View\Helper::applicationError("Security Warning", "The install folder needs to be deleted for security reasons before using WHMCS.", $e);
        exit;
    }
    if ($e instanceof WHMCS\Exception\Application\Configuration\ParseError || $e instanceof WHMCS\Exception\Application\Configuration\CannotConnectToDatabase || $e instanceof WHMCS\Exception) {
        echo WHMCS\View\Helper::applicationError("Critical Error", $e->getMessage(), $e);
        exit;
    }
    echo WHMCS\View\Helper::applicationError("Critical Error", $e->getMessage(), $e);
    exit;
}
$whmcsAppConfig = $whmcs->getApplicationConfig();
$templates_compiledir = $whmcsAppConfig["templates_compiledir"];
$downloads_dir = $whmcsAppConfig["downloads_dir"];
$attachments_dir = $whmcsAppConfig["attachments_dir"];
$customadminpath = $whmcsAppConfig["customadminpath"];
if (function_exists("mb_internal_encoding")) {
    $characterSet = $whmcs->get_config("Charset") == "" ? "UTF-8" : $whmcs->get_config("Charset");
    mb_internal_encoding($characterSet);
}
if (function_exists("htmlspecialchars_array")) {
    exit("Detected attempt to include init.php for a second time. Unable to continue.");
}
function htmlspecialchars_array($arr)
{
    return App::self()->sanitize_input_vars($arr);
}
define("CLIENT_DATE_FORMAT", getClientDateFormat());
if (defined("ADMINAREA") && !defined("MOBILEEDITION")) {
    $currentDirectoryPath = dirname($whmcs->getPhpSelf());
    $currentDirectoryPathParts = explode("/", $currentDirectoryPath);
    $currentDir = array_pop($currentDirectoryPathParts);
    $appConfig = $whmcs->getApplicationConfig();
    $configuredAdminDir = $appConfig["customadminpath"];
    $adminDirErrorMsg = "";
    $docsUrl = "https://docs.whmcs.com/Customising_the_Admin_Directory";
    if ($configuredAdminDir == "admin" && $currentDir != $configuredAdminDir) {
        $adminDirErrorMsg = "You are attempting to access the admin area via a directory" . " that is not configured. Please either revert to the default admin" . " directory name, or see our documentation for" . " <a href=\"" . $docsUrl . "\" target=\"_blank\">Customising the Admin Directory</a>.";
    } else {
        if ($currentDir != $configuredAdminDir) {
            $adminDirErrorMsg = "You are attempting to access the admin area via a" . " directory that is different from the one configured. Please refer" . " to the <a href=\"" . $docsUrl . "\" target=\"_blank\">" . "Customising the Admin Directory</a>" . " documentation for instructions on how to update it.";
        } else {
            if ($configuredAdminDir != "admin" && is_dir(ROOTDIR . DIRECTORY_SEPARATOR . "admin")) {
                $adminDirErrorMsg = "You are attempting to access the admin area via a" . " custom directory, but we have detected the presence of a default" . " \"admin\" directory too. This could indicate files from a recent" . " update have been uploaded to the default admin path location" . " instead of the custom one, resulting in these files being out of" . " date. Please ensure your custom admin folder contains all the" . " latest files, and delete the default admin directory to continue.";
            }
        }
    }
    if ($adminDirErrorMsg) {
        throw new WHMCS\Exception\Fatal(WHMCS\View\Helper::applicationError("Critical Error", $adminDirErrorMsg));
    }
}
if (defined("ADMINAREA") && constant("ADMINAREA") && $_SERVER["SCRIPT_NAME"]) {
    $file = $_SERVER["SCRIPT_NAME"];
    if (substr($file, -10) != "/index.php" && (!defined("ROUTE_CONVERTED_LEGACY_ENDPOINT") || !constant("ROUTE_CONVERTED_LEGACY_ENDPOINT"))) {
        $request = WHMCS\Http\Message\ServerRequest::fromGlobals();
        $response = DI::make("Frontend\\Dispatcher")->dispatch($request);
    }
}
if (!$whmcs->check_template_cache_writeable()) {
    echo WHMCS\View\Helper::applicationError("Permissions Error", "The templates compiling directory '" . $whmcs->get_template_compiledir_name() . "'" . " must be writeable (CHMOD 777) before you can continue.<br>If the" . " path shown is incorrect, you can update it in the configuration.php file.");
    exit;
}
if (defined("CLIENTAREA") && $whmcs->isInMaintenanceMode() && !$_SESSION["adminid"]) {
    if ($CONFIG["MaintenanceModeURL"]) {
        header("Location: " . $CONFIG["MaintenanceModeURL"]);
        exit;
    }
    $maintenanceModeMessage = $whmcs->isUpdating() ? $CONFIG["UpdateMaintenanceMessage"] : $CONFIG["MaintenanceModeMessage"];
    echo WHMCS\View\Helper::applicationError("Down for Maintenance (Err 3)", $maintenanceModeMessage);
    exit;
}
$licensing = DI::make("license")->checkFile("a896faf2c31f2acd47b0eda0b3fd6070958f1161");
if (defined("CLIENTAREA") && isset($_SESSION["uid"]) && !isset($_SESSION["adminid"])) {
    $twofa = new WHMCS\TwoFactorAuthentication();
    $twofa->setClientID($_SESSION["uid"]);
    if ($twofa->isForced() && !$twofa->isEnabled() && $twofa->isActiveClients()) {
        $filename = $whmcs->get_filename();
        $originalUri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
        if (strpos($originalUri, "/account/security/two-factor/") === false && !($filename == "clientarea" && $whmcs->get_req_var("action") == "security") && $filename != "logout") {
            redir("action=security&activate2fa=1", "clientarea.php");
        }
    }
}
if (isset($_SESSION["currency"]) && is_array($_SESSION["currency"])) {
    $_SESSION["currency"] = $_SESSION["currency"]["id"];
}
if (!isset($_SESSION["uid"]) && isset($_REQUEST["currency"])) {
    $result = select_query("tblcurrencies", "id", array("id" => (int) $_REQUEST["currency"]));
    $data = mysql_fetch_array($result);
    if ($data["id"]) {
        $_SESSION["currency"] = $data["id"];
    }
}
if (defined("CLIENTAREA") && $whmcs->isSSLAvailable() && !$whmcs->in_ssl()) {
    $reqvars = $_REQUEST;
    if (array_key_exists("token", $reqvars)) {
        unset($reqvars["token"]);
    }
    $whmcs->redirectSystemURL($whmcs->getCurrentFilename(false), $reqvars);
}
load_hooks();

?>