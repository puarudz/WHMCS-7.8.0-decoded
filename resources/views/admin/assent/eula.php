<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$this->layout("layouts/minimal");
$this->start("body");
echo "<p>The End User License Agreement displayed below provides information about\n  the terms of use for this software. You must review and agree to the license\n  terms before continuing to use WHMCS.</p>\n<textarea\n    id=\"eulaText\"\n    class=\"eula\"\n    rows=\"25\" readonly >\n";
echo $eulaText . "Updated " . $effectiveDate;
echo "</textarea>\n";
$this->end();
if (empty($readOnly)) {
    $this->start("actionButtons");
    echo "<div style=\"margin:10px 0;padding:10px 20px;color:#a94442;background-color:#f2dede;\n  border-color:#ebccd1;text-align:center;display:none;\" id=\"msgEulaDisagree\">\n  You must agree to the End User License Agreement in order to continue.\n</div>\n<form class=\"form-horizontal\"\n      name=\"frmActivateLicense\"\n      id=\"frmActivateLicense\"\n      method=\"post\"\n      action=\"";
    echo routePath("admin-eula-accept");
    echo "\">\n    <input type=\"hidden\" value=\"1\" name=\"eulaAccepted\"/>\n    <div class=\"btn-container\">\n        <button type=\"submit\"\n           class=\"btn btn-success\"\n           id=\"btnEulaAgree\"\n           name=\"btnEulaAgree\"\n           aria-label=\"I AGREE\"\n           aria-controls=\"eulaText\">\n           <i class=\"fas fa-check fa-fw\"></i>\n           I AGREE\n        </button>\n        <button type=\"button\"\n           class=\"btn btn-default\"\n           id=\"btnEulaDisagree\"\n           name=\"btnEulaDisagree\"\n           aria-controls=\"eulaText\">\n           <i class=\"fas fa-times fa-fw\"></i>\n           I DISAGREE\n        </button>\n    </div>\n</form>\n<script>\n    jQuery(document).ready(function(){\n        jQuery('#btnEulaDisagree').on('click', function(event){\n           event.preventDefault();\n           jQuery('#msgEulaDisagree').fadeIn();\n        });\n    });\n</script>\n    ";
    $this->end();
}

?>