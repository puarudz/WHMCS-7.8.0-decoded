<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require dirname(__DIR__) . "/init.php";
$aInt = new WHMCS\Admin("Health and Updates");
$aInt->title = AdminLang::trans("healthCheck.title");
$aInt->sidebar = "help";
$aInt->icon = "support";
$smartyValues = array();
$healthChecks = new WHMCS\View\Admin\HealthCheck\HealthCheckRepository();
$keyChecks = $healthChecks->keyChecks();
$nonKeyChecks = $healthChecks->nonKeyChecks();
$allChecks = $keyChecks->merge($nonKeyChecks);
$export = App::get_req_var("export");
if ($export) {
    $exportData = array();
    foreach ($nonKeyChecks as $check) {
        $body = $textBody = $check->getBody();
        $body = str_replace("<ul>", " ", $body);
        $body = str_replace("</li>", " ", $body);
        $body = str_replace("</ul>", ".", $body);
        $exportData["json"][$check->getSeverityLevel()][$check->getName()] = strip_tags($body);
        $textBody = str_replace("<li>", " - ", $textBody);
        $textBody = str_replace("</li>", "\n", $textBody);
        $textBody = str_replace("<ul>", "\n", $textBody);
        $exportData["text"][$check->getSeverityLevel()][$check->getName()] = strip_tags($textBody);
    }
    if ($export == "json") {
        $aInt->setBodyContent($exportData["json"]);
        $aInt->output(defined("JSON_PRETTY_PRINT") ? JSON_PRETTY_PRINT : NULL);
        throw new WHMCS\Exception\ProgramExit();
    }
    if ($export == "text") {
        header("Content-type: text/plain");
        echo "WHMCS Health Check\n================================================================================\n";
        foreach ($exportData["text"] as $severity => $values) {
            echo "\n" . $severity . ":\n";
            foreach ($values as $value) {
                $strings = explode("\n", wordwrap($value, 77));
                $firstLine = array_shift($strings);
                echo " * " . $firstLine . "\n";
                if ($strings) {
                    foreach ($strings as $string) {
                        echo "   " . $string . "\n";
                    }
                }
            }
        }
        throw new WHMCS\Exception\ProgramExit();
    }
}
$apiResponse = localApi("GetHealthStatus", array());
$healthChecks = $apiResponse["checks"];
$smartyValues["totalChecks"] = $keyChecks->count() + $nonKeyChecks->count();
$smartyValues["checks"] = $allChecks->reduce(function ($results = 0, WHMCS\View\Admin\HealthCheck\HealthCheckResult $result) {
    $results = is_null($results) ? array() : $results;
    switch ($result->getSeverityLevel()) {
        case PSR\Log\LogLevel::INFO:
        case PSR\Log\LogLevel::NOTICE:
            $results["success"][] = $result;
            break;
        case PSR\Log\LogLevel::WARNING:
            $results["warning"][] = $result;
            break;
        case PSR\Log\LogLevel::ERROR:
        case PSR\Log\LogLevel::CRITICAL:
        case PSR\Log\LogLevel::ALERT:
        case PSR\Log\LogLevel::EMERGENCY:
            $results["danger"][] = $result;
            break;
    }
    return $results;
});
$smartyValues["successfulChecks"] = count($smartyValues["checks"]["success"]);
$smartyValues["warningChecks"] = count($smartyValues["checks"]["warning"]);
$smartyValues["dangerChecks"] = count($smartyValues["checks"]["danger"]);
$smartyValues["keyChecks"] = $keyChecks;
$smartyValues["regularChecks"] = $nonKeyChecks;
$checkPercentages = array("successful" => 0, "warning" => round($smartyValues["warningChecks"] / $smartyValues["totalChecks"] * 100, 0), "danger" => round($smartyValues["dangerChecks"] / $smartyValues["totalChecks"] * 100, 0));
$checkPercentages["successful"] = 100 - $checkPercentages["warning"] - $checkPercentages["danger"];
$smartyValues["checkPercentages"] = $checkPercentages;
$installedVersion = App::getVersion();
$installedVersionParts = explode(" ", $installedVersion->getCasual(), 2);
if (empty($installedVersionParts[1])) {
    $installedVersionParts[1] = "General Release";
}
$smartyValues["installedVersionNumberParts"] = $installedVersionParts;
$smartyValues["installedVersionNumberCanonical"] = $installedVersion->getCanonical();
$updater = new WHMCS\Installer\Update\Updater();
$smartyValues["installedVersionChangelog"] = $updater->getChangelogUrl();
$smartyValues["installedVersionReleaseNotes"] = $updater->getReleaseNotesUrl();
$aInt->template = "systemhealthandupdates";
$aInt->templatevars = $smartyValues;
$aInt->addInternalJqueryCode("\n\$(document).on('click', '.panel-heading span.clickable', function(e){\n    var \$this = \$(this);\n    if(!\$this.hasClass('panel-collapsed')) {\n        \$this.parents('.panel').find('.panel-body').slideUp();\n        \$this.addClass('panel-collapsed');\n        \$this.find('i').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');\n    } else {\n        \$this.parents('.panel').find('.panel-body').slideDown();\n        \$this.removeClass('panel-collapsed');\n        \$this.find('i').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');\n    }\n});\n\$(window).resize(minimiseSuccessPanel);\n\$(document).ready(minimiseSuccessPanel);\n");
$aInt->addHeadJsCode("\nfunction minimiseSuccessPanel() {\n    if (\$(\".health-status-col-danger\").css(\"left\") == \"0px\") {\n        \$(\".panel-health-check-success .panel-heading\").find(\"span\").addClass(\"panel-collapsed\").find(\"i\").removeClass(\"glyphicon-chevron-up\").addClass(\"glyphicon-chevron-down\");\n        \$(\".panel-health-check-success .panel-body\").css(\"display\", \"none\");\n    } else {\n        \$(\".panel-health-check-success .panel-heading\").find(\"span\").removeClass(\"panel-collapsed\").find(\"i\").removeClass(\"glyphicon-chevron-down\").addClass(\"glyphicon-chevron-up\");\n        \$(\".panel-health-check-success .panel-body\").css(\"display\", \"\");\n    }\n}\n");
$aInt->display();

?>