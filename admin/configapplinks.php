<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Application Links");
$aInt->title = AdminLang::trans("setup.applicationLinks");
$aInt->sidebar = "config";
$aInt->icon = "autosettings";
$aInt->helplink = "Application Links";
$action = $whmcs->get_req_var("action");
if ($action == "toggle") {
    check_token("WHMCS.admin.default");
    $state = $whmcs->get_req_var("state");
    $module = explode("_", $whmcs->get_req_var("module"));
    $moduleName = $module[1];
    if ($state == "true") {
        $moduleInterface = new WHMCS\Module\Server();
        $moduleInterface->load($moduleName);
        $moduleInterface->enableApplicationLinks();
        logActivity("Application Links Enabled for " . $moduleInterface->getDisplayName());
    } else {
        if ($state == "false") {
            $moduleInterface = new WHMCS\Module\Server();
            $moduleInterface->load($moduleName);
            $moduleInterface->disableApplicationLinks();
            logActivity("Application Links Disabled for " . $moduleInterface->getDisplayName());
        }
    }
    $appLink = WHMCS\ApplicationLink\ApplicationLink::whereModuleType($moduleInterface->getType())->whereModuleName($moduleInterface->getLoadedModule())->first();
    echo WHMCS\ApplicationLink\Log::whereApplinkId($appLink->id)->whereLevel(300)->count();
    WHMCS\Terminus::getInstance()->doExit();
} else {
    if ($action == "savemoduleconfig") {
        check_token("WHMCS.admin.default");
        $enabled = $whmcs->get_req_var("enabled");
        $label = $whmcs->get_req_var("label");
        $order = $whmcs->get_req_var("tblCpanelLinks");
        $positions = array_flip($order);
        $moduleInterface = new WHMCS\Module\Server();
        $moduleInterface->load("cpanel");
        $availableAppLinks = $moduleInterface->call("GetSupportedApplicationLinks");
        if (is_array($availableAppLinks)) {
            $appLink = WHMCS\ApplicationLink\ApplicationLink::firstOrCreate(array("module_type" => $moduleInterface->getType(), "module_name" => $moduleInterface->getLoadedModule()));
            foreach ($availableAppLinks as $scope => $link) {
                $alternativeScope = str_replace(":", ".", $scope);
                $link = WHMCS\ApplicationLink\Links::firstOrNew(array("applink_id" => $appLink->id, "scope" => $scope));
                $link->displayLabel = $label[$alternativeScope];
                $link->isEnabled = $enabled[$alternativeScope] ?: 0;
                $link->order = $positions[$alternativeScope];
                $link->save();
            }
            if ($appLink->isEnabled) {
                $moduleInterface->syncApplicationLinksConfigChange();
            }
            echo WHMCS\ApplicationLink\Log::whereApplinkId($appLink->id)->whereLevel(300)->count();
        }
        throw new WHMCS\Exception\ProgramExit();
    } else {
        if ($action == "getlog") {
            $modalContent = "<table class=\"table table-striped applink-links\" id=\"tblCpanelLinks\">\n    <thead>\n        <tr>\n            <td>" . AdminLang::trans("global.timestamp") . "</td>\n            <td>" . AdminLang::trans("global.logEntry") . "</td>\n            <td>" . AdminLang::trans("global.logLevel") . "</td>\n        </tr>\n    </thead>\n    <tbody>";
            $appLink = WHMCS\ApplicationLink\ApplicationLink::firstOrNew(array("module_type" => $whmcs->get_req_var("moduletype"), "module_name" => $whmcs->get_req_var("modulename")));
            $logStyles = array(100 => "default", 200 => "info", 250 => "warning", 300 => "danger");
            foreach ($appLink->log()->get() as $log) {
                $modalContent .= "<tr>\n    <td>" . $log->created_at . "</td>\n    <td>" . $log->message . "</td>\n    <td class=\"text-center\">\n        <span class=\"label label-" . $logStyles[$log->level] . "\">" . Monolog\Logger::getLevelName($log->level) . "</span>\n    </td>\n</tr>";
            }
            $modalContent .= "\n    </tbody>\n</table>";
            echo $modalContent;
            throw new WHMCS\Exception\ProgramExit();
        }
    }
}
$modalOutput = "";
ob_start();
echo "\n<p>";
echo AdminLang::trans("appLinks.description");
echo "</p>\n<p>";
echo AdminLang::trans("appLinks.description2");
echo "</p>\n\n<div class=\"inset-grey-bg\">\n\n    ";
$assetHelper = DI::make("asset");
$moduleTypes = array("WHMCS\\Module\\Server" => "servers");
foreach ($moduleTypes as $class => $moduleType) {
    $moduleInterface = new $class();
    foreach ($moduleInterface->getList() as $module) {
        if ($moduleInterface->load($module) && $moduleInterface->isApplicationLinkSupported()) {
            $logo = $moduleInterface->getLogoFilename() ?: ($logo = $assetHelper->getImgPath() . DIRECTORY_SEPARATOR . "empty_logo.png");
            $appLink = WHMCS\ApplicationLink\ApplicationLink::whereModuleType($moduleInterface->getType())->whereModuleName($moduleInterface->getLoadedModule())->first();
            $logWarningCount = WHMCS\ApplicationLink\Log::whereApplinkId($appLink->id)->whereLevel(300)->count();
            $uCFirstModuleName = ucfirst($module);
            if ($appLink->isEnabled) {
                $appLinkStatus = " checked";
                $disabledStatus = " hidden";
                $activeStatus = "";
            } else {
                $appLinkStatus = "";
                $disabledStatus = "";
                $activeStatus = " hidden";
            }
            if (Illuminate\Database\Capsule\Manager::table("tblservers")->where("tblservers.type", "=", $moduleInterface->getLoadedModule())->whereDisabled("0")->count() == 0) {
                $appLinkStatus .= " disabled=\"disabled\"";
                $noServersMsg = "<br /><span class=\"small\"><em>No Active " . $moduleInterface->getDisplayName() . " Servers Found.</em></span>";
            } else {
                $noServersMsg = "";
            }
            $availableAppLinks = NULL;
            $configureLink = "";
            if ($moduleInterface->functionExists("GetSupportedApplicationLinks")) {
                $availableAppLinks = $moduleInterface->call("GetSupportedApplicationLinks");
                if (is_array($availableAppLinks) && 0 < count($availableAppLinks)) {
                    $configureLink = "<a href=\"#\" class=\"btn btn-link\" id=\"btn" . $uCFirstModuleName . "Configure\" data-toggle=\"modal\" data-target=\"#modalConfigure" . $uCFirstModuleName . "Settings\">Configure</a>";
                } else {
                    $availableAppLinks = NULL;
                }
            }
            $disabledString = AdminLang::trans("global.disabled");
            $initAppLinksString = AdminLang::trans("appLinks.initPleaseWait");
            $savingConfigString = AdminLang::trans("appLinks.savingConfigChanges");
            $disablingString = AdminLang::trans("appLinks.disabling");
            $activeString = AdminLang::trans("status.active");
            $viewLogString = AdminLang::trans("global.viewLog");
            $warningsString = AdminLang::trans("global.warnings");
            echo "<div class=\"inset-element-container\">\n    <div class=\"row\">\n        <div class=\"col-sm-3 bottom-xs-margin\">\n            <img src=\"" . $logo . "\" class=\"img-responsive center-block\" alt=\"" . $moduleInterface->getDisplayName() . "\" />\n        </div>\n        <div class=\"col-sm-6 bottom-xs-margin\">\n            <div class=\"bottom-margin-5\">" . $moduleInterface->getApplicationLinkDescription() . $noServersMsg . "</div>\n            <div id=\"status" . $uCFirstModuleName . "\">\n                <span id=\"status" . $uCFirstModuleName . "Disabled\" class=\"label label-default app-link-status" . $disabledStatus . "\">\n                    <i class=\"fas fa-times\"></i>\n                    " . $disabledString . "\n                </span>\n                <span id=\"status" . $uCFirstModuleName . "Initialising\" class=\"label label-warning app-link-status hidden\">\n                    <i class=\"fas fa-spinner fa-spin\"></i>\n                    " . $initAppLinksString . "\n                </span>\n                <span id=\"status" . $uCFirstModuleName . "Updating\" class=\"label label-warning app-link-status hidden\">\n                    <i class=\"fas fa-spinner fa-spin\"></i>\n                    " . $savingConfigString . "\n                </span>\n                <span id=\"status" . $uCFirstModuleName . "Disabling\" class=\"label label-warning app-link-status hidden\">\n                    <i class=\"fas fa-spinner fa-spin\"></i>\n                    " . $disablingString . "\n                </span>\n                <span id=\"status" . $uCFirstModuleName . "Active\" class=\"label label-success app-link-status" . $activeStatus . "\">\n                    <i class=\"fas fa-check-circle\"></i>\n                    " . $activeString . "\n                </span>\n                &nbsp;\n                <button id=\"btn" . $uCFirstModuleName . "ViewLog\" class=\"btn btn-default btn-xs\" onclick=\"showLogModal('" . $moduleInterface->getType() . "', '" . $moduleInterface->getLoadedModule() . "')\">\n                    <i class=\"far fa-file-alt\"></i>\n                    " . $viewLogString . " (<span id=\"btn" . $uCFirstModuleName . "ViewLogWarningCount\">" . $logWarningCount . "</span> " . $warningsString . ")\n                </button>\n            </div>\n        </div>\n        <div class=\"col-sm-3 text-center\">\n            <input id=\"input" . $uCFirstModuleName . "Status\" type=\"checkbox\" name=\"" . $moduleInterface->getType() . "_" . $module . "\"" . $appLinkStatus . " class=\"app-toggle-switch\"><br />\n            " . $configureLink . "\n        </div>\n    </div>\n</div>";
            $configureWhichLinksString = AdminLang::trans("appLinks.configureWhichLinks");
            $dragAndDropString = AdminLang::trans("appLinks.dragAndDrop");
            $linkDescString = AdminLang::trans("appLinks.linkDescription");
            $displayLabelString = AdminLang::trans("appLinks.displayLabel");
            $configToken = generate_token();
            $modalContent = "<p>" . $configureWhichLinksString . "<br /><em>" . $dragAndDropString . "</em></p>\n<form id='frmConfigure" . $uCFirstModuleName . "'>\n    " . $configToken . "\n    <table class='table table-striped applink-links' id='tbl" . $uCFirstModuleName . "Links'>\n        <thead>\n            <tr>\n                <td></td>\n                <td>" . $linkDescString . "</td>\n                <td class='text-center'>" . $displayLabelString . "</td>\n                <td></td>\n            </tr>\n        </thead>\n        <tbody>\n";
            if (is_array($availableAppLinks)) {
                $appLink = WHMCS\ApplicationLink\ApplicationLink::firstOrNew(array("module_type" => $moduleInterface->getType(), "module_name" => $moduleInterface->getLoadedModule()));
                $sortedTableRows = array();
                $unsortedTableRows = array();
                foreach ($availableAppLinks as $scope => $link) {
                    $isEnabled = true;
                    $displayLabel = $link["label"];
                    $order = 0;
                    $dbLink = $appLink->links()->whereScope($scope)->first();
                    if (!is_null($dbLink)) {
                        $isEnabled = $dbLink->isEnabled;
                        $displayLabel = $dbLink->displayLabel;
                        $order = $dbLink->order;
                    }
                    $postScope = str_replace(":", ".", $scope);
                    $row = "\n                            <tr id=\"" . $postScope . "\">\n                                <td class=\"applink-link-input-offset\"><input type=\"checkbox\" name=\"enabled[" . $postScope . "]\" value=\"1\"" . ($isEnabled ? " checked" : "") . " class=\"toggle-switch\" data-size=\"small\" /></td>\n                                <td><strong>" . $link["label"] . "</strong><br />" . $link["description"] . "</td>\n                                <td class=\"applink-link-input-offset\"><input type=\"text\" name=\"label[" . $postScope . "]\" value=\"" . $displayLabel . "\" class=\"form-control input-sm\" /></td>\n                                <td class=\"sortcol\">&nbsp;</td>\n                            </tr>\n                        ";
                    if (0 < $order) {
                        $sortedTableRows[$order] = $row;
                    } else {
                        $unsortedTableRows[] = $row;
                    }
                }
                ksort($sortedTableRows);
                ksort($unsortedTableRows);
                $modalContent .= implode($sortedTableRows) . implode($unsortedTableRows);
            }
            $modalContent .= "\n        </tbody>\n    </table>\n</form>";
            $modalOutput .= $aInt->modal("Configure" . $uCFirstModuleName . "Settings", AdminLang::trans("appLinks.configAppLinks"), $modalContent, array(array("title" => AdminLang::trans("global.savechanges"), "class" => "btn-primary", "onclick" => "configurationSubmit(\"" . $uCFirstModuleName . "\");"), array("title" => AdminLang::trans("global.cancel"))), "large");
        }
    }
}
echo "\n</div>\n\n";
$content = ob_get_contents();
ob_end_clean();
$modalOutput .= $aInt->modal("LogView", "Viewing Log", AdminLang::trans("global.loading"), array(array("title" => "Dismiss")), "", "default");
$content .= $modalOutput . WHMCS\View\Asset::jsInclude("jqueryro.js");
$jsCode = "\nfunction configurationSubmit(moduleName) {\n    jQuery(\"#modalConfigure\" + moduleName + \"Settings\").modal(\"hide\");\n\n    if (jQuery(\"#input\" + moduleName + \"Status\").is(\":checked\")) {\n        jQuery(\"#status\" + moduleName + \" .app-link-status\").addClass(\"hidden\");\n        jQuery(\"#status\" + moduleName + \"Updating\").removeClass(\"hidden\");\n        jQuery(\"#btn\" + moduleName + \"Configure\").addClass(\"disabled\");\n        jQuery(\"#btn\" + moduleName + \"ViewLog\").hide();\n    }\n\n    WHMCS.http.jqClient.post(\n        \"configapplinks.php\",\n        \"action=savemoduleconfig&\" + jQuery(\"#frmConfigure\" + moduleName).serialize() + \"&\" + jQuery(\"#tbl\" + moduleName + \"Links\").tableDnDSerialize(),\n        function(data) {\n            if (jQuery(\"#input\" + moduleName + \"Status\").is(\":checked\")) {\n                jQuery(\".app-link-status\").addClass(\"hidden\");\n                jQuery(\"#status\" + moduleName + \"Active\").removeClass(\"hidden\");\n                jQuery(\"#btn\" + moduleName + \"Configure\").removeClass(\"disabled\");\n                jQuery(\"#btn\" + moduleName + \"ViewLogWarningCount\").html(data);\n                jQuery(\"#btn\" + moduleName + \"ViewLog\").fadeIn();\n            }\n        }\n    );\n}\nfunction showLogModal(moduleType, moduleName) {\n    jQuery(\"#modalLogView\").modal(\"show\");\n    WHMCS.http.jqClient.post(\"configapplinks.php\", \"action=getlog&moduletype=\" + moduleType + \"&modulename=\" + moduleName,\n        function( data ) {\n            jQuery(\"#modalLogView .modal-body\").html(data);\n        });\n}\n";
$token = generate_token("plain");
$jQueryCode = "jQuery(\".toggle-switch\").bootstrapSwitch();\njQuery(\".app-toggle-switch\").bootstrapSwitch(\n    {\n        'onColor': 'success',\n        'onSwitchChange': function(event, state)\n        {\n            var moduleName;\n            var regex;\n            var match;\n\n            // Will be in the form 'type_module'\n            regex = /^[^_]+_(.+)\$/;\n            match = regex.exec(this.name);\n            moduleName = match[1];\n\n            // ucfirst on the module name;\n            moduleName = moduleName.charAt(0).toUpperCase() + moduleName.substring(1).toLowerCase();\n\n            if (state) {\n                jQuery(\".app-link-status\").addClass(\"hidden\");\n                jQuery(\"#status\" + moduleName + \"Initialising\").removeClass(\"hidden\");\n            } else {\n                jQuery(\".app-link-status\").addClass(\"hidden\");\n                jQuery(\"#status\" + moduleName + \"Disabling\").removeClass(\"hidden\");\n            }\n            jQuery(\"#btn\" + moduleName + \"Configure\").addClass(\"disabled\");\n            jQuery(\"#btn\" + moduleName + \"ViewLog\").hide();\n\n            WHMCS.http.jqClient.post(\n                'configapplinks.php',\n                {\n                    action: 'toggle',\n                    state: state,\n                    module: event.target['name'],\n                    token: '" . $token . "'\n                },\n                function(data) {\n                    if (state) {\n                        jQuery(\".app-link-status\").addClass(\"hidden\");\n                        jQuery(\"#status\" + moduleName + \"Active\").removeClass(\"hidden\");\n                    } else {\n                        jQuery(\".app-link-status\").addClass(\"hidden\");\n                        jQuery(\"#status\" + moduleName + \"Disabled\").removeClass(\"hidden\");\n                    }\n                    jQuery(\"#btn\" + moduleName + \"Configure\").removeClass(\"disabled\");\n                    jQuery(\"#btn\" + moduleName + \"ViewLogWarningCount\").html(data);\n                    jQuery(\"#btn\" + moduleName + \"ViewLog\").fadeIn();\n                }\n            );\n        }\n    }\n);";
$jQueryCode .= "\n\$(\".applink-links tbody\").tableDnD({\n    dragHandle: \"sortcol\"\n});\njQuery(\"#modalLogView\").on(\"hidden.bs.modal\", function () {\n    jQuery(\"#modalLogView .modal-body\").html(\"" . AdminLang::trans("global.loading") . "\");\n});\n";
$aInt->content = $content;
$aInt->jquerycode = $jQueryCode;
$aInt->jscode = $jsCode;
$aInt->display();

?>