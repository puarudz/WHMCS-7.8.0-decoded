<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "\n<style>\n    .provider-config-field {\n        margin: 0 0 20px 0;\n    }\n    .provider-config-field strong {\n        display: block;\n        margin: 5px 0;\n    }\n    .provider-config-field input {\n        display: block;\n        margin: 5px 0;\n        max-width: 100%;\n        width: 100%;\n    }\n</style>\n\n<script type=\"application/javascript\">\n(function(\$, window) {\n    \$(document).ready(function() {\n        var form = \$('.provider-config-form').first();\n\n        if (\$(form).find('input:not([type=hidden]):not(:disabled)').length === 0) {\n            \$(form).parents('.modal').find('.modal-submit').hide();\n        }\n\n        \$(form).on('submit', function (e) {\n            e.preventDefault();\n            \$('#btnSaveStorageConfiguration').trigger('click');\n        });\n    });\n})(jQuery, window);\n</script>\n\n<form method=\"post\" class=\"provider-config-form\" action=\"";
echo routePath("admin-setup-storage-save-configuration", $id);
echo "\">\n    ";
echo generate_token();
echo "    <input type=\"hidden\" name=\"provider\" value=\"";
echo $provider->getShortName();
echo "\" />\n    <input type=\"hidden\" name=\"duplicate_configuration_id\" value=\"";
echo $duplicate_configuration_id;
echo "\" />\n\n    ";
if ($errorMsg) {
    echo "        <div class=\"alert alert-danger\">\n            ";
    echo $errorMsg;
    echo "        </div>\n    ";
}
echo "\n    ";
if ($inUse) {
    echo "        <div class=\"alert alert-info\">\n            ";
    echo AdminLang::trans("storage.inUseConfigChangeRestricted");
    echo "        </div>\n    ";
}
echo "\n    <div class=\"row\">\n        ";
$fields = $provider->getConfigurationFields();
$index = 0;
foreach ($fields as $field) {
    echo "\n            <div class=\"";
    echo ++$index < count($fields) ? "col-md-6" : "col-md-12";
    echo " provider-config-field\">\n                <strong>";
    echo $field["FriendlyName"];
    echo "</strong>\n                ";
    echo moduleConfigFieldOutput(array_merge($field, array("Name" => "settings[" . $field["Name"] . "]", "Value" => isset($settings[$field["Name"]]) ? $settings[$field["Name"]] : NULL, "Disabled" => $inUse && in_array($field["Name"], $provider->getFieldsLockedInUse()))));
    echo "            </div>\n\n        ";
}
echo "    </div>\n</form>\n";

?>