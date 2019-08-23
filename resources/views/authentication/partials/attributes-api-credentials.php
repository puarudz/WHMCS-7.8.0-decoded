<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div class=\"form-group\">\n    <label for=\"inputDescription\">";
echo AdminLang::trans("global.description");
echo "</label>\n    <input type=\"text\" class=\"form-control\" id=\"inputDescription\" name=\"description\" placeholder=\"";
echo AdminLang::trans("global.description");
echo "\" value=\"";
echo $device->description;
echo "\">\n</div>\n<div class=\"form-group\">\n    <label for=\"selectRoles\">";
echo AdminLang::trans("apicreds.apiRoles");
echo "</label>\n    <select multiple class=\"form-control\" id=\"selectRoles\" name=\"roleIds[]\">\n        ";
if (!empty($roles)) {
    if (isset($device)) {
        $currentRoles = $device->rolesCollection();
    } else {
        $currentRoles = array();
    }
    foreach ($roles as $role) {
        echo sprintf("<option value=\"%s\" %s>%s</option>", $role->id, array_key_exists($role->id, $currentRoles) ? "selected" : "", $role->role);
    }
} else {
    echo sprintf("<option value=\"\" disabled>%s</option>", AdminLang::trans("apirole.noRolesDefined"));
}
echo "    </select>\n    <p class=\"help-block\">\n        ";
echo AdminLang::trans("apicreds.roleSelectionHelper");
echo "    </p>\n</div>\n";

?>