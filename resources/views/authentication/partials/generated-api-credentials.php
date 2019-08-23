<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div id=\"createCredentialsSuccess\">\n    <p>\n        ";
echo AdminLang::trans("apicreds.credSuccessSummary");
echo "        <span class=\"alert-warning\">";
echo AdminLang::trans("apicreds.mustCopySecret");
echo "            </span>\n    </p>\n    <div class=\"form-group\">\n        <label for=\"inputDeviceIdentifier\">";
echo AdminLang::trans("apicreds.identifier");
echo "</label>\n        <div class=\"input-group\">\n            <input id=\"inputDeviceIdentifier\" name=\"inputDeviceIdentifier\" value=\"";
echo $identifier;
echo "\" class=\"form-control\" />\n            <span class=\"input-group-btn\"><button class=\"btn btn-default copy-to-clipboard\" data-clipboard-target=\"#inputDeviceIdentifier\" type=\"button\"><img src=\"../assets/img/clippy.svg\" alt=\"Copy to clipboard\" width=\"15\"></button></span>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputDeviceIdentifier\">";
echo AdminLang::trans("apicreds.secret");
echo "</label>\n        <div class=\"input-group\">\n            <input id=\"inputDeviceSecret\" name=\"inputDeviceSecret\" value=\"";
echo $secret;
echo "\" class=\"form-control\" />\n            <span class=\"input-group-btn\"><button class=\"btn btn-default copy-to-clipboard\" data-clipboard-target=\"#inputDeviceSecret\" type=\"button\"><img src=\"../assets/img/clippy.svg\" alt=\"Copy to clipboard\" width=\"15\"></button></span>\n        </div>\n    </div>\n</div>\n<script>\n    jQuery(document).ready(function() {\n        WHMCS.ui.dataTable.getTableById('tblDevice').ajax.reload();\n        jQuery('#NewAPICredentials-Generate').hide();\n    });\n</script>\n";

?>