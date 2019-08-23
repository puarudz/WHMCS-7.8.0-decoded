<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>";
echo Lang::trans("twofadisableintro");
echo "</p>\n\n";
if ($errorMsg) {
    echo "    <div class=\"alert alert-danger\">\n        ";
    echo $errorMsg;
    echo "    </div>\n";
}
echo "\n<form onsubmit=\"dialogSubmit();return false\" class=\"form-horizontal\" action=\"";
echo routePath(($isAdmin ? "admin-" : "") . "account-security-two-factor-disable-confirm");
echo "\">\n    ";
echo generate_token("form");
echo "    <div class=\"form-group\">\n        <label for=\"inputPasswordVerify\" class=\"col-sm-4 control-label\">\n            ";
echo Lang::trans("twofaconfirmpw");
echo "        </label>\n        <div class=\"col-sm-6\">\n            <input type=\"password\" autocomplete=\"off\" name=\"pwverify\" id=\"inputPasswordVerify\" value=\"\" class=\"form-control\" autofocus>\n        </div>\n    </div>\n    <div class=\"form-group text-center\">\n        <input type=\"submit\" value=\"";
echo Lang::trans("twofadisable");
echo "\" class=\"btn btn-danger\">\n    </div>\n</form>\n";

?>