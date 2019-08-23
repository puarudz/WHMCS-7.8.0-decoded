<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<h3>Other Settings</h3>\n\n<div class=\"promotions\">\n    <div class=\"row\">\n        ";
foreach ($generalSettings as $setting) {
    echo "            <div class=\"col-sm-12\">\n                <div class=\"promo\">\n                    <h4>\n                        ";
    echo $setting["label"];
    echo "                        <input type=\"checkbox\" class=\"setting-switch\" data-name=\"";
    echo $setting["name"];
    echo "\" data-service=\"";
    echo $serviceOffering["vendorSystemName"];
    echo "\"";
    echo $service->setting("general." . $setting["name"]) || is_null($service->setting("general." . $setting["name"])) && $setting["default"] ? " checked" : "";
    echo ">\n                    </h4>\n                    <p>";
    echo $setting["description"];
    echo "</p>\n                </div>\n            </div>\n        ";
}
echo "    </div>\n</div>\n\n";

?>