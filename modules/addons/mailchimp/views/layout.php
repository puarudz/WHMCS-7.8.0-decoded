<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<style>\n.mailchimp .wrapper {\n    margin: 10px auto;\n    padding: 30px 50px;\n    border: 1px solid #ccc;\n    border-radius: 4px;\n    max-width: 650px;\n}\n@media (min-width:1000px) {\n    .mailchimp .wrapper {\n        margin: 100px auto;\n    }\n}\n.mailchimp h3 {\n    font-size: 1.6em;\n}\n.mailchimp .logo {\n    margin-bottom: 30px;\n    max-width: 100%;\n}\n.mailchimp .nav {\n    margin: 30px 0 0 0;\n    padding: 20px 0 0 0;\n    border-top: 1px solid #ddd;\n}\n.mailchimp .nav .btn {\n    margin-bottom: 2px;\n}\n.mailchimp .form-group-company input {\n    margin-bottom: 3px;\n}\n.mailchimp a:not(.btn) {\n    text-decoration: underline;\n}\n</style>\n\n<div class=\"mailchimp\">\n    <div class=\"wrapper\">\n\n        <p>\n            <a href=\"addonmodules.php?module=mailchimp\">\n                <img src=\"../modules/addons/mailchimp/logo.png\" class=\"logo\">\n            </a>\n        </p>\n\n        <form method=\"post\" action=\"addonmodules.php?module=mailchimp\" id=\"frmMailchimp\">\n            ";
echo $content;
echo "        </form>\n\n        ";
if (!in_array($action, array("setup", "chooselist", "sync"))) {
    echo "            <div class=\"nav\">\n                <a href=\"addonmodules.php?module=mailchimp\" class=\"btn btn-default\">\n                    Home\n                </a>\n                <a href=\"addonmodules.php?module=mailchimp&action=settings\" class=\"btn btn-default\">\n                    Manage Settings\n                </a>\n                <a href=\"addonmodules.php?module=mailchimp&action=disconnect\" class=\"btn btn-default\">\n                    Disconnect Integration\n                </a>\n                <a href=\"https://docs.whmcs.com/Mailchimp\" target=\"_blank\" class=\"btn btn-default\">\n                    Help\n                </a>\n            </div>\n        ";
}
echo "    </div>\n</div>\n";

?>