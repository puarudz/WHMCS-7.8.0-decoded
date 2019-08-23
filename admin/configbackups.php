<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Database Backups");
$aInt->title = $aInt->lang("backups", "title");
$aInt->sidebar = "config";
$aInt->icon = "dbbackups";
$aInt->helplink = "Backups";
$aInt->requireAuthConfirmation();
$activeBackupSystems = (new WHMCS\Backups\Backups())->getActiveProviders();
$action = App::getFromRequest("action");
$activate = (bool) (int) App::getFromRequest("activate");
$success = App::getFromRequest("success");
$type = App::getFromRequest("type");
if ($action == "deactivate") {
    check_token("WHMCS.admin.default");
    try {
        if (in_array($type, $activeBackupSystems)) {
            $activeBackupSystems = array_flip($activeBackupSystems);
            unset($activeBackupSystems[$type]);
            $activeBackupSystems = array_flip($activeBackupSystems);
            WHMCS\Config\Setting::setValue("ActiveBackupSystems", implode(",", array_filter($activeBackupSystems)));
            logAdminActivity("Automatic Backup Option Deactivated: " . ucfirst($type));
            $response = array("success" => true, "successMessage" => AdminLang::trans("backups.deactivateSuccess"), "successMessageTitle" => AdminLang::trans("backups.changesuccess"), "inactiveText" => AdminLang::trans("status.inactive"));
        } else {
            throw new WHMCS\Exception("Backup Type not Active");
        }
    } catch (Exception $e) {
        $response = array("success" => false, "errorMessage" => $e->getMessage(), "errorMessageTitle" => AdminLang::trans("global.erroroccurred"));
    }
    $aInt->jsonResponse($response);
}
if ($action == "test") {
    check_token("WHMCS.admin.default");
    try {
        switch ($type) {
            case "cpanel":
                $server = new WHMCS\Module\Server();
                $server->load("cpanel");
                $newPassword = trim(App::getFromRequest("cpanelAPIToken"));
                $originalPassword = decrypt(WHMCS\Config\Setting::getValue("CpanelBackupAPIToken"));
                $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
                if ($valueToStore === false) {
                    $newPassword = $originalPassword;
                }
                try {
                    $hostAddress = new WHMCS\Filter\HostAddress(App::getFromRequest("cpanelHostname"));
                } catch (WHMCS\Exception\Validation\InvalidHostAddress $e) {
                    throw new WHMCS\Exception(AdminLang::trans("validation.regex", array(":attribute" => AdminLang::trans("backups.cpanelHostname"))));
                }
                $response = $server->call("TestConnection", array("serverip" => "", "serverhostname" => $hostAddress->getHostname(), "serverusername" => App::getFromRequest("cpanelWHMUsername"), "serveraccesshash" => $newPassword, "serverhttpprefix" => "https", "serverport" => "2087", "serversecure" => true));
                if (array_key_exists("error", $response)) {
                    $error = $response["error"]["cpanelresult"]["error"];
                    throw new WHMCS\Exception($error);
                }
                break;
            case "ftp":
                $invalidField = NULL;
                try {
                    $hostAddress = new WHMCS\Filter\HostAddress(App::getFromRequest("ftpbackuphostname"), "", App::getFromRequest("ftpbackupport"));
                } catch (WHMCS\Exception\Validation\InvalidPort $e) {
                    $invalidField = "backups.ftpport";
                } catch (WHMCS\Exception\Validation\InvalidHostAddress $e) {
                    $invalidField = "backups.ftphost";
                }
                if ($invalidField) {
                    throw new WHMCS\Exception(AdminLang::trans("validation.regex", array(":attribute" => AdminLang::trans($invalidField))));
                }
                $connectionDetails = array("ftpBackupHostname" => $hostAddress->getHostname(), "ftpBackupPort" => $hostAddress->getPort(), "ftpBackupUsername" => App::getFromRequest("ftpbackupusername"), "ftpBackupDestination" => App::getFromRequest("ftpbackupdestination"), "ftpPassiveMode" => App::getFromRequest("ftppassivemode"), "ftpSecureMode" => (bool) App::getFromRequest("ftpsecuremode"));
                $newPassword = trim(App::getFromRequest("ftpbackuppassword"));
                $originalPassword = decrypt(WHMCS\Config\Setting::getValue("FTPBackupPassword"));
                $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
                if ($valueToStore === false) {
                    $newPassword = $originalPassword;
                }
                $connectionDetails["ftpBackupPassword"] = $newPassword;
                $tempFile = tempnam(sys_get_temp_dir(), "test");
                if (!touch($tempFile) || !chmod($tempFile, 384) || ($fh = fopen($tempFile, "w")) === false) {
                    throw new WHMCS\Exception("(S)FTP: Unable to open " . $tempFile . " for writing.");
                }
                $result = fwrite($fh, "WHMCS Test File");
                if ($result === false || $result === 0) {
                    fclose($fh);
                    throw new WHMCS\Exception("(S)FTP: Unable to write to temp file: " . $tempFile);
                }
                fclose($fh);
                if ($connectionDetails["ftpSecureMode"]) {
                    $sftp = new phpseclib\Net\SFTP($connectionDetails["ftpBackupHostname"], $connectionDetails["ftpBackupPort"] ?: 22);
                    if (!$sftp->login(WHMCS\Input\Sanitize::decode($connectionDetails["ftpBackupUsername"]), WHMCS\Input\Sanitize::decode($connectionDetails["ftpBackupPassword"]))) {
                        throw new WHMCS\Exception("SFTP Backup - Login Failed");
                    }
                    $upload = $sftp->put($connectionDetails["ftpBackupDestination"] . "testfile.txt", $tempFile);
                    $sftp->disconnect();
                } else {
                    $ftpConnection = ftp_connect($connectionDetails["ftpBackupHostname"], $connectionDetails["ftpBackupPort"] ?: 21, 20);
                    if (!$ftpConnection) {
                        throw new WHMCS\Exception("FTP: Could not connect to " . $connectionDetails["ftpBackupHostname"]);
                    }
                    if (!ftp_login($ftpConnection, WHMCS\Input\Sanitize::decode($connectionDetails["ftpBackupUsername"]), WHMCS\Input\Sanitize::decode($connectionDetails["ftpBackupPassword"]))) {
                        throw new WHMCS\Exception("FTP: Login Failed");
                    }
                    if ($connectionDetails["ftpPassiveMode"]) {
                        ftp_pasv($ftpConnection, true);
                    }
                    $upload = ftp_put($ftpConnection, $connectionDetails["ftpBackupDestination"] . "testfile.txt", $tempFile, FTP_BINARY);
                    ftp_close($ftpConnection);
                }
                if (!$upload) {
                    throw new WHMCS\Exception("(S)FTP Backup - Uploading Failed");
                }
                break;
            default:
                throw new WHMCS\Exception("Invalid Backup Type for Testing");
        }
        $response = array("success" => true, "successMessage" => AdminLang::trans("backups.testSuccess"), "successMessageTitle" => AdminLang::trans("global.success"));
    } catch (Exception $e) {
        $response = array("success" => false, "errorMessage" => $e->getMessage(), "errorMessageTitle" => AdminLang::trans("global.erroroccurred"));
    }
    $aInt->jsonResponse($response);
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $changes = array();
    try {
        $successMessage = AdminLang::trans("backups." . $type . "BackupSuccess");
        switch ($type) {
            case "cpanel":
                try {
                    $cpanelHostAddress = new WHMCS\Filter\HostAddress(App::getFromRequest("cpanelHostname"));
                } catch (WHMCS\Exception\Validation\InvalidHostAddress $e) {
                    throw new WHMCS\Exception(AdminLang::trans("validation.regex", array(":attribute" => AdminLang::trans("backups.cpanelHostname"))));
                }
                $invalidField = NULL;
                try {
                    $destinationHostAddress = new WHMCS\Filter\HostAddress(App::getFromRequest("cpanelDestinationHostname"), "", App::getFromRequest("cpanelDestinationPort"));
                } catch (WHMCS\Exception\Validation\InvalidPort $e) {
                    $invalidField = "backups.cpanelBackupDestinationPort";
                } catch (WHMCS\Exception\Validation\InvalidHostAddress $e) {
                    $invalidField = "backups.cpanelBackupDestinationHostname";
                }
                if ($invalidField) {
                    throw new WHMCS\Exception(AdminLang::trans("validation.regex", array(":attribute" => AdminLang::trans($invalidField))));
                }
                $save_arr = array("CpanelBackupHostname" => $cpanelHostAddress->getHostname(), "CpanelBackupWHMUsername" => App::getFromRequest("cpanelWHMUsername"), "CpanelBackupUsername" => App::getFromRequest("cpanelUsername"), "CpanelBackupDestination" => App::getFromRequest("cpanelDestination"), "CpanelBackupDestinationHostname" => $destinationHostAddress->getHostname(), "CpanelBackupDestinationPort" => $destinationHostAddress->getPort(), "CpanelBackupDestinationUser" => App::getFromRequest("cpanelDestinationUser"), "CpanelBackupDestinationDirectory" => App::getFromRequest("cpanelDestinationDirectory"), "CpanelBackupNotifyEmail" => App::getFromRequest("cpanelNotifyEmail"));
                $validate = new WHMCS\Validate();
                if (!$validate->validate("email", "cpanelNotifyEmail", "none")) {
                    throw new WHMCS\Exception("Invalid Notification Email Address");
                }
                $newPassword = trim(App::getFromRequest("cpanelAPIToken"));
                $originalPassword = decrypt(WHMCS\Config\Setting::getValue("CpanelBackupAPIToken"));
                $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
                if ($valueToStore !== false) {
                    $save_arr["CpanelBackupAPIToken"] = $valueToStore;
                    if ($newPassword != $originalPassword) {
                        $changes[] = "Cpanel Backup API Token Changed";
                    }
                }
                $newPassword = trim(App::getFromRequest("cpanelDestinationPassword"));
                $originalPassword = decrypt(WHMCS\Config\Setting::getValue("CpanelBackupDestinationPassword"));
                $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
                if ($valueToStore !== false) {
                    $save_arr["CpanelBackupDestinationPassword"] = $valueToStore;
                    if ($newPassword != $originalPassword) {
                        $changes[] = "Cpanel Backup Destination Password Changed";
                    }
                }
                if ($save_arr["CpanelBackupDestination"] == "homedir") {
                    $save_arr["CpanelBackupDestinationHostname"] = "";
                    $save_arr["CpanelBackupDestinationPort"] = "";
                    $save_arr["CpanelBackupDestinationUser"] = "";
                    $save_arr["CpanelBackupDestinationDirectory"] = "";
                } else {
                    if (!$save_arr["CpanelBackupDestinationPort"]) {
                        $save_arr["CpanelBackupDestinationPort"] = 21;
                    }
                }
                break;
            case "email":
                $save_arr = array("DailyEmailBackup" => App::getFromRequest("dailyemailbackup"));
                $validate = new WHMCS\Validate();
                if (!$validate->validate("email", "dailyemailbackup", "none")) {
                    throw new WHMCS\Exception("Invalid Email Address");
                }
                break;
            case "ftp":
                $invalidField = NULL;
                try {
                    $hostAddress = new WHMCS\Filter\HostAddress(App::getFromRequest("ftpbackuphostname"), "", App::getFromRequest("ftpbackupport"));
                } catch (WHMCS\Exception\Validation\InvalidPort $e) {
                    $invalidField = "backups.ftpport";
                } catch (WHMCS\Exception\Validation\InvalidHostAddress $e) {
                    $invalidField = "backups.ftphost";
                }
                if ($invalidField) {
                    throw new WHMCS\Exception(AdminLang::trans("validation.regex", array(":attribute" => AdminLang::trans($invalidField))));
                }
                $save_arr = array("FTPBackupHostname" => $hostAddress->getHostname(), "FTPBackupPort" => $hostAddress->getPort(), "FTPBackupUsername" => App::getFromRequest("ftpbackupusername"), "FTPBackupDestination" => App::getFromRequest("ftpbackupdestination"), "FTPPassiveMode" => App::getFromRequest("ftppassivemode"), "FTPSecureMode" => (bool) App::getFromRequest("ftpsecuremode"));
                $newPassword = trim(App::getFromRequest("ftpbackuppassword"));
                $originalPassword = decrypt(WHMCS\Config\Setting::getValue("FTPBackupPassword"));
                $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
                if ($valueToStore !== false) {
                    $save_arr["FTPBackupPassword"] = $valueToStore;
                    if ($newPassword != $originalPassword) {
                        $changes[] = "FTP Backup Password Changed";
                    }
                }
                break;
            default:
                throw new WHMCS\Exception("Invalid Backup Type");
        }
        if ($activate && !in_array($type, $activeBackupSystems)) {
            $activeBackupSystems[] = $type;
            $successMessage = AdminLang::trans("backups." . $type . "BackupActivationSuccess");
            WHMCS\Config\Setting::setValue("ActiveBackupSystems", implode(",", array_filter($activeBackupSystems)));
            if ($changes) {
                logAdminActivity("Automatic Backup Option Activated: " . ucfirst($type));
            }
        }
        foreach ($save_arr as $k => $v) {
            $currentSetting = WHMCS\Config\Setting::getValue($k);
            if ($v != $currentSetting && !in_array($k, array("FTPBackupPassword", "CpanelBackupAPIToken", "CpanelBackupDestinationPassword"))) {
                $regEx = "/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/x";
                $friendlySettingParts = preg_split($regEx, $k);
                $friendlySetting = implode(" ", $friendlySettingParts);
                $newSetting = $v;
                if (in_array($k, array("FTPPassiveMode", "FTPSecureMode"))) {
                    if ($currentSetting == "on") {
                        $newSetting = "off";
                    }
                    if ($newSetting == "on") {
                        $currentSetting = "off";
                    }
                }
                $changes[] = (string) $friendlySetting . " changed from '" . $currentSetting . "' to '" . $newSetting . "'";
            }
            WHMCS\Config\Setting::setValue($k, trim($v));
        }
        if ($changes) {
            logAdminActivity("Automatic Backup Settings Changed. " . implode(". ", $changes));
        }
        $response = array("success" => true, "successMessage" => $successMessage, "successMessageTitle" => AdminLang::trans("backups.changesuccess"), "activeText" => AdminLang::trans("status.active"));
    } catch (Exception $e) {
        $response = array("success" => false, "errorMessage" => $e->getMessage(), "errorMessageTitle" => AdminLang::trans("global.erroroccurred"));
    }
    $aInt->jsonResponse($response);
}
if (!WHMCS\Config\Setting::getValue("FTPBackupPort")) {
    WHMCS\Config\Setting::setValue("FTPBackupPort", "21");
}
ob_start();
foreach (array("cpanel", "email", "ftp") as $backupType) {
    ${$backupType} = in_array($backupType, $activeBackupSystems) ? "<span id=\"" . $backupType . "Label\" class=\"label label-success\">" . AdminLang::trans("status.active") . "</span>" : "<span id=\"" . $backupType . "Label\" class=\"label label-default\">" . AdminLang::trans("status.inactive") . "</span>";
    $booleanVar = $backupType . "Active";
    ${$booleanVar} = in_array($backupType, $activeBackupSystems);
}
$cpanelBackupDestination = WHMCS\Config\Setting::getValue("CpanelBackupDestination");
echo "<div id=\"alertInfo\" class=\"alert alert-success hidden\" role=\"alert\"></div>\n<div class=\"database-backups\">\n\n    <p>";
echo AdminLang::trans("backups.description");
echo "</p>\n\n    <div class=\"panel-group panel-backup-options\" id=\"accordion\" role=\"tablist\" aria-multiselectable=\"true\">\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\" role=\"tab\" id=\"headingOne\">\n                <h4 class=\"panel-title\">\n                    <a role=\"button\" data-toggle=\"collapse\" data-parent=\"#accordion\" href=\"#collapseOne\" aria-expanded=\"true\" aria-controls=\"collapseOne\">\n                        ";
echo AdminLang::trans("backups.ftp");
echo "                        ";
echo $ftp;
echo "                    </a>\n                </h4>\n            </div>\n            <div id=\"collapseOne\" class=\"panel-collapse collapse in\" role=\"tabpanel\" aria-labelledby=\"headingOne\">\n                <div class=\"panel-body\">\n\n                <form>\n                    <div class=\"row\">\n                        <div class=\"col-sm-9\">\n                            <div class=\"form-group\">\n                                <label for=\"inputHostname\">";
echo AdminLang::trans("backups.ftphost");
echo "</label>\n                                <input type=\"text\" name=\"ftpbackuphostname\" class=\"form-control\" id=\"inputFTPHostname\" placeholder=\"www.example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("FTPBackupHostname");
echo "\">\n                            </div>\n                        </div>\n                        <div class=\"col-sm-3\">\n                            <div class=\"form-group\">\n                                <label for=\"inputPort\">";
echo AdminLang::trans("backups.ftpport");
echo "</label>\n                                <input type=\"text\" name=\"ftpbackupport\" class=\"form-control\" id=\"inputFTPPort\" placeholder=\"22\" value=\"";
echo WHMCS\Config\Setting::getValue("FTPBackupPort");
echo "\">\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"row\">\n                        <div class=\"col-sm-6\">\n                            <div class=\"form-group\">\n                                <label for=\"inputUsername\">";
echo AdminLang::trans("backups.ftpuser");
echo "</label>\n                                <input type=\"text\" name=\"ftpbackupusername\" class=\"form-control\" id=\"inputFTPUsername\" placeholder=\"youruser@example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("FTPBackupUsername");
echo "\" autocomplete=\"off\">\n                            </div>\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <div class=\"form-group\">\n                                <label for=\"inputPassword\">";
echo AdminLang::trans("backups.ftppass");
echo "</label>\n                                <input type=\"password\" name=\"ftpbackuppassword\" class=\"form-control\" id=\"inputFTPPassword\" placeholder=\"FTP Password\" value=\"";
echo replacePasswordWithMasks(decrypt(WHMCS\Config\Setting::getValue("FTPBackupPassword")));
echo "\" autocomplete=\"off\">\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputDestination\">";
echo AdminLang::trans("backups.ftppath");
echo "</label>\n                        <input type=\"text\" name=\"ftpbackupdestination\" class=\"form-control\" id=\"inputFTPDestination\" placeholder=\"/backups/whmcs/\" value=\"";
echo WHMCS\Config\Setting::getValue("FTPBackupDestination");
echo "\">\n                    </div>\n                    <div class=\"checkbox\">\n                        <label>\n                            <input name=\"ftpsecuremode\" type=\"checkbox\"";
echo WHMCS\Config\Setting::getValue("FTPSecureMode") ? " checked=\"checked\"" : "";
echo ">\n                            ";
echo AdminLang::trans("backups.useSecureFtp");
echo "                        </label>\n                        &nbsp;\n                        <label>\n                            <input name=\"ftppassivemode\" type=\"checkbox\"";
echo WHMCS\Config\Setting::getValue("FTPPassiveMode") ? " checked=\"checked\"" : "";
echo ">\n                            ";
echo AdminLang::trans("backups.ftppassivemode");
echo "                        </label>\n                    </div>\n\n                    <div id=\"ftpTest\" class=\"alert alert-default hidden\" role=\"alert\">\n                        <span class=\"default-text\">";
echo AdminLang::trans("backups.testingConnection");
echo "</span>\n                        <span class=\"extra-text hidden\"></span>\n                    </div>\n                    <button type=\"button\" class=\"btn btn-info test\" data-type=\"ftp\">";
echo AdminLang::trans("backups.testConnection");
echo "</button>\n                    <button type=\"button\" class=\"btn btn-default activate";
echo $ftpActive ? " hidden" : "\" disabled=\"disabled";
echo "\" data-type=\"ftp\">";
echo AdminLang::trans("backups.saveAndActivate");
echo "</button>\n                    <button type=\"button\" class=\"btn btn-default save";
echo $ftpActive ? "" : " hidden";
echo "\" data-type=\"ftp\">";
echo AdminLang::trans("global.savechanges");
echo "</button>\n                    <button type=\"button\" class=\"btn btn-danger deactivate-start";
echo $ftpActive ? "" : " hidden";
echo "\" data-type=\"ftp\">";
echo AdminLang::trans("backups.deactivate");
echo "</button>\n                </form>\n\n                </div>\n            </div>\n        </div>\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\" role=\"tab\" id=\"headingTwo\">\n                <h4 class=\"panel-title\">\n                    <a class=\"collapsed\" role=\"button\" data-toggle=\"collapse\" data-parent=\"#accordion\" href=\"#collapseTwo\" aria-expanded=\"false\" aria-controls=\"collapseTwo\">\n                        ";
echo AdminLang::trans("backups.cpanel");
echo "                        ";
echo $cpanel;
echo "                    </a>\n                </h4>\n            </div>\n            <div id=\"collapseTwo\" class=\"panel-collapse collapse\" role=\"tabpanel\" aria-labelledby=\"headingTwo\">\n                <div class=\"panel-body\">\n\n                    <form>\n                        <div class=\"form-group\">\n                            <label for=\"inputCpanelHostname\">";
echo AdminLang::trans("backups.cpanelHostname");
echo "</label>\n                            <input type=\"text\" name=\"cpanelHostname\" class=\"form-control\" id=\"inputHostname\" placeholder=\"www.example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupHostname");
echo "\">\n                        </div>\n                        <div class=\"form-group\">\n                            <label for=\"inputCpanelUsername\">";
echo AdminLang::trans("backups.cpanelUsername");
echo "</label>\n                            <input type=\"text\" name=\"cpanelWHMUsername\" class=\"form-control\" id=\"inputWHMUsername\" placeholder=\"Username\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupWHMUsername");
echo "\">\n                            <p class=\"help-block\">";
echo AdminLang::trans("backups.cpanelUsernameDescription");
echo "</p>\n                        </div>\n                        <div class=\"form-group\">\n                            <label for=\"inputApiToken\">";
echo AdminLang::trans("backups.cpanelApiToken");
echo "</label>\n                            <input type=\"password\" name=\"cpanelAPIToken\" class=\"form-control\" id=\"inputApiToken\" placeholder=\"WHM API Token\" value=\"";
echo replacePasswordWithMasks(decrypt(WHMCS\Config\Setting::getValue("CpanelBackupAPIToken")));
echo "\">\n                            <p class=\"help-block\">";
echo AdminLang::trans("backups.cpanelApiTokenDescription");
echo "</p>\n                        </div>\n                        <div class=\"form-group\">\n                            <label for=\"inputCpanelUsername\">";
echo AdminLang::trans("backups.cpanelBackupUser");
echo "</label>\n                            <input type=\"text\" name=\"cpanelUsername\" class=\"form-control\" id=\"inputCpanelUsername\" placeholder=\"Username\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupUsername");
echo "\">\n                            <p class=\"help-block\">";
echo AdminLang::trans("backups.cpanelBackupUserDescription");
echo "</p>\n                        </div>\n                        <div class=\"form-group\">\n                            <label for=\"inputDestination\">";
echo AdminLang::trans("backups.cpanelBackupDestination");
echo "</label>\n                            <select id=\"inputDestination\" name=\"cpanelDestination\" class=\"form-control\">\n                                <option value=\"ftp\"";
echo $cpanelBackupDestination == "ftp" ? " selected=\"selected\"" : "";
echo ">";
echo AdminLang::trans("backups.cpanelBackupDestinationFTP");
echo "</option>\n                                <option value=\"passiveftp\"";
echo $cpanelBackupDestination == "passiveftp" ? " selected=\"selected\"" : "";
echo ">";
echo AdminLang::trans("backups.cpanelBackupDestinationPassiveFTP");
echo "</option>\n                                <option value=\"scp\"";
echo $cpanelBackupDestination == "scp" ? " selected=\"selected\"" : "";
echo ">";
echo AdminLang::trans("backups.cpanelBackupDestinationSCP");
echo "</option>\n                                <option value=\"homedir\"";
echo $cpanelBackupDestination == "homedir" ? " selected=\"selected\"" : "";
echo ">";
echo AdminLang::trans("backups.cpanelBackupDestinationHomeDirectory");
echo "</option>\n                            </select>\n                        </div>\n                        <div id=\"destinationData\"";
echo $cpanelBackupDestination == "homedir" ? " class=\"hidden\"" : "";
echo ">\n                            <div class=\"row\">\n                                <div class=\"col-sm-9\">\n                                    <div class=\"form-group\">\n                                        <label for=\"inputHostname\">";
echo AdminLang::trans("backups.cpanelBackupDestinationHostname");
echo "</label>\n                                        <input type=\"text\" name=\"cpanelDestinationHostname\" class=\"form-control\" id=\"inputHostname\" placeholder=\"www.example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupDestinationHostname");
echo "\">\n                                    </div>\n                                </div>\n                                <div class=\"col-sm-3\">\n                                    <div class=\"form-group\">\n                                        <label for=\"inputPort\">";
echo AdminLang::trans("backups.cpanelBackupDestinationPort");
echo "</label>\n                                        <input type=\"text\" name=\"cpanelDestinationPort\" class=\"form-control\" id=\"inputPort\" placeholder=\"22\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupDestinationPort");
echo "\">\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"row\">\n                                <div class=\"col-sm-6\">\n                                    <div class=\"form-group\">\n                                        <label for=\"inputUsername\">";
echo AdminLang::trans("backups.cpanelBackupDestinationUser");
echo "</label>\n                                        <input type=\"text\" name=\"cpanelDestinationUser\" class=\"form-control\" id=\"inputUsername\" placeholder=\"youruser@example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupDestinationUser");
echo "\" autocomplete=\"off\">\n                                    </div>\n                                </div>\n                                <div class=\"col-sm-6\">\n                                    <div class=\"form-group\">\n                                        <label for=\"inputPassword\">";
echo AdminLang::trans("backups.cpanelBackupDestinationPassword");
echo "</label>\n                                        <input type=\"password\" name=\"cpanelDestinationPassword\" class=\"form-control\" id=\"inputPassword\" placeholder=\"FTP Password\" value=\"";
echo replacePasswordWithMasks(decrypt(WHMCS\Config\Setting::getValue("CpanelBackupDestinationPassword")));
echo "\" autocomplete=\"off\">\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"form-group\">\n                                <label for=\"inputDestination\">";
echo AdminLang::trans("backups.cpanelBackupDestinationDirectory");
echo "</label>\n                                <input type=\"text\" name=\"cpanelDestinationDirectory\" class=\"form-control\" id=\"inputDestination\" placeholder=\"/backups/whmcs/\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupDestinationDirectory");
echo "\">\n                            </div>\n                        </div>\n                        <div class=\"form-group\">\n                            <label for=\"inputEmailConfirmation\">";
echo AdminLang::trans("backups.cpanelBackupNotifyEmail");
echo "</label>\n                            <input type=\"email\" name=\"cpanelNotifyEmail\" class=\"form-control\" id=\"inputEmailConfirmation\" placeholder=\"yourname@example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("CpanelBackupNotifyEmail");
echo "\">\n                            <p class=\"help-block\">";
echo AdminLang::trans("backups.cpanelBackupNotifyEmailDescription");
echo "</p>\n                        </div>\n\n                        <div id=\"cpanelTest\" class=\"alert alert-default hidden\" role=\"alert\">\n                            <span class=\"default-text\">";
echo AdminLang::trans("backups.testingConnection");
echo "</span>\n                            <span class=\"extra-text hidden\"></span>\n                        </div>\n                        <button type=\"button\" class=\"btn btn-info test\" data-type=\"cpanel\">";
echo AdminLang::trans("backups.testConnection");
echo "</button>\n                        <button type=\"button\" class=\"btn btn-default activate";
echo $cpanelActive ? " hidden" : "\" disabled=\"disabled";
echo "\" data-type=\"cpanel\">";
echo AdminLang::trans("backups.saveAndActivate");
echo "</button>\n                        <button type=\"button\" class=\"btn btn-default save";
echo $cpanelActive ? "" : " hidden";
echo "\" data-type=\"cpanel\">";
echo AdminLang::trans("global.savechanges");
echo "</button>\n                        <button type=\"button\" class=\"btn btn-danger deactivate-start";
echo $cpanelActive ? "" : " hidden";
echo "\" data-type=\"cpanel\">";
echo AdminLang::trans("backups.deactivate");
echo "</button>\n                    </form>\n\n                </div>\n            </div>\n        </div>\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\" role=\"tab\" id=\"headingThree\">\n                <h4 class=\"panel-title\">\n                    <a class=\"collapsed\" role=\"button\" data-toggle=\"collapse\" data-parent=\"#accordion\" href=\"#collapseThree\" aria-expanded=\"false\" aria-controls=\"collapseThree\">\n                        ";
echo AdminLang::trans("backups.dailyemail");
echo "                        ";
echo $email;
echo "                    </a>\n                </h4>\n            </div>\n            <div id=\"collapseThree\" class=\"panel-collapse collapse\" role=\"tabpanel\" aria-labelledby=\"headingThree\">\n                <div class=\"panel-body\">\n                    <form>\n                        <div class=\"form-group\">\n                            <label for=\"inputEmail\">";
echo AdminLang::trans("backups.emailBackupEmail");
echo "</label>\n                            <input type=\"email\" name=\"dailyemailbackup\" class=\"form-control\" id=\"inputEmail\" placeholder=\"yourname@example.com\" value=\"";
echo WHMCS\Config\Setting::getValue("DailyEmailBackup");
echo "\">\n                            <p class=\"help-block\">";
echo AdminLang::trans("backups.emailBackupEmailInfo");
echo "</p>\n                        </div>\n                        <button type=\"button\" class=\"btn btn-default activate";
echo $emailActive ? " hidden" : "";
echo "\" data-type=\"email\">";
echo AdminLang::trans("backups.saveAndActivate");
echo "</button>\n                        <button type=\"button\" class=\"btn btn-default save";
echo $emailActive ? "" : " hidden";
echo "\" data-type=\"email\">";
echo AdminLang::trans("global.savechanges");
echo "</button>\n                        <button type=\"button\" class=\"btn btn-danger deactivate-start";
echo $emailActive ? "" : " hidden";
echo "\" data-type=\"email\">";
echo AdminLang::trans("backups.deactivate");
echo "</button>\n                    </form>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n\n";
$title = AdminLang::trans("backups.confirmDeactivate");
$question = AdminLang::trans("backups.deactivateAreYouSure");
$yes = AdminLang::trans("global.yes");
$no = AdminLang::trans("global.no");
$close = AdminLang::trans("global.close");
echo "<div class=\"modal fade in\" id=\"modalConfirmDeactivate\" role=\"dialog\" aria-labelledby=\"confirmDeactivateLabel\" aria-hidden=\"true\">\n    <div class=\"modal-dialog\">\n        <div class=\"modal-content panel panel-primary\">\n            <div id=\"modalConfirmDeactivateHeading\" class=\"modal-header panel-heading\">\n                <button type=\"button\" class=\"close\" data-dismiss=\"modal\">\n                    <span aria-hidden=\"true\">×</span>\n                    <span class=\"sr-only\">" . $close . "</span>\n                </button>\n                <h4 class=\"modal-title\" id=\"confirmDeactivateLabel\">" . $title . "</h4>\n            </div>\n            <div id=\"modalConfirmDeactivateBody\" class=\"modal-body panel-body\">\n                " . $question . "\n            </div>\n            <div id=\"modalConfirmDeactivateFooter\" class=\"modal-footer panel-footer\">\n                <form>\n                    <button type=\"button\" id=\"confirmDeactivateYes\" class=\"btn btn-primary deactivate\" data-type=\"\">\n                        " . $yes . "\n                    </button>\n                    <button type=\"button\" id=\"confirmDeactivateNo\" class=\"btn btn-default\" data-dismiss=\"modal\">\n                        " . $no . "\n                    </button>\n                </form>\n            </div>\n        </div>\n    </div>\n</div>";
echo "<form>" . $aInt->modal("ConfirmDeactivate", AdminLang::trans("backups.confirmDeactivate"), AdminLang::trans("backups.deactivateAreYouSure"), array(array("title" => AdminLang::trans("global.yes"), "class" => "btn-primary deactivate", "onclick" => ""), array("title" => AdminLang::trans("global.no")))) . "</form>";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>