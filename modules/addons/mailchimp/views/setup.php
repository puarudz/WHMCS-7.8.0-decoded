<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($errorMsg) {
    echo "    <div class=\"alert alert-danger\">\n        ";
    echo $errorMsg;
    echo "    </div>\n";
}
echo "\n<div class=\"form-group\">\n    <label for=\"inputApiKey\">API Integration Key</label>\n    <input type=\"text\" name=\"api_key\" class=\"form-control\" id=\"inputApiKey\" placeholder=\"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us01\">\n    <p class=\"help-block\">Navigate to Account > Extras > API Keys. We recommend creating a new API Key for WHMCS to use.</p>\n</div>\n\n<div class=\"alert alert-warning\">\n    New to MailChimp?\n    <a href=\"https://go.whmcs.com/1297/mailchimp-create-account\" target=\"_blank\" class=\"alert-link\">Create a free account</a>\n</div>\n\n<p>\n    <button type=\"submit\" class=\"btn btn-primary\">\n        Validate API Key\n    </button>\n</p>\n\n<input type=\"hidden\" name=\"action\" value=\"validateapikey\">\n";

?>