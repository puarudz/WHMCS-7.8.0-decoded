<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$providerName = $provider["name"];
$displayName = $provider["displayName"];
$description = $provider["description"];
$configurationIntro = $provider["configurationIntro"];
$providerFields = $provider["fields"];
$inputs = array();
$activateBtn = "";
$resetClass = "hidden";
$submitClass = "";
$submitLabel = AdminLang::trans("global.saveAndActivate");
foreach ($providerFields as $field) {
    $fieldName = $field["name"];
    $fieldValue = isset($field["value"]) ? $field["value"] : "";
    $fieldType = isset($field["type"]) ? $field["type"] : "text";
    $inputId = $providerName . "_" . $fieldName;
    $attrValue = sprintf("value=\"%s\"", $fieldValue);
    if ($fieldType == "checkbox") {
        $attrValue = $fieldValue ? "checked" : "";
    }
    if (strcasecmp($fieldName, "enabled") === 0) {
        $class = $fieldValue ? "btn-primary" : "btn-success";
        $label = $fieldValue ? AdminLang::trans("home.manage") : AdminLang::trans("global.activate");
        $activateBtn = sprintf("<button id=\"btnProviderModal_%s\" class=\"btn %s\" data-toggle=\"modal\" data-target=\"#integration-%s\">%s</button>", $providerName, $class, $providerName, $label);
        $resetClass = $fieldValue ? "" : "hidden";
        $submitLabel = $fieldValue ? AdminLang::trans("global.save") : AdminLang::trans("global.saveAndActivate");
    } else {
        $inputs[] = sprintf("<div class=\"form-group\">" . "<label for=\"%s\">%s</label>" . "<input class=\"form-control\" type=\"%s\" name=\"%s\" id=\"%s\" %s />" . "</div>", $inputId, $fieldName, $fieldType, $inputId, $inputId, $attrValue);
    }
}
echo "\n<div class=\"col-sm-6 col-md-4\">\n    <div class=\"app\">\n        <div class=\"logo-container\">\n            <img src=\"../assets/img/auth/";
echo $providerName;
echo ".png\" class=\"provider-logo-";
echo $providerName;
echo "\">\n        </div>\n        <p>";
echo $description;
echo "</p>\n        ";
echo $activateBtn;
echo "    </div>\n</div>\n\n<form name=\"frmRemoteProviderSetting";
echo ucfirst($providerName);
echo "\" method=\"post\" class=\"integration-provider\" data-provider=\"";
echo $providerName;
echo "\">\n    <input type=\"hidden\" name=\"provider\" value=\"";
echo $providerName;
echo "\" />\n    <div class=\"modal whmcs-modal modal-integration-provider fade\" id=\"integration-";
echo $providerName;
echo "\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"integration-";
echo $providerName;
echo "-label\">\n        <div class=\"modal-dialog\" role=\"document\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\" id=\"integration-";
echo $providerName;
echo "-label\">\n                        ";
echo $displayName;
echo "                    </h4>\n                </div>\n                <div class=\"modal-body\">\n\n                    <p>\n                        ";
echo $configurationIntro;
echo "                        ";
echo sprintf(AdminLang::trans("signIn.docLink"), $displayName);
echo "                    </p>\n\n                    <div class=\"alert-container\">\n                        <div class=\"alert alert-danger hidden\" role=\"alert\">\n                            ";
echo AdminLang::trans("signIn.invalidDetails");
echo "                        </div>\n                    </div>\n\n                    ";
echo implode("\n", $inputs);
echo "\n                </div>\n                <div class=\"modal-footer\">\n                    <a href=\"https://docs.whmcs.com/Configuring_Sign-In_using_";
echo $displayName;
echo "\" target=\"_blank\" class=\"btn btn-info pull-left\">\n                        ";
echo AdminLang::trans("help.contextlink");
echo "                    </a>\n                    <button type=\"button\" class=\"btn btn-default pull-left\" data-dismiss=\"modal\" aria-label=\"Cancel\">\n                        ";
echo AdminLang::trans("global.cancel");
echo "                    </button>\n                    <button type=\"reset\" class=\"";
echo $resetClass;
echo " btn btn-danger\">\n                        ";
echo AdminLang::trans("global.deactivate");
echo "                    </button>\n                    <button type=\"submit\" class=\"btn btn-primary\">\n                        ";
echo $submitLabel;
echo "                    </button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n";

?>