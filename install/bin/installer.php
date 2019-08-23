<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(dirname(__DIR__)));
}
if (version_compare(PHP_VERSION, "5.4.0", "<")) {
    echo "The WHMCS command line installer requires PHP >= 5.4.0.";
    exit(1);
}
if (file_exists(ROOTDIR . DIRECTORY_SEPARATOR . "c3.php")) {
    include ROOTDIR . DIRECTORY_SEPARATOR . "c3.php";
}
if (!defined("INSTALLER_DIR")) {
    define("INSTALLER_DIR", dirname(__DIR__));
}
ini_set("eaccelerator.enable", 0);
ini_set("eaccelerator.optimizer", 0);
require_once ROOTDIR . "/vendor/autoload.php";
require_once ROOTDIR . "/includes/functions.php";
require_once ROOTDIR . "/includes/dbfunctions.php";
require_once INSTALLER_DIR . "/functions.php";
$errorLevel = basename(INSTALLER_DIR) == "install2" ? 32767 : 0;
$errMgmt = WHMCS\Utility\ErrorManagement::boot();
WHMCS\Utility\ErrorManagement::setErrorReportingLevel($errorLevel);
$exitCode = 0;
set_time_limit(0);
WHMCS\Utility\Bootstrap\Installer::boot();
$errMgmt->loadApplicationHandlers();
Log::debug("Installer bootstrapped");
$whmcsInstaller = new WHMCS\Installer\Installer(new WHMCS\Version\SemanticVersion(WHMCS\Installer\Installer::DEFAULT_VERSION), new WHMCS\Version\SemanticVersion(WHMCS\Application::FILES_VERSION));
$whmcsInstaller->setInstallerDirectory(INSTALLER_DIR);
$climate = new League\CLImate\CLImate();
$climate->description("Update WHMCS from the command line.");
$climate->arguments->add(array("help" => array("prefix" => "h", "longPrefix" => "help", "description" => "Print usage statement", "noValue" => true), "verbose" => array("prefix" => "v", "longPrefix" => "verbose", "description" => "Print installer log to standard out", "noValue" => true), "non-interactive" => array("prefix" => "n", "longPrefix" => "non-interactive", "description" => "Non interactive. Assume Yes for confirmations", "noValue" => true), "install" => array("prefix" => "i", "longPrefix" => "install", "description" => "Perform an installation", "noValue" => true), "upgrade" => array("prefix" => "u", "longPrefix" => "upgrade", "description" => "Perform an upgrade", "noValue" => true), "status" => array("prefix" => "s", "longPrefix" => "status", "description" => "Print status information about files and database", "noValue" => true)));
$cli = new WHMCS\Installer\Cli\Application($climate, $whmcsInstaller);
try {
    $cmd = "";
    if ($climate->arguments->defined("help")) {
        $cmd = "help";
        $climate->usage();
    } else {
        if ($climate->arguments->defined("status")) {
            $cmd = "status";
            $cli->header("WHMCS Status Information")->status()->footer();
        } else {
            if ($climate->arguments->defined("verbose")) {
                $cli->addVerbosity();
            }
            if ($climate->arguments->defined("install")) {
                $cmd = "install";
                $cli->header("Install WHMCS")->eula()->install()->footer();
            } else {
                if ($climate->arguments->defined("upgrade")) {
                    $cmd = "upgrade";
                    $cli->header("Upgrade WHMCS")->eula()->upgrade()->footer();
                } else {
                    throw new WHMCS\Exception\Installer\UnknownArgument("No action requested.");
                }
            }
        }
    }
} catch (WHMCS\Exception\Installer\UserBail $e) {
    $climate->comment($e->getMessage());
    $cli->footer();
} catch (WHMCS\Exception\Installer\UnknownArgument $e) {
    $cli->error($e->getMessage(), true);
    $exitCode = 1;
} catch (Exception $e) {
    if ($climate->arguments->defined("verbose")) {
        $cli->errorException($e);
    } else {
        $cli->error($e->getMessage(), false);
    }
    $exitCode = 1;
}
exit($exitCode);

?>