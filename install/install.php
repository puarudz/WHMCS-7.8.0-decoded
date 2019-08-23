<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__));
}
if (file_exists(ROOTDIR . DIRECTORY_SEPARATOR . "c3.php")) {
    include ROOTDIR . DIRECTORY_SEPARATOR . "c3.php";
}
if (!defined("INSTALLER_DIR")) {
    define("INSTALLER_DIR", __DIR__);
}
ini_set("eaccelerator.enable", 0);
ini_set("eaccelerator.optimizer", 0);
require_once ROOTDIR . "/vendor/autoload.php";
require_once ROOTDIR . "/includes/functions.php";
require_once ROOTDIR . "/includes/dbfunctions.php";
require_once INSTALLER_DIR . "/functions.php";
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
Log::debug("Installer bootstrapped");
WHMCS\Security\Environment::setHttpProxyHeader(DI::make("config")->outbound_http_proxy);
$whmcsInstaller = new WHMCS\Installer\Installer(new WHMCS\Version\SemanticVersion(WHMCS\Installer\Installer::DEFAULT_VERSION), new WHMCS\Version\SemanticVersion(WHMCS\Application::FILES_VERSION));
$whmcsInstaller->setInstallerDirectory(INSTALLER_DIR);
$step = isset($_REQUEST["step"]) ? trim($_REQUEST["step"]) : "";
$type = isset($_REQUEST["type"]) ? trim($_REQUEST["type"]) : "";
$doUpgradeFromInstall = isset($_REQUEST["do-upgrade-from-install"]) ? (bool) (int) $_REQUEST["do-upgrade-from-install"] : false;
$licenseKey = isset($_REQUEST["licenseKey"]) ? trim($_REQUEST["licenseKey"]) : "";
$databaseHost = isset($_REQUEST["databaseHost"]) ? trim($_REQUEST["databaseHost"]) : "";
$databasePort = isset($_REQUEST["databasePort"]) ? trim($_REQUEST["databasePort"]) : "";
$databaseUsername = isset($_REQUEST["databaseUsername"]) ? trim($_REQUEST["databaseUsername"]) : "";
$databasePassword = isset($_REQUEST["databasePassword"]) ? trim($_REQUEST["databasePassword"]) : "";
$databaseName = isset($_REQUEST["databaseName"]) ? trim($_REQUEST["databaseName"]) : "";
$firstName = isset($_REQUEST["firstName"]) ? WHMCS\Input\Sanitize::encode(trim($_REQUEST["firstName"])) : "";
$lastName = isset($_REQUEST["lastName"]) ? WHMCS\Input\Sanitize::encode(trim($_REQUEST["lastName"])) : "";
$email = isset($_REQUEST["email"]) ? WHMCS\Input\Sanitize::encode(trim($_REQUEST["email"])) : "";
$username = isset($_REQUEST["username"]) ? WHMCS\Input\Sanitize::encode(trim($_REQUEST["username"])) : "";
$password = isset($_REQUEST["password"]) ? WHMCS\Input\Sanitize::encode(trim($_REQUEST["password"])) : "";
$confirmPassword = isset($_REQUEST["confirmPassword"]) ? WHMCS\Input\Sanitize::encode(trim($_REQUEST["confirmPassword"])) : "";
$validationError = "";
try {
    $dbErrorMessage = "";
    if ($whmcsInstaller->isInstalled()) {
        DI::make("db")->getSqlVersion();
    }
} catch (Exception $e) {
    $dbErrorMessage = $e->getMessage();
}
$systemRequirements = array(10 => array("Requirement" => "PHP Version", "CurrentValue" => PHP_VERSION, "RequiredValue" => "5.6.0", "PassingStatus" => version_compare(PHP_VERSION, "5.6.0", ">="), "Help" => "WHMCS requires a certain version of PHP. We always recommend running the latest available stable version."), 11 => array("Requirement" => "PHP Memory Limit", "CurrentValue" => WHMCS\Environment\Php::getIniSetting("memory_limit"), "RequiredValue" => "64M", "PassingStatus" => 64 * 1024 * 1024 <= WHMCS\Environment\Php::getPhpMemoryLimitInBytes(), "Help" => "The minimum PHP memory limit required to run WHMCS is 64M. We recommend 128M for the best experience. Please increase the limit and try again."), 50 => array("Requirement" => "CURL with SSL Support", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => extension_loaded("curl") && WHMCS\Environment\Php::functionEnabled("curl_init") && WHMCS\Environment\Php::functionEnabled("curl_exec"), "Help" => "CURL is required for external communication. Currently, it appears the CURL extension is either missing or disabled."), 60 => array("Requirement" => "JSON", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => extension_loaded("json") && WHMCS\Environment\Php::functionEnabled("json_encode"), "Help" => "JSON is required. Currently, it appears the JSON extension is either missing or disabled. As of PHP 5.2.0, the JSON extension is bundled and compiled into PHP by default."), 70 => array("Requirement" => "PDO", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => extension_loaded("pdo"), "Help" => "PDO is required for WHMCS database connectivity. Please load the PDO extension."), 80 => array("Requirement" => "PDO-MySQL", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => extension_loaded("pdo_mysql"), "Help" => "The PDO MySQL driver is required for WHMCS database connectivity. Please load the PDO_MYSQL extension."), 90 => array("Requirement" => "GD", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => extension_loaded("gd") && WHMCS\Environment\Php::functionEnabled("imagecreate"), "Help" => "GD Libraries for PHP are required for image processing within WHMCS. Proceeding without GD Libraries may not allow WHMCS to function properly."), 95 => array("Requirement" => "XML", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => extension_loaded("xml"), "Help" => "XML Library for PHP are required for API connections with many popular services. Proceeding without XML Libraries may not allow WHMCS to function properly."));
$systemRecommendations = array(10 => array("Recommendation" => "Windows Operating System Detected", "Condition" => "\\" == DIRECTORY_SEPARATOR, "Help" => "We validate WHMCS to run in Linux based environments running the Apache web server. " . "Other environments such as Windows based configurations may experience compatibility issues and are not supported."));
if ($whmcsInstaller->isInstalled()) {
    $systemRequirements[15] = array("Requirement" => "MySQL Connection", "RequiredValue" => "Available", "FailureValue" => "Unavailable", "PassingStatus" => $dbErrorMessage == "", "Help" => "There was an error while connecting to MySQL database: " . $dbErrorMessage . ". Please correct the error before proceeding.");
}
$configFilename = ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
$configFilePassingStatus = is_file($configFilename) && !is_link($configFilename) && is_writable($configFilename);
if (!$configFilePassingStatus) {
    @file_put_contents($configFilename, "<?php" . PHP_EOL . "// Auto-created by installer");
    $configFilePassingStatus = is_file($configFilename) && !is_link($configFilename) && is_writable($configFilename);
    if ($configFilePassingStatus && file_exists($configFilename . ".new")) {
        @unlink($configFilename . ".new");
    }
}
$fileWritePermissions = array(10 => array("Requirement" => "Configuration File", "Path" => "/configuration.php", "PassingStatus" => $configFilePassingStatus, "Help" => file_exists($configFilename) ? "The configuration.php file must be writeable." : "Unable to create config file. Please rename configuration.php.new to configuration.php" . " within the root directory of your WHMCS installation to continue."), 20 => array("Requirement" => "Attachments Directory", "Path" => "/attachments/", "PassingStatus" => is_writable(ROOTDIR . "/attachments/"), "Help" => is_dir(ROOTDIR . "/attachments/") ? "The attachments directory is not writeable." : "Could not find attachments directory. Please create it and try again."), 30 => array("Requirement" => "Downloads Directory", "Path" => "/downloads/", "PassingStatus" => is_writable(ROOTDIR . "/downloads/"), "Help" => is_dir(ROOTDIR . "/downloads/") ? "The downloads directory is not writeable." : "Could not find downloads directory. Please create it and try again."), 40 => array("Requirement" => "Templates Compile Directory", "Path" => "/templates_c/", "PassingStatus" => is_writable(ROOTDIR . "/templates_c/"), "Help" => is_dir(ROOTDIR . "/templates_c/") ? "The templates_c directory is not writeable." : "Could not find templates_c directory. Please create it and try again."));
echo "<!DOCTYPE html>\n<html>\n    <head>\n        <title>WHMCS Install/Upgrade Process</title>\n        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n        <link href=\"../assets/css/bootstrap.min.css\" rel=\"stylesheet\">\n        <link href=\"../assets/css/fontawesome-all.min.css\" rel=\"stylesheet\">\n        <link href=\"../assets/css/install.css\" rel=\"stylesheet\">\n        <script type=\"text/javascript\" src=\"../assets/js/jquery.min.js\"></script>\n        <script type=\"text/javascript\" src=\"../assets/js/bootstrap.min.js\"></script>\n\n        <script>\n            function hideLoading() {\n                jQuery(\"#submitButton\").removeAttr(\"disabled\");\n                jQuery(\".loading\").fadeOut();\n            }\n            function showLoading() {\n                jQuery(\"#submitButton\").attr(\"disabled\",\"disabled\");\n                jQuery(\".loading\").fadeIn();\n            }\n            function setUpgradeInProgress() {\n                jQuery(\"#btnConfirmBackup\").attr(\"disabled\", \"disabled\");\n                jQuery(\"#btnCloseUpgradeBackupModal\").attr(\"disabled\", \"disabled\");\n                jQuery(\"#btnConfirmBackup\").html('<i class=\"fas fa-spinner fa-spin\"></i> Upgrade In Progress... Please be patient...');\n                jQuery(\"#upgradeDurationMsg\").hide().removeClass('hidden').slideDown();\n            }\n        </script>\n\n    </head>\n\n    <body onunload=\"\">\n        <div class=\"wrapper\">\n            <div class=\"version\">V";
echo $whmcsInstaller->getLatestMajorMinorVersion();
echo "</div>\n            <div style=\"margin:30px;\">\n                <a href=\"https://www.whmcs.com/\" target=\"_blank\"><img src=\"//www.whmcs.com/images/logo.png\" alt=\"WHMCS - The Complete Client Management, Billing & Support Solution\" border=\"0\" /></a>\n            </div>\n            ";
if ($step == "4") {
    if (!$licenseKey) {
        $validationError = "A License Key is required. If you don't yet have one, please visit <a href=\"https://www.whmcs.com/order\">www.whmcs.com</a> to purchase one.";
    } else {
        if (!$databaseHost) {
            $validationError = "A Database Hostname is required.";
        } else {
            if (!$databaseUsername) {
                $validationError = "A Database Username is required.";
            } else {
                if (!$databasePassword) {
                    $validationError = "A Database Password is required.";
                } else {
                    if (!$databaseName) {
                        $validationError = "A Database Name is required.";
                    } else {
                        $tmpConfig = new WHMCS\Config\Application();
                        $tmpConfig->setDatabaseCharset("utf8")->setDatabaseHost($databaseHost)->setDatabaseName($databaseName)->setDatabaseUsername($databaseUsername)->setDatabasePassword($databasePassword);
                        if ($databasePort) {
                            $tmpConfig->setDatabasePort($databasePort);
                        }
                        try {
                            $db = new WHMCS\Database($tmpConfig);
                        } catch (Exception $e) {
                            $validationError = "Could not connect to database server: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
    if ($validationError) {
        $step = "3";
    }
}
if ($step == "5") {
    $adminUsernameValidationError = 0;
    try {
        (new WHMCS\User\Admin())->validateUsername($username);
    } catch (Exception $e) {
        $adminUsernameValidationError = $e->getMessage();
    }
    if (!$firstName) {
        $validationError = "First name is required";
    } else {
        if (!$email) {
            $validationError = "Email address is required";
        } else {
            if (!$adminUsernameValidationError) {
                $validationError = $adminUsernameValidationError;
            } else {
                if (!$password) {
                    $validationError = "Password is required";
                } else {
                    if (strlen(WHMCS\Input\Sanitize::decode($password)) < 5) {
                        $validationError = "Password must be at least 5 characters long";
                    } else {
                        if (!$confirmPassword) {
                            $validationError = "You must confirm your password";
                        } else {
                            if ($password != $confirmPassword) {
                                $validationError = "The passwords you entered did not match";
                            }
                        }
                    }
                }
            }
        }
    }
    if ($validationError) {
        $step = "4";
    }
}
if ($step == "") {
    echo "<h1>End User License Agreement</h1>";
    if (isset($_REQUEST["disagree"]) && $_REQUEST["disagree"]) {
        echo "<div class=\"alert alert-danger text-center\" role=\"alert\">\n                        You cannot continue with the installation unless you agree to the License Agreement\n                    </div>";
    }
    echo "                <p>Please review the license terms before installing/upgrading WHMCS.  By installing, copying, or otherwise using the software, you are agreeing to be bound by the terms of the EULA.</p>\n                <p align=\"center\">\n                        <textarea class=\"form-control\" style=\"font-family: Tahoma, sans-serif; font-size: 12px; color: #666666;\" rows=\"25\" readonly>\n                            ";
    $eulaText = (new WHMCS\Utility\Eula())->getEulaText();
    if ($eulaText) {
        echo $eulaText;
        echo "                        </textarea>\n                </p>\n\n                <p align=\"center\">\n                    <a href=\"install.php?step=2\" class=\"btn btn-success btn-lg\" id=\"btnEulaAgree\">I AGREE</a>\n                    <a href=\"install.php?disagree=1\" class=\"btn btn-default btn-lg\" id=\"btnEulaDisagree\">I DISAGREE</a>\n\n            ";
    } else {
        echo "EULA.txt is missing. Cannot continue.</textarea>";
        exit;
    }
} else {
    if ($step == "2") {
        echo "\n";
        $isUpgrade = false;
        $isInstallation = false;
        if ($whmcsInstaller->isInstalled()) {
            if ($doUpgradeFromInstall && $step == 2) {
                $newCcHash = $_REQUEST["upgrade-cc-hash"];
                $output = getConfigurationFileContentWithNewCcHash($newCcHash);
                $fp = fopen($configFilename, "w");
                fwrite($fp, $output);
                fclose($fp);
            }
            Log::info("Previous install detected");
            $installedVersion = $whmcsInstaller->getVersion()->getCasual();
            echo "<h1>Upgrade Your Installation</h1>";
            if ($whmcsInstaller->isUpToDate()) {
                Log::debug("Installation is already up to date");
                echo "<p>We have detected that you are already running WHMCS Version " . $installedVersion . ".</p>" . "<p>This installer script can only upgrade as far as " . $installedVersion . " so there is no update to perform.</p>" . "<br /><p><small><em>Looking to perform a new installation?</em> To do so, you must first drop all existing tables from your WHMCS database, then try running the installer again. (Warning: You will lose all existing data if you do this.)</small></p>";
            } else {
                if ($whmcsInstaller->getInstalledVersionNumeric() < 320) {
                    Log::debug("Installation is too old to upgrade");
                    echo "<p>We have detected that you are currently running WHMCS Version " . $installedVersion . ".</p>" . "<p>This version of WHMCS is too old to be upgraded automatically.</p>" . "<p>To update, we recommend purchasing our professional upgrade service @ <a href=\"https://www.whmcs.com/services/\">www.whmcs.com/services/</a> to have it manually updated.</p>";
                } else {
                    Log::debug(sprintf("An upgrade from %s to %s will be attempted.", $whmcsInstaller->getVersion()->getCanonical(), $whmcsInstaller->getLatestVersion()->getCanonical()));
                    echo "<p>We have detected that you are currently running WHMCS Version " . $installedVersion . ".</p>" . "<p>This update process will upgrade your installation to " . $whmcsInstaller->getLatestVersion()->getCasual() . ".</p>";
                    $isUpgrade = true;
                }
            }
        } else {
            Log::info("Previous install not detected; a fresh install will be attempted");
            echo "<h1>New Installation</h1>" . "<p>No existing installation was detected.</p>" . "<p class=\"text-muted\">Intending to perform an upgrade? Do not continue, and <a href=\"https://docs.whmcs.com/Upgrading_New_Installation_Prompt\" target=\"_blank\">click here for help</a>.</p>";
            $isInstallation = true;
        }
        if ($isInstallation || $isUpgrade) {
            if (extension_loaded("PDO") && extension_loaded("pdo_mysql") && $dbErrorMessage == "") {
                $systemRequirements[20] = array("Requirement" => "MySQL Version", "CurrentValue" => $isUpgrade ? DI::make("db")->getSqlVersion() : "Version Unavailable", "RequiredValue" => "5.2.3", "PassingStatus" => true, "Help" => "MySQL is the database engine used by WHMCS. Currently it appears the MySQL extension is either missing or disabled.");
                if ($isUpgrade) {
                    $systemRequirements[30] = array("Requirement" => "MySQL Strict Mode", "RequiredValue" => "Off", "FailureValue" => "On", "PassingStatus" => !DI::make("db")->isSqlStrictMode(), "Help" => "MySQL Strict Mode must be disabled.");
                }
            }
            $meetsRequirements = true;
            $systemRequirementsOutput = "";
            $filePermissionRequirementsOutput = "";
            $systemRequirementsFailures = "";
            $filePermissionRequirementsFailures = "";
            ksort($systemRequirements);
            foreach ($systemRequirements as $i => $values) {
                $requirementOutput = "<tr>\n            <td>" . $values["Requirement"] . "</td>\n            <td>" . (isset($values["CurrentValue"]) ? $values["CurrentValue"] : ($values["PassingStatus"] ? $values["RequiredValue"] : $values["FailureValue"])) . "</td>\n            <td>" . $values["RequiredValue"] . "</td>\n            <td>" . ($values["PassingStatus"] ? "<i class=\"far fa-check-square icon-success\"></i>" : "<button type=\"button\" class=\"btn btn-info btn-xs help-icon\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"" . $values["Help"] . "\"><i class=\"fas fa-question\"></i></button>") . "</td>\n        </tr>";
                $systemRequirementsOutput .= $requirementOutput;
                if (!$values["PassingStatus"]) {
                    $systemRequirementsFailures .= $requirementOutput;
                    $meetsRequirements = false;
                }
            }
            $systemRecommendationsOutput = "";
            if ($isInstallation) {
                ksort($fileWritePermissions);
                foreach ($fileWritePermissions as $i => $values) {
                    $filePermissionOutput = "<tr>\n                <td>" . $values["Requirement"] . "</td>\n                <td>" . $values["Path"] . "</td>\n                <td>" . ($values["PassingStatus"] ? "<i class=\"far fa-check-square icon-success\"></i>" : "<button type=\"button\" class=\"btn btn-info btn-xs help-icon\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"" . $values["Help"] . "\"><i class=\"fas fa-question\"></i></button>") . "</td>\n            </tr>";
                    $filePermissionRequirementsOutput .= $filePermissionOutput;
                    if (!$values["PassingStatus"]) {
                        $filePermissionRequirementsFailures .= $filePermissionOutput;
                        $meetsRequirements = false;
                    }
                }
                ksort($systemRecommendations);
                foreach ($systemRecommendations as $i => $systemRecommendation) {
                    if ($systemRecommendation["Condition"]) {
                        $systemRecommendationsOutput .= "<tr><td>" . $systemRecommendation["Recommendation"] . "</td>" . "<td><button type=\"button\" class=\"btn btn-info btn-xs help-icon\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"" . $systemRecommendation["Help"] . "\"><i class=\"fas fa-question\"></i></button></td></tr>";
                    }
                }
            }
            if ($meetsRequirements) {
                if ($systemRecommendationsOutput) {
                    $systemRecommendationsOutput = "<table class=\"table table-striped requirements\">\n            <tr>\n                <th>Information</th>\n                <th></th>\n            </tr>" . $systemRecommendationsOutput . "\n            </table>\n            <script>\njQuery(function () {\n    jQuery('[data-toggle=\"tooltip\"]').tooltip()\n})\n</script>";
                }
                echo "<br /><div class=\"alert alert-success text-center\" role=\"alert\"  id=\"requirementsSummary\"><strong><i class=\"fas fa-check-circle\"></i> System Requirements Check Passed</strong><div style=\"font-size:0.9em;padding:6px;\">Your system meets the requirements necessary to run this version of WHMCS.</div><a href=\"#\" id=\"btnDetailedCheckResults\" class=\"btn btn-default btn-sm\" data-toggle=\"modal\" data-target=\"#requirementsFullResults\">View detailed check results</a></div>\n" . $systemRecommendationsOutput . "\n<div class=\"modal fade\" id=\"requirementsFullResults\">\n  <div class=\"modal-dialog\">\n    <div class=\"modal-content\">\n      <div class=\"modal-header\">\n        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>\n        <h4 class=\"modal-title\">System Requirements Check Results</h4>\n      </div>\n      <div class=\"modal-body\">\n\n        <table class=\"table table-striped requirements\">\n            <tr>\n                <th>Requirement</th>\n                <th>Your Value</th>\n                <th>Required Value</th>\n                <th></th>\n            </tr>" . $systemRequirementsOutput . "\n        </table>";
                if ($filePermissionRequirementsOutput) {
                    echo "<table class=\"table table-striped requirements\">\n            <tr>\n                <th>Read/Write Permissions</th>\n                <th>File/Directory Path</th>\n                <th></th>\n            </tr>" . $filePermissionRequirementsOutput . "\n        </table>";
                }
                echo "\n      </div>\n    </div>\n  </div>\n</div>\n\n";
            } else {
                echo "<br /><div class=\"alert alert-danger text-center\" role=\"alert\" id=\"requirementsSummary\"><strong><i class=\"fas fa-exclamation-triangle\"></i> System Requirements Check Failed</strong><div style=\"font-size:0.9em;padding:6px;\">Your system <strong>does not</strong> meet the requirements necessary to run this version of WHMCS.<br />You must resolve the issues below before you can continue with installation.</div></div>\n<script>\njQuery(function () {\n    jQuery('[data-toggle=\"tooltip\"]').tooltip()\n})\n</script>";
                if ($systemRequirementsFailures) {
                    echo "<table class=\"table table-striped requirements\">\n    <tr>\n        <th>Requirement</th>\n        <th>Your Value</th>\n        <th>Required Value</th>\n        <th>Help</th>\n    </tr>\n    " . $systemRequirementsFailures . "\n</table>";
                }
                if ($filePermissionRequirementsFailures) {
                    echo "<table class=\"table table-striped requirements\">\n    <tr>\n        <th>Read/Write Permissions</th>\n        <th>File/Directory Path</th>\n        <th>Help</th>\n    </tr>\n    " . $filePermissionRequirementsFailures . "\n</table>";
                }
                if ($systemRecommendationsOutput) {
                    echo "<table class=\"table table-striped requirements\">\n            <tr>\n                <th>Information</th>\n                <th></th>\n            </tr>" . $systemRecommendationsOutput . "\n            </table>";
                }
                echo "<p>Please address the issues listed above and then click the button below to recheck the requirements and continue.</p>" . "<p align=\"center\"><a href=\"?step=2\" id=\"btnRecheckRequirements\" class=\"btn btn-success\">Recheck Requirements</a></p>";
                $isInstallation = false;
                $isUpgrade = false;
            }
        }
        if ($isUpgrade) {
            echo "<h2>Ready to Begin?</h2>" . "<p>The upgrade process can take some time depending upon the size of your database. Please do not stop or navigate away from the page once it has begun.</p>" . "<p>If a problem occurs at any point during the upgrade, the process will be halted. In that scenario, we recommend that you restore your backup and contact our support team for assistance.</p>";
        }
        if ($isInstallation) {
            echo "<br /><p align=\"center\"><a href=\"?step=3\" id=\"btnBeginInstallation\" class=\"btn btn-danger btn-lg\">Begin Installation</a></p>";
        }
        if ($isUpgrade) {
            echo "<br />";
            if (is_writable(__DIR__ . DIRECTORY_SEPARATOR . "log") && (is_writable(__DIR__ . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "installer.log") || !file_exists(__DIR__ . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "installer.log"))) {
                echo "<div class=\"alert alert-info text-center\" role=\"alert\">\n        A log of events will be written to <em>/install/log/installer.log</em>. This log will be needed in the event of a failure.\n    </div>";
            } else {
                echo "<div class=\"alert alert-warning text-center\" role=\"alert\">\n        The file /install/log/installer.log is not writeable or cannot be created. If you continue the PHP Error Log as defined in your <em>php.ini</em> configuration will be used instead.\n\n    </div>";
            }
            echo "<p align=\"center\"><button type=\"button\" id=\"btnUpgradeContinue\" class=\"btn btn-danger btn-lg\" data-toggle=\"modal\" data-target=\"#upgradeBackup\" data-backdrop=\"static\" data-keyboard=\"false\">Continue</button></p>";
        }
        echo "<div class=\"modal fade\" id=\"upgradeBackup\">\n  <div class=\"modal-dialog\">\n    <div class=\"modal-content\">\n      <div class=\"modal-header\">\n        <button type=\"button\" class=\"close\" id=\"btnCloseUpgradeBackupModal\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>\n        <h4 class=\"modal-title\"><i class=\"fas fa-exclamation-triangle\"></i><br />Backup Confirmation</h4>\n      </div>\n      <div class=\"modal-body\">\n\n<div class=\"alert alert-danger\">\n<p>Before proceeding, please ensure you have a recent database backup that you are able to restore should the upgrade fail to complete successfully for any reason.</p>\n</div>\n<p>In the event of a failed or interrupted upgrade, a backup is<br />essential to being able to get back online quickly.</p>\n<p>If you don't have a backup, please generate one now.</p>\n\n      </div>\n      <div class=\"modal-footer\">\n        <form method=\"post\" action=\"install.php?step=upgrade\" onsubmit=\"setUpgradeInProgress()\">\n            <input type=\"hidden\" name=\"confirmBackup\" value=\"1\" />\n            <button type=\"submit\" id=\"btnConfirmBackup\" class=\"btn btn-info btn-lg\">\n                I have a backup, start the upgrade\n            </button>\n        </form>\n        <div class=\"upgrade-duration-msg text-muted hidden\" id=\"upgradeDurationMsg\">\n            Depending upon the size of your database, upgrades can take several minutes to complete.\n        </div>\n      </div>\n    </div>\n  </div>\n</div>";
    } else {
        if ($step == "3") {
            if ($validationError) {
                echo "<div class=\"alert alert-danger text-center\" role=\"alert\">\n                        " . $validationError . "\n                    </div>";
            }
            echo "\n                <form method=\"post\" action=\"install.php?step=4\" onsubmit=\"showLoading()\">\n                    <h1>License Key</h1>\n\n                    <p>You can find your license key in our <a href=\"https://www.whmcs.com/members/clientarea.php\" target=\"_blank\">Members Area</a> or alternatively if you obtained a license from a reseller, they should have already provided a license key to you.</p>\n\n                    <table class=\"table-padded\">\n                        <tr>\n                            <td width=\"200\">\n                                <label for=\"licenseKey\">License Key:</label>\n                            </td>\n                            <td width=\"350\">\n                                <input type=\"text\" name=\"licenseKey\" id=\"licenseKey\" value=\"";
            echo htmlspecialchars($licenseKey);
            echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                    </table>\n                    <br>\n\n                    <h1>Database Connection Details</h1>\n                    <p>WHMCS requires a MySQL database. If you have not already created one, please do so now.</p>\n\n                    <table class=\"table-padded\">\n                        <tr>\n                            <td width=\"200\">\n                                <label for=\"databaseHost\">Database Host:</label>\n                            </td>\n                            <td width=\"200\">\n                                <input type=\"text\" name=\"databaseHost\" id=\"databaseHost\" size=\"20\" value=\"";
            echo $databaseHost ? htmlspecialchars($databaseHost) : "localhost";
            echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"databasePort\">Database Port:</label>\n                            </td>\n                            <td>\n                                <input type=\"text\" name=\"databasePort\" id=\"databasePort\" size=\"15\" value=\"";
            echo $databasePort ? htmlspecialchars($databasePort) : "";
            echo "\" class=\"form-control\" placeholder=\"3306\">\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"databaseUsername\">Database Username:</label>\n                            </td>\n                            <td>\n                                <input type=\"text\" name=\"databaseUsername\" id=\"databaseUsername\" size=\"20\" value=\"";
            echo htmlspecialchars($databaseUsername);
            echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"databasePassword\">Database Password:</label>\n                            </td>\n                            <td>\n                                <input type=\"password\" name=\"databasePassword\" id=\"databasePassword\" size=\"20\" value=\"";
            echo htmlspecialchars($databasePassword);
            echo "\" class=\"form-control\" autocomplete=\"off\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"databaseName\">Database Name:</label>\n                            </td>\n                            <td>\n                                <input type=\"text\" name=\"databaseName\" id=\"databaseName\" size=\"20\" value=\"";
            echo htmlspecialchars($databaseName);
            echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                    </table>\n\n                    <br />\n\n                    <p align=\"center\">\n                        <input type=\"submit\" value=\"Continue &raquo;\" class=\"btn btn-lg btn-primary\" id=\"submitButton\" />\n                    </p>\n\n                    <div class=\"loading\">Initialising Database... Please Wait...<br>\n                        <img src=\"../assets/img/loading.gif\">\n                    </div>\n                </form>\n                <script>\n                    jQuery(document).ready(function(){\n                        hideLoading();\n                    });\n                </script>\n            ";
        } else {
            if ($step == "4") {
                $goToPreviousStep = "<script>jQuery(\"#previousStep\").click(\n                    function(e){\n                        e.preventDefault();\n                        window.history.go(-1)\n                    });</script>";
                if ($licenseKey) {
                    $length = 64;
                    $seeds = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    $encryptionHash = NULL;
                    $seeds_count = strlen($seeds) - 1;
                    for ($i = 0; $i < $length; $i++) {
                        $encryptionHash .= $seeds[rand(0, $seeds_count)];
                    }
                    $output = "<?php\n\$license = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($licenseKey) . "';\n\$db_host = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($databaseHost) . "';\n\$db_port = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($databasePort) . "';\n\$db_username = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($databaseUsername) . "';\n\$db_password = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($databasePassword) . "';\n\$db_name = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($databaseName) . "';\n\$cc_encryption_hash = '" . WHMCS\Input\Sanitize::escapeSingleQuotedString($encryptionHash) . "';\n\$templates_compiledir = 'templates_c';\n\$mysql_charset = 'utf8';";
                    $configurationFile = ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
                    $fp = fopen($configurationFile, "w");
                    if (fwrite($fp, $output) !== false) {
                        fclose($fp);
                        Log::info("New configuration file has been written");
                        if (function_exists("opcache_invalidate")) {
                            opcache_invalidate($configurationFile);
                        }
                        $previousDatabaseFound = false;
                        $databaseExists = false;
                        $databaseConnectFailed = false;
                        $strictSqlMode = false;
                        try {
                            $whmcsInstaller->checkIfInstalled(true);
                            if ($whmcsInstaller->isInstalled()) {
                                $previousDatabaseFound = true;
                                $databaseExists = true;
                            } else {
                                if ($whmcsInstaller->getDatabase()) {
                                    $databaseExists = true;
                                } else {
                                    $databaseConnectFailed = true;
                                }
                            }
                            $strictSqlMode = DI::make("db")->isSqlStrictMode();
                        } catch (Exception $e) {
                            $databaseConnectFailed = true;
                        }
                        if ($databaseConnectFailed) {
                            Log::error("Failed to connect to database '" . $databaseName . "'");
                            echo "                        <h1>New Installation</h1>\n                        <div class=\"alert alert-danger text-center\" role=\"alert\">\n                            <strong>\n                                <i class=\"fas fa-exclamation-triangle\"></i>\n                                Oops! There's a problem\n                            </strong>\n                        </div>\n                        <p><strong>Could not connect to the database</strong></p>\n                        <p>Please check the database connection details you provided and ensure the mysql user has access to the given database name.</p>\n                        <br />\n                        <p><a id=\"previousStep\" href=\"#\" class=\"btn btn-default\">&laquo; Go back and try again</a></p>\n                        ";
                            echo $goToPreviousStep;
                            exit;
                        }
                        if ($strictSqlMode) {
                            Log::error("SQL strict mode detected");
                            echo "                        <h1>New Installation</h1>\n                        <div class=\"alert alert-danger text-center\" role=\"alert\">\n                            <strong>\n                                <i class=\"fas fa-exclamation-triangle\"></i>\n                                Oops! There's a problem\n                            </strong>\n                        </div>\n                        <p><strong>Strict SQL Mode Detected</strong></p>\n                        <p>Please disable strict SQL mode before continuing.</p>\n                        <p>See <a href=\"https://docs.whmcs.com/Database_Setup#Strict_SQL_Mode\" target=\"_blank\">our database setup documentation</a> for more information.</p>\n                        <br />\n                        <p><a id=\"previousStep\" href=\"#\" class=\"btn btn-default\">&laquo; Go back and try again</a></p>\n                        ";
                            echo $goToPreviousStep;
                            exit;
                        }
                        if ($databaseExists && $previousDatabaseFound) {
                            Log::error("Previous WHMCS database found after configuration file was created");
                            echo "                        <h1>New Installation</h1>\n                        <div class=\"alert alert-danger text-center\" role=\"alert\">\n                            <strong>\n                                <i class=\"fas fa-exclamation-triangle\"></i>\n                                Warning! Existing Installation Detected\n                            </strong>\n                        </div>\n                        <p><strong>Existing WHMCS Database Found</strong></p>\n                        <p>The database details provided are for a pre-existing WHMCS database.</p>\n                        <p>New installations require a database that does not contain an existing WHMCS installation.</p>\n                        <p>For a new installation: Please either create a new database or drop the existing WHMCS tables and data from the database provided, and then try again.</p>\n                        <p>To upgrade the existing database: Please enter your Credit Card Encryption Hash below to continue with an upgrade.</p>\n                        <form action=\"install.php\" method=\"post\">\n                            <input type=\"hidden\" name=\"step\" value=\"2\" />\n                            <input type=\"hidden\" name=\"do-upgrade-from-install\" value=\"1\" />\n                            <p>\n                                <label for=\"upgradeCcHash\">Credit Card Encryption Hash:</label><br>\n                                <input type=\"password\" id=\"upgradeCcHash\" name=\"upgrade-cc-hash\" class=\"form-control\" required=\"required\" />\n                            </p>\n                            <br />\n                            <p><a id=\"previousStep\" href=\"#\" class=\"btn btn-default\">&laquo; Go back and try again</a> <button id=\"doUpgrade\" type=\"submit\" class=\"btn btn-danger pull-right\">Continue with Upgrade</button></p>\n                        </form>\n                        ";
                            echo $goToPreviousStep;
                            exit;
                        }
                        if ($databaseExists && !$previousDatabaseFound) {
                            Log::info("Applying base SQL schema");
                            $whmcsInstaller->seedDatabase();
                        }
                    } else {
                        echo "                        <h1>New Installation</h1>\n                        <div class=\"alert alert-danger text-center\" role=\"alert\">\n                            <strong>\n                                <i class=\"fas fa-exclamation-triangle\"></i>\n                                Oops! There's a problem\n                            </strong>\n                        </div>\n                        <p><strong>Could not write configuration file</strong></p>\n                        <p>Please make sure that /configuration.php file can be written to.</p>\n                        <br />\n                        <p><a id=\"previousStep\" href=\"#\" class=\"btn btn-default\">&laquo; Go back and try again</a></p>\n                        ";
                        echo $goToPreviousStep;
                        exit;
                    }
                }
                if ($validationError) {
                    echo "<div class=\"alert alert-danger text-center\" role=\"alert\">\n                        " . $validationError . "\n                    </div>";
                }
                echo "\n                <h1>Setup Administrator Account</h1>\n\n                <form method=\"post\" action=\"install.php?step=5\" onsubmit=\"showLoading()\">\n                    <p>You now need to setup your administrator account.</p>\n\n                    <table class=\"table-padded\">\n                        <tr>\n                            <td width=\"200\">\n                                <label for=\"firstName\">First Name:</label>\n                            </td>\n                            <td width=\"350\">\n                                <input type=\"text\" name=\"firstName\" id=\"firstName\" value=\"";
                echo $firstName;
                echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"lastName\">Last Name:</label>\n                            </td>\n                            <td>\n                                <input type=\"text\" name=\"lastName\" id=\"lastName\" value=\"";
                echo $lastName;
                echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"email\">Email:</label>\n                            </td>\n                            <td>\n                                <input type=\"email\" name=\"email\" id=\"email\" value=\"";
                echo $email;
                echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"username\">Username:</label>\n                            </td>\n                            <td>\n                                <input type=\"text\" name=\"username\" id=\"username\" autocomplete=\"off\" value=\"";
                echo $username;
                echo "\" class=\"form-control\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"password\">Password:</label>\n                            </td>\n                            <td>\n                                <input type=\"password\" name=\"password\" id=\"password\" value=\"";
                echo $password;
                echo "\" class=\"form-control\" autocomplete=\"off\" required>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td>\n                                <label for=\"confirmPassword\">Confirm Password:</label>\n                            </td>\n                            <td>\n                                <input type=\"password\" name=\"confirmPassword\" id=\"confirmPassword\" value=\"";
                echo $confirmPassword;
                echo "\" class=\"form-control\" autocomplete=\"off\" required>\n                            </td>\n                        </tr>\n                    </table>\n\n                    <br />\n\n                    <p align=\"center\">\n                        <input type=\"submit\" value=\"Complete Setup &raquo;\" class=\"btn btn-primary btn-lg\" id=\"submitButton\" />\n                    </p>\n\n                    <div class=\"loading\">Setting Up System for First Use... Please Wait...<br>\n                        <img src=\"../assets/img/loading.gif\">\n                    </div>\n                </form>\n            ";
            } else {
                if ($step == "5") {
                    include ROOTDIR . "/configuration.php";
                    DI::make("db");
                    Log::info("Creating initial admin account");
                    echo "<h1>New Installation</h1>";
                    $errorMsg = "";
                    try {
                        $whmcsInstaller->createInitialAdminUser($_REQUEST["username"], $_REQUEST["firstName"], $_REQUEST["lastName"], $password, $_REQUEST["email"]);
                        $whmcsInstaller->performNonSeedIncrementalChange();
                        $whmcsInstaller->setReleaseTierPin();
                        $admin = WHMCS\User\Admin::query()->orderBy("id")->first();
                        (new WHMCS\Utility\Eula())->markAsAccepted($admin);
                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
                    }
                    if ($errorMsg) {
                        Log::error("Installation process terminated due to error.");
                        echo "\n                    <div class=\"alert alert-danger text-center\" role=\"alert\">\n                        <strong>\n                            <i class=\"fas fa-exclamation-triangle\"></i>\n                            Installation Failed\n                        </strong>\n                    </div>\n\n                    <p>A problem was encountered during the installation process.</p>\n\n                    <p>The error message returned by the installer was as follows:</p>\n\n                    <div class=\"well\">\n                        ";
                        echo $errorMsg;
                        echo "                    </div>\n\n                    <h2>How do I get help?</h2>\n\n                    <p>We want to make sure you can start using our product as soon as possible.</p>\n                    <p>Please <a href=\"https://www.whmcs.com/support/\" target=\"_blank\">open a ticket with our support team</a> including a copy of your installation log file from <em>/install/log/</em>. This will help them diagnose what caused the failure, and what needs to be done before attempting the installation process again.</p>\n\n                    ";
                    } else {
                        Log::info("Installation process completed.");
                        echo "\n                    <div class=\"alert alert-success text-center\" role=\"alert\">\n                        <strong>\n                            <i class=\"fas fa-check-circle\"></i>\n                            Installation Completed Successfully!\n                        </strong>\n                    </div>\n\n                    <h2>Next Steps</h2>\n\n                    <p><strong>1. Delete the Install Folder</strong></p>\n                    <p>You should now delete the <em>/install/</em> directory from your web server.</p>\n\n                    <p><strong>2. Secure the Writable Directories</strong></p>\n                    <p>We recommend moving all writeable directories to a non-public directory above your web root to prevent web based access. Details on how to do this, and a number of other security hardening tips, can be found in our documentation @ <a href=\"https://docs.whmcs.com/Further_Security_Steps\" target=\"_blank\">Further Security Steps</a>.</p>\n\n                    <p><strong>3. Setup the Daily Cron Job</strong></p>\n                    <p>You should setup a cron job in your control panel to run using the following command every 5 minutes, or as frequently as your web hosting provider allows:<br /><br />\n                    <input type=\"text\" value=\"";
                        echo WHMCS\Environment\Php::getPreferredCliBinary();
                        echo " -q ";
                        echo ROOTDIR;
                        echo "/crons/cron.php\" class=\"form-control\" readonly>\n                    </p>\n\n                    <p><strong>4. Configure WHMCS</strong></p>\n                    <p>Now it's time to configure your WHMCS installation.</p>\n\n                    <div class=\"alert alert-info text-center\" role=\"alert\">\n                        We have lots of <strong>helpful resources & guides</strong> available to assist you in setting up & using your new WHMCS system in our comprehensive online documentation located @ <a href=\"https://docs.whmcs.com/\" target=\"_blank\">https://docs.whmcs.com/</a> (you can access the docs at any time by going to Help > Documentation or using the handy Help shortcuts available from most setup pages within the admin area).\n                    </div>\n\n                    <br>\n\n                    <p align=\"center\">\n                        <a href=\"../admin/\" id=\"btnGoToAdminArea\" class=\"btn btn-default\">Go to the Admin Area Now &raquo;</a>\n                    </p>\n\n                    <br>\n                    ";
                    }
                    echo "<h2>Thank you for choosing WHMCS!</h2>";
                } else {
                    if ($step == "upgrade") {
                        if (!isset($_REQUEST["confirmBackup"])) {
                            echo "<h1>Did you backup?</h1><p>You must confirm you have backed up your database before upgrading. Please go back and try again.";
                        } else {
                            Log::info("Applying incremental updates to existing installation");
                            echo "<h1>Upgrade Your Installation</h1>";
                            $errorMsg = "";
                            try {
                                $whmcsInstaller->runUpgrades();
                                $whmcsInstaller->setReleaseTierPin();
                                $admin = WHMCS\User\Admin::query()->orderBy("id")->first();
                                (new WHMCS\Utility\Eula())->markAsAccepted($admin);
                            } catch (Exception $e) {
                                $errorMsg = $e->getMessage();
                            }
                            try {
                                $whmcsInstaller->clearCompiledTemplates();
                            } catch (Exception $e) {
                                logActivity("Error cleaning template cache during upgrade: " . $e->getMessage());
                            }
                            if ($errorMsg) {
                                Log::error("Upgrade process terminated due to error.");
                                echo "\n                        <div class=\"alert alert-danger text-center\" role=\"alert\">\n                            <strong>\n                                <i class=\"fas fa-exclamation-triangle\"></i>\n                                Upgrade Failed\n                            </strong>\n                        </div>\n\n                        <p>A problem was encountered while attempting to apply the database schema updates.</p>\n\n                        <p>The error message returned by the update process was as follows:</p>\n\n                        <div class=\"well\">\n                            ";
                                echo $errorMsg;
                                echo "                        </div>\n\n                        <h2>How do I get help?</h2>\n\n                        <p>First, we recommend you restore the backup you took before you began the upgrade process to get your installation back online as quickly as possible.</p>\n                        <p>Then <a href=\"https://www.whmcs.com/support/\" target=\"_blank\">open a ticket with our support team</a> including a copy of your upgrade log file from <em>/install/log/</em>. This will help them diagnose what caused the failure, and what needs to be done before attempting the upgrade process again.</p>\n\n                        ";
                            } else {
                                Log::info("Upgrade process completed.");
                                echo "\n                        <div class=\"alert alert-success text-center\" role=\"alert\">\n                            <strong>\n                                <i class=\"fas fa-check-circle\"></i>\n                                Upgrade Completed Successfully!\n                            </strong>\n                        </div>\n\n                        <p>You are now running WHMCS Version ";
                                echo $whmcsInstaller->getLatestVersion()->getCasual();
                                echo ".</p>\n                        <p>We strongly recommend reading the <a href=\"https://docs.whmcs.com/Release_Notes\" target=\"_blank\">Release Notes</a> for this version in full to ensure you are aware of any changes requiring your attention.</p>\n                        <p>You should now delete the <em>/install/</em> folder from your web server.</p>\n\n                        <p align=\"center\">\n                            <a href=\"../";
                                echo $whmcsInstaller->getAdminPath();
                                echo "/\" id=\"btnGoToAdminArea\" class=\"btn btn-default\">Go to the Admin Area Now &raquo;</a>\n                        </p>\n\n                        <br />\n\n                        <h2>Thank you for choosing WHMCS!</h2>\n\n                        ";
                            }
                        }
                    }
                }
            }
        }
    }
}
echo "\n            <br>\n            <br>\n\n            <div align=\"center\"><small>Copyright &copy; WHMCS 2005-";
echo date("Y");
echo "<br>\n                <a href=\"https://www.whmcs.com/\" target=\"_blank\">www.whmcs.com</a></small>\n            </div>\n        </div>\n    </body>\n</html>\n";

?>