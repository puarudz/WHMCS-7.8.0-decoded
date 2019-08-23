<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div class=\"apps active\">\n    ";
$hasActiveApps = false;
foreach ($apps->active() as $app) {
    $this->insert("apps/shared/app", array("app" => $app));
    $hasActiveApps = true;
}
echo "    ";
if (!$hasActiveApps) {
    echo "        <div class=\"no-active-apps\">\n            <span>";
    echo AdminLang::trans("apps.noActiveApps");
    echo "</span>\n            <br><br>\n            ";
    echo AdminLang::trans("apps.description");
    echo "            <br>\n            ";
    echo AdminLang::trans("apps.activateToGetStarted");
    echo "            <br>\n            <a href=\"#\" class=\"btn btn-default btn-lg\" onclick=\"\$('#tabBrowse').click();\">Browse Apps</a>\n        </div>\n    ";
}
echo "</div>\n";

?>