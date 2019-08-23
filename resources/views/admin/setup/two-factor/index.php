<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($saveSuccess) {
    echo "    <div class=\"alert alert-success\">\n        Global settings saved successfully!\n    </div>\n";
}
echo "\n<p>The following services are supported for Two-Factor Authentication. You may activate one or more of these.</p>\n\n<div class=\"signin-apps-container\">\n    <div class=\"row\">\n        ";
foreach ($modules as $module) {
    echo "            <div class=\"col-sm-6 col-md-4\">\n                <div class=\"app\">\n                    <div class=\"logo-container\">\n                        <img src=\"";
    echo WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/modules/security/" . $module["name"];
    echo "/logo.png\">\n                    </div>\n                    <h2>";
    echo $module["friendlyName"];
    echo "</h2>\n                    <p>";
    echo $module["description"];
    echo "</p>\n                    <a href=\"";
    echo routePath("admin-setup-auth-two-factor-configure", $module["name"]);
    echo "\" id=\"btnConfigure-";
    echo $module["name"];
    echo "\" class=\"btn btn-";
    echo $module["active"] ? "default" : "success";
    echo " open-modal\" data-modal-title=\"Configure ";
    echo $module["friendlyName"];
    echo "\" data-btn-submit-id=\"";
    echo $module["name"];
    echo "Save\" data-btn-submit-label=\"Save\" data-modal-class=\"configure-twofa\">";
    echo $module["active"] ? "Configure" : "Activate";
    echo "</a>\n                </div>\n            </div>\n        ";
}
echo "    </div>\n</div>\n\n<strong>What is Two-Factor Authentication?</strong><br /><br />\n\n<p>Two-factor authentication adds an additional layer of security by adding a second step to the login process. It takes something you know (ie. your password) and adds a second factor, typically something you have (such as your phone). Since both are required to log in, the threat of a leaked password is lessened.</p>\n<p>One of the most common and simplest forms of Two-Factor Authentication is Time Based Tokens. With Time Based Tokens, in addition to your regular username & password, you also have to enter a 6 digit code that re-generates every 30 seconds. Only your token device (typically a mobile smartphone app) will know your secret key and be able to generate valid one time passwords for your account. <strong><em>We recommend enabling Time Based Tokens (also enabled by default).</em></strong></p>\n\n<form method=\"post\" action=\"";
echo routePath("admin-setup-auth-two-factor-settings-save");
echo "\">\n    <div class=\"well\">\n        <strong>Global Two-Factor Authentication Settings</strong><br /><br />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"forceclient\"";
if ($globalSettings["forceClients"]) {
    echo " checked";
}
echo ">\n            Force Client Users to enable Two Factor Authentication on Next Login\n        </label>\n        <br>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"forceadmin\"";
if ($globalSettings["forceAdmins"]) {
    echo " checked";
}
echo ">\n            Force Administrative Users to enable Two Factor Authentication on Next Login\n        </label>\n        <br><br>\n        <button type=\"submit\" class=\"btn btn-default\">\n            ";
echo AdminLang::trans("global.savechanges");
echo "        </button>\n    </div>\n</form>\n\n<script>\n    jQuery(document).ready(function() {\n        jQuery('#modalAjax').on('hide.bs.modal', function (event) {\n            WHMCS.http.jqClient.get('";
echo routePath("admin-setup-auth-two-factor-status");
echo "', function(response) {\n                jQuery.each(response, function (module, enabled) {\n                    if (enabled) {\n                        jQuery('#btnConfigure-' + module).removeClass('btn-success').addClass('btn-default').html('Configure');\n                    } else {\n                        jQuery('#btnConfigure-' + module).addClass('btn-success').removeClass('btn-default').html('Activate');\n                    }\n                });\n            }, 'json');\n        });\n\n        ";
if ($moduleToConfigure) {
    echo "            jQuery('#btnConfigure-";
    echo $moduleToConfigure;
    echo "').click();\n        ";
}
echo "    });\n</script>\n";

?>