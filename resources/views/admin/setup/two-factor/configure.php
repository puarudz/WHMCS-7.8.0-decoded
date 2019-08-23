<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>";
echo $configuration["Description"]["Value"];
echo "</p>\n\n<form method=\"post\" action=\"";
echo routePath("admin-setup-auth-two-factor-configure-save", $module);
echo "\">\n\n    <h2>Status</h2>\n\n    <div class=\"form-group\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"clientenabled\" value=\"1\"";
if ($isEnabledForClients) {
    echo " checked";
}
echo ">\n            Enable for use by Clients\n        </label><br>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"adminenabled\" value=\"1\"";
if ($isEnabledForAdmins) {
    echo " checked";
}
echo ">\n            Enable for use by Administrative Users\n        </label>\n    </div>\n\n    <h2>Configuration Settings</h2>\n\n    ";
if (0 < count($settingFields)) {
    echo "        ";
    foreach ($settingFields as $fieldName => $field) {
        echo "            <div class=\"form-group\">\n                <label for=\"inputX\">";
        echo $fieldName;
        echo "</label><br>\n                ";
        echo $field;
        echo "            </div>\n        ";
    }
    echo "    ";
} else {
    echo "        <p>No configuration required.</p>\n    ";
}
echo "\n</form>\n";

?>