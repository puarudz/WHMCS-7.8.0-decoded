<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$this->layout("layouts/admin-content");
$this->start("body");
echo "\n<div class=\"redirect-msg\">\nYou are now being redirected to the WHMCS Marketplace.<br>\nWhen you are finished, simply close this tab to return to WHMCS.<br><br>\n<small>If you are not automatically redirected within 5 seconds, please <a href=\"#\">click here</a></small>\n</div>\n\n<script>\n\$(document).ready(function() {\n    WHMCS.http.jqClient.post('', 'action=doSsoRedirect&destination=";
echo $ssoDestination;
echo "', function(data) {\n        window.location = data.redirectUrl;\n    }, 'json');\n});\n</script>\n";
$this->end();

?>