<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!isset($title) || empty($title)) {
    $title = AdminLang::trans("errorPage." . $statusCode . ".title");
}
if (!isset($subtitle) || empty($subtitle)) {
    $subtitle = AdminLang::trans("errorPage." . $statusCode . ".subtitle");
}
if (!isset($description) || empty($description)) {
    $description = AdminLang::trans("errorPage." . $statusCode . ".description");
}
echo "<div class=\"error-page\">\n    <div class=\"error-heading\">\n        <h3 class=\"error-title\">\n            <i class=\"fas fa-exclamation-triangle\"></i> ";
echo AdminLang::trans("errorPage.general.oops");
echo "        </h3>\n    </div>\n    <div class=\"error-body\">\n        <p><strong>";
echo $title;
echo "</strong></p>\n        <p>";
echo $subtitle;
echo "</p>\n        ";
echo $description ? "<p>" . $description . "</p>" : "";
echo "        <br/><p>";
echo AdminLang::trans("errorPage.general.tryOtherNav");
echo "</p>\n    </div>\n    <div class=\"error-footer\">\n        <div class=\"buttons\">\n            <button type=\"button\" class=\"btn btn-default btn-lg\" onclick=\"history.go(-1)\">\n                <i class=\"fas fa-arrow-circle-left\"></i> ";
echo AdminLang::trans("global.goback");
echo "            </button>\n            <a href=\"";
echo routePath("admin-homepage");
echo "\" type=\"button\" class=\"btn btn-default btn-lg\" >\n                <i class=\"fas fa-home\"></i> ";
echo AdminLang::trans("errorPage.general.home");
echo "            </a>\n            ";
if ($statusCode == 500) {
    echo "                <a href=\"https://www.whmcs.com/support/\" class=\"btn btn-default btn-lg\" target=\"_blank\">\n                    <i class=\"fas fa-ticket-alt\"></i> ";
    echo AdminLang::trans("errorPage.general.submitTicket");
    echo "                </a>\n                ";
}
echo "        </div>\n    </div>\n</div>\n";

?>