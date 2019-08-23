<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require dirname(__DIR__) . "/init.php";
$aInt = new WHMCS\Admin("Update WHMCS");
if ($_SERVER["HTTP_X_REQUESTED_WITH"] != "XMLHttpRequest" || $_SERVER["REQUEST_METHOD"] != "POST") {
    App::redirect("update.php");
}
if (!$aInt->hasAuthConfirmation()) {
    $aInt->jsonResponse(array("body" => "Authentication has expired. Please refresh the page and try again."));
}
$version = App::getFromRequest("version");
if (!$version) {
    $aInt->jsonResponse(array("body" => "Invalid request. Please try again."));
}
if ($version == App::getVersion()->getVersion()) {
    $aInt->jsonResponse(array("body" => "Invalid request. Cannot Update to Current Version."));
}
$changedFiles = array();
$hashListFile = ROOTDIR . "/resources/file_hashes/hash_list.php";
if (file_exists($hashListFile)) {
    try {
        $hashes = (include $hashListFile);
    } catch (Exception $e) {
        $hashes = array();
    }
    foreach ($hashes as $filePath => $controlHash) {
        $filename = ROOTDIR . DIRECTORY_SEPARATOR . $filePath;
        if (file_exists($filename)) {
            $actualFileHash = hash_file("sha1", $filename);
            if (strcasecmp($actualFileHash, $controlHash) !== 0) {
                $changedFiles[] = $filePath;
            }
        }
    }
}
$updaterUpdateToken = generateFriendlyPassword(64);
WHMCS\Config\Setting::setValue("UpdaterUpdateToken", $updaterUpdateToken);
$steps = array();
$healthCheck = new WHMCS\View\Admin\HealthCheck\HealthCheckRepository();
$updater = new WHMCS\Installer\Update\Updater();
$passesHealthCheck = $healthCheck->checkUpdaterRequirements($updater);
$updateBlock = "";
if ($passesHealthCheck->getSeverityLevel() === Psr\Log\LogLevel::WARNING) {
    $bodyOutput = $passesHealthCheck->getBody();
    if (substr_count($bodyOutput, "<li>") == 1) {
        $bodyOutput = strip_tags($bodyOutput);
    } else {
        $bodyOutput = "The following environment issue(s) are preventing automatic updates:" . $bodyOutput;
    }
    $updateBlock .= "\n<div class=\"alert alert-danger\" id=\"preUpdateWarning\">\n    <div class=\"alert-icon\">\n        <i class=\"fas fa-exclamation-triangle\"></i>\n    </div>\n    <div class=\"alert-msg\">\n        <strong>Unable to Perform Automatic Updates</strong><br>\n        " . $bodyOutput . "\n    </div>\n</div>\n<script>\n    jQuery(\"#modalAjaxClose\").show();\n    jQuery(\"#modalAjax .modal-submit\").hide();\n</script>";
}
$steps[] = "\n<h1>You are about to perform an update</h1>\n<h2>" . $version . "</h2>" . ($updateBlock ? $updateBlock : "\n<div class=\"alert alert-warning\">\n    We recommend ensuring you have at least <strong>250MB</strong> of available disk space before performing an update.\n</div>\n<form method=\"post\" action=\"systemdatabase.php?dlbackup=1\">\n    " . generate_token() . "\n    <div class=\"alert alert-danger\">\n        <div style=\"display:inline-block;\">\n            <button type=\"submit\" class=\"btn btn-default updater-btn-download-backup\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"Database backups generated through the browser can be affected by your PHP environment memory and time limit settings. If you have a larger database, you may need to use an external tool to take a backup.\">\n                <i class=\"fas fa-download\"></i>\n                &nbsp;Download Database Backup\n            </button>\n        </div>\n        <div class=\"updater-download-backup-desc\">\n            Before proceeding, please make a backup of your WHMCS installation.<br />\n            We recommend backing up both your files and database.\n        </div>\n    </div>\n</form>\n<script>\n\$(function () {\n    \$('[data-toggle=\"tooltip\"]').tooltip();\n})\n</script>\n");
if (0 < count($changedFiles)) {
    $steps[] = "\n<h1>Customised Files</h1>\n<p>We have detected changes in the following <strong>" . count($changedFiles) . "</strong> file" . (count($changedFiles) != 1 ? "s" : "") . ". They will be overwritten by the update.<br />\nIf you wish to keep them, please save a copy of them now. <a href=\"https://docs.whmcs.com/Updater_File_Changes\" target=\"_blank\">Learn more</a></p>\n\n    <div class=\"changed-files\">\n        <ul>\n            <li>" . implode("<li>", $changedFiles) . "\n        </ul>\n    </div>\n    ";
}
$steps[] = "\n<div class=\"ready-to-begin\">\n    <h3 class=\"textgreen\"><i class=\"fas fa-check\"></i> The update is ready to begin.</h3>\n    <p>Once started, the update process cannot be stopped. The update may take several minutes to complete.</p>\n    <div class=\"alert alert-info\">\n        Please do not navigate away from this page or close your browser.<br>\n        You will be redirected automatically once complete.\n    </div>\n    <p>When you are ready to begin the update, click <strong>Begin Update</strong> below.</p>\n</div>\n<input type=\"hidden\" name=\"step\" value=\"preflight\">\n";
$steps[] = "\n<h1>Performing Update</h1>\n<p><img src=\"images/hourglass.svg\"></p>\n<p style=\"margin:15px 0\">This may take several minutes to complete.</p>\n<div id=\"updateNavigationWarning\" class=\"alert alert-warning\">\n    Please do not navigate away from this page or close your browser.<br>\n    You will be redirected automatically once complete.\n</div>\n<div id=\"updateStallWarning\" class=\"alert alert-danger\">\n    This seems to be taking a while!<br/>\n    If the update hasn't completed in the next few minutes, we recommend refreshing this page and trying again.\n</div>\n<input type=\"hidden\" name=\"step\" value=\"update\">\n<input type=\"hidden\" id=\"updaterUpdateToken\" value=\"" . $updaterUpdateToken . "\">\n";
$steps[] = "\n<div class=\"update-result-successful\">\n    <div class=\"alert alert-success update-successful\" style=\"margin-top:-15px;\">\n        <i class=\"fas fa-check-circle\"></i> The update was successful!\n    </div>\n    <p>Your WHMCS installation has been updated successfully.</p>\n    <p>We recommend reading the release notes for this version before continuing.</p>\n    <div class=\"alert alert-update-banner-grey text-left\">\n        <strong>\n            <i class=\"fas fa-exclamation-triangle\"></i> Important Note about Templates\n        </strong><br>\n        If you are using a custom template, updates may be required.\n        It is important to keep your custom templates up-to-date to ensure an uninterrupted and error\n        free experience while using WHMCS. Please see the\n        <a href=\"#\" id=\"linkTemplateChanges\" class=\"autoLinked template-changes-link\">Release Notes</a>\n        for details of any changes.\n    </div>\n    <p><a href=\"#\" id=\"btnInstalledReleaseNotes\" class=\"btn btn-default btn-lg release-notes-link\" target=\"_blank\">View Release Notes</a></p>\n</div>\n<div class=\"update-result-failed\">\n    <div class=\"alert alert-danger update-successful\" style=\"margin-top:-15px;\">\n        <i class=\"fas fa-exclamation-triangle\"></i> Update Failed\n    </div>\n    <p>An error occurred that prevented the update from completing successfully.</p>\n    <div class=\"well update-failure-output\">Unknown error response.</div>\n    <p>Please try again and if the issue persists, please contact support.</p>\n</div>\n<input type=\"hidden\" name=\"step\" value=\"finish\">\n";
$aInt->jsonResponse(array("body" => "<div class=\"update-steps\"><div>" . implode("</div><div>", $steps) . "</div></div>"));

?>