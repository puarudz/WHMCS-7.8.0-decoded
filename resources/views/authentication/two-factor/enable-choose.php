<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($twoFactorEnforced) {
    echo "    <div class=\"alert alert-warning\">\n        ";
    echo Lang::trans("twofaenforced");
    echo "    </div>\n";
}
echo "\n<p>";
echo Lang::trans("twofaactivationintro");
echo "</p>\n\n<form action=\"";
echo routePath(($isAdmin ? "admin-" : "") . "account-security-two-factor-enable-configure");
echo "\">\n    ";
echo generate_token("form");
echo "    <p>";
echo Lang::trans("twofaactivationmultichoice");
echo "</p>\n    ";
$first = true;
foreach ($modules as $module => $displayName) {
    echo "        <div class=\"twofa-module";
    if ($first) {
        echo " active";
    }
    echo "\">\n            <div class=\"col-radio\">\n                <input type=\"radio\" name=\"module\" value=\"";
    echo $module;
    echo "\"";
    if ($first) {
        echo " checked";
    }
    echo ">\n            </div>\n            <div class=\"col-logo\">\n                <img src=\"";
    echo $webRoot;
    echo "/modules/security/";
    echo $module;
    echo "/logo.png\">\n            </div>\n            <div class=\"col-description\">\n                <strong>";
    echo $displayName;
    echo "</strong><br>\n                ";
    echo $descriptions[$module];
    echo "            </div>\n        </div>\n    ";
    $first = false;
}
echo "    <p align=\"center\">\n        <input type=\"button\" value=\"";
echo Lang::trans("twofasetupgetstarted");
echo " &raquo;\" onclick=\"dialogSubmit()\" class=\"btn btn-primary\" />\n    </p>\n</form>\n\n<script>\n    \$(document).ready(function() {\n        \$('.twofa-module').click(function(e) {\n            \$('.twofa-module').removeClass('active');\n            \$(this).addClass('active').find('input').prop('checked', true);\n        });\n    });\n</script>\n";

?>