<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
function google_analytics_config()
{
    $configarray = array("name" => "Google Analytics", "description" => "This module provides a quick and easy way to integrate full Google Analytics tracking into your WHMCS installation", "version" => "2.0", "author" => "WHMCS", "fields" => array("analytics_version" => array("FriendlyName" => "Analytics Version", "Type" => "radio", "Options" => "Google Analytics,Universal Analytics", "Description" => "<a href='https://support.google.com/analytics/answer/2790010' target='_blank'>More Info</a>"), "code" => array("FriendlyName" => "Tracking Code", "Type" => "text", "Size" => "25", "Description" => "Format: UA-XXXXXXXX-X"), "domain" => array("FriendlyName" => "Tracking Domain", "Type" => "text", "Size" => "25", "Description" => "(Optional) Format: yourdomain.com")));
    return $configarray;
}
function google_analytics_output($vars)
{
    echo "<br /><br />\n<p align=\"center\"><input type=\"button\" value=\"Launch Google Analytics Website\" onclick=\"window.open('http://www.google.com/analytics/','ganalytics');\" class=\"btn btn-primary btn-lg\" /></p>\n<br /><br />\n<p>Configuration of the Google Analytics Addon is done via <a href=\"configaddonmods.php\"><b>Setup > Addon Modules</b></a>. Please also ensure your active client area footer.tpl template file includes the {\$footeroutput} template tag.</p>";
}

?>