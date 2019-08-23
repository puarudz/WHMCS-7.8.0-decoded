<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\General\UriManagement\View\Helper;

class SimpleSetting
{
    public function getSimpleSettingHtmlPartial()
    {
        $overrideSettingName = \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController::SETTING_MODE_OVERRIDE;
        $storedOverrideStatus = \WHMCS\Config\Setting::getValue($overrideSettingName);
        $response = (new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController())->remoteDetectEnvironmentMode(new \WHMCS\Http\Message\ServerRequest());
        $testedModeValue = $response->getRawData();
        $testedModeValue = $testedModeValue["data"]["mode"];
        $uriPath = new \WHMCS\Route\UriPath();
        $uriPathModeHtml = "";
        $storedMode = $uriPath->getMode();
        if ($storedOverrideStatus) {
            $effectiveMode = $storedMode;
        } else {
            $effectiveMode = $testedModeValue;
        }
        $validModes = array();
        foreach ($uriPath::getAllKnownModes() as $option) {
            switch ($option) {
                case \WHMCS\Route\UriPath::MODE_REWRITE:
                    $validModes[$option] = \AdminLang::trans("uriPathMgmt.btnModeRewrite");
                    break;
                case \WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO:
                    $validModes[$option] = \AdminLang::trans("uriPathMgmt.btnModeAcceptPathInfo");
                    break;
                default:
                    $validModes[$option] = \AdminLang::trans("uriPathMgmt.btnModeBasic");
            }
        }
        $selectedMode = isset($validModes[$effectiveMode]) ? $validModes[$effectiveMode] : $validModes[\WHMCS\Route\UriPath::MODE_BASIC];
        foreach ($validModes as $mode => $modeName) {
            $classes = $mode == $testedModeValue ? "bg-success" : "bg-info";
            if ($modeName == $selectedMode) {
                $classes .= " current";
            }
            $uriPathModeHtml .= sprintf("<li class=\"%s\"><a href=\"#%s\">%s</a></li>", $classes, $mode, $modeName);
        }
        $csrfToken = generate_token("plain");
        $html = "\n            <script>\n                jQuery(document).ready(function(){\n                    \n                    function updateButtonSelected(buttonSelected) {\n                        buttonSelected.parents(\".btn-group\").find(\".selection\").text(buttonSelected.text());\n                        jQuery(\".uriPathMgmt li\").removeClass(\"current\");\n                        buttonSelected.closest(\"li\").addClass(\"current\");\n                    }\n                    \n                    function resetDetectModeBgColor(buttonSelected) {\n                        var allButtonsLi = jQuery(\".uriPathMgmt li\");\n                        allButtonsLi.removeClass(\"bg-success\");\n                        allButtonsLi.addClass(\"bg-info\");\n                        \n                        var buttonLi = buttonSelected.closest(\"li\");\n                        buttonLi.removeClass(\"bg-info\");\n                        buttonLi.addClass(\"bg-success\");\n                    }\n                    \n                    function updateLabelModeOverride(buttonSelected) {\n                        var override = buttonSelected.closest(\"li\").hasClass(\"bg-success\") ? 0 : 1;\n                        var label = jQuery(\"#labelUriModeOverrideStatus\");\n                        label.removeClass(\"label-info label-success\");\n                        if (override) {\n                            label.addClass(\"label-info\");\n                        } else {\n                            label.addClass(\"label-success\");\n                        }\n                    }\n                    \n                    /** User selects from button dropdown **/\n                    jQuery(\".uriPathMgmt li a\").click(function(e) {\n                        e.preventDefault();\n                        var buttonSelected = jQuery(this);\n                        var buttonValue = buttonSelected.attr(\"href\");\n                        var newMode = buttonValue.substring(1);\n                        var override = buttonSelected.closest(\"li\").hasClass(\"bg-success\") ? 0 : 1;\n                         WHMCS.http.jqClient.post(\n                            \"configurimgmt.php\",\n                            {\n                                action: \"updateUriPathMode\",\n                                mode: newMode,\n                                setOverride: override,\n                                token: \"" . $csrfToken . "\"\n                            }\n                        )\n                        .done(function(data){\n                            if (data.status == \"okay\") {\n                                updateButtonSelected(buttonSelected);\n                                updateLabelModeOverride(buttonSelected);\n                                jQuery.growl.notice({ title: \"Setting Updated\", message: data.successMessage });\n                            } else {\n                                jQuery.growl.error({ title: \"Error\", message: data.errorMessage });\n                            }\n                        });\n                    });\n                    \n                    /** User selects refresh/sync **/\n                    jQuery(\"#setUriModeToBest\").click(function(e) {\n                        var resetDone = function (data) {\n                            swal({\n                                    title: \"Done!\",\n                                    html: true,\n                                    text: data.successMessageHtml,\n                                    type: \"success\"\n                                });\n                                var mode = data.data.mode;\n                                var buttonToSelect = \"\";\n                                jQuery(\".uriPathMgmt li a\").each(function (i, e) {\n                                    if (jQuery(e).attr(\"href\") == \"#\" + mode) {\n                                        buttonToSelect = jQuery(e);\n                                    }\n                                });\n                                if (buttonToSelect) {\n                                    updateButtonSelected(buttonToSelect);\n                                    resetDetectModeBgColor(buttonToSelect);\n                                    updateLabelModeOverride(buttonToSelect);\n                                }\n                        };\n                        var refreshIcon = jQuery(\"#setUriModeToBest i\");\n                        e.preventDefault();\n                        refreshIcon.addClass(\"fa-spin\");\n                        \n                         WHMCS.http.jqClient.post(\n                            \"configurimgmt.php\",\n                            {\n                                action: \"applyBestConfiguration\",\n                                token: \"" . $csrfToken . "\"\n                            }\n                        )\n                        .done(function(data) {\n                            if (data.status == \"okay\") {\n                                 resetDone(data);\n                            } else if (data.status == \"prompt\") {\n                                swal({\n                                    title: data.promptTitle,\n                                    html: true,\n                                    text: data.promptMessage,\n                                    type: \"info\",\n                                    showCancelButton: true,\n                                    confirmButtonText: \"Enable\",\n                                    closeOnConfirm: false,\n                                    showLoaderOnConfirm: true\n                                    },\n                                    function(){\n                                        WHMCS.http.jqClient.post(\n                                            \"configurimgmt.php\",\n                                            {\n                                                enableRewriteMgmt: true,\n                                                action: \"applyBestConfiguration\",\n                                                token: \"" . $csrfToken . "\"\n                                            }\n                                        )\n                                        .done(function(data){\n                                            if (data.status == \"okay\") {\n                                                resetDone(data);\n                                            }\n                                        });\n                                    }\n                                );\n                            } else if (data.status == \"error\") {\n                                swal(\"Error\", data.errorMessage, \"error\");\n                            } else {\n                                swal(\"Oops!\", \"Oops! There's a problem\", \"error\");\n                            }\n                            refreshIcon.removeClass(\"fa-spin\");\n                        })\n                        .fail(function () {\n                            refreshIcon.removeClass(\"fa-spin\");\n                            swal(\"Oops!\", \"Oops! There's a problem\", \"error\");\n                        });                    \n                    });\n                });\n            </script>\n            <style>\n            #btnGroupUriSimpleModeDropdown li.current a::before {\n                font-family: \"Font Awesome 5 Pro\";\n                padding: 0 3px;\n                margin-left: -4px;\n                content: \"\\f00c\";\n            }\n            #labelUriModeOverrideStatus.label-info span::before {\n                content: \"" . \AdminLang::trans("uriPathMgmt.labelManualOverride") . "\"; \n            }\n            #labelUriModeOverrideStatus.label-success span::before {\n                content: \"" . \AdminLang::trans("uriPathMgmt.labelSystemDetected") . "\"; \n            }\n            </style>\n            <div id=\"btnGroupUriSimpleModeDropdown\" class=\"btn-group dropdown\">\n                <button id=\"btnUriSimpleModeDropdown\"\n                    type=\"button\" \n                    class=\"btn btn-default dropdown-toggle\" \n                    data-toggle=\"dropdown\" \n                    aria-expanded=\"false\" \n                    aria-haspopup=\"true\">\n                    <span class=\"selection\">\n                    " . $selectedMode . "\n                    </span>\n                    &nbsp;&nbsp;\n                    <span class=\"caret\"></span>\n                </button>\n                <ul class=\"uriPathMgmt dropdown-menu\" aria-labelledby=\"btnUriSimpleModeDropdown\">" . $uriPathModeHtml . "</ul>\n            </div>\n            <label id=\"labelUriModeOverrideStatus\" class=\"label label-" . ($storedOverrideStatus ? "info" : "success") . "\"><span></span></label>\n            <a id=\"setUriModeToBest\" class=\"btn btn-xs btn-default\">\n                <i class=\"fas fa-sync\"></i>\n            </a>\n            &nbsp;\n            <a id=\"btnUriPathManagementConfigure\" \n                href=\"configurimgmt.php?action=modal-view\" \n                data-modal-title=\"URI Path Management\" \n                onclick=\"return false;\" \n                class=\"open-modal pull-right btn btn-link\">\n                <small><u>" . \AdminLang::trans("automation.advsettings") . "</u></small>\n            </a>\n            ";
        return $html;
    }
    public function resetSuccessHtml(array $successMessages)
    {
        foreach ($successMessages as $key => $message) {
            if (!$message) {
                unset($successMessages[$key]);
                continue;
            }
            $successMessages[$key] = sprintf("<li>%s</li>", $message);
        }
        $checkIcon = "\\f00c";
        return sprintf("<style>\n                    .uri-check-container li {\n                      list-style: none;\n                    }\n                    .uri-check-container li:before {\n                      font-family: 'Font Awesome\\ 5 Pro';\n                      content: '%s';\n                      float: left;\n                      margin-left: -1.5em;\n                      color: #5cb85c;\n                    }\n                </style>\n                <div class=\"container-fluid uri-check-container\">\n                    <ul>%s</ul>\n                </div>", $checkIcon, implode("\n", $successMessages));
    }
}

?>