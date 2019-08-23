<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<table class=\"table table-bordered table-striped\">\n    <tr>\n        <th>";
echo AdminLang::trans("fields.datetime");
echo "</th>\n        <th>";
echo AdminLang::trans("fields.action");
echo "</th>\n        <th>";
echo AdminLang::trans("fields.ipaddress");
echo "</th>\n    </tr>\n    ";
if (0 < $consentHistory->count()) {
    echo "        ";
    foreach ($consentHistory->get() as $consent) {
        echo "            <tr>\n                <td>";
        echo $consent->createdAt->toAdminDateTimeFormat();
        echo "</td>\n                <td>";
        echo $consent->optIn ? AdminLang::trans("marketingConsent.optIn") : AdminLang::trans("marketingConsent.optOut");
        echo $consent->admin ? " " . AdminLang::trans("marketingConsent.byAdminUser") : "";
        echo "</td>\n                <td>";
        echo $consent->ipAddress;
        echo "</td>\n            </tr>\n        ";
    }
    echo "    ";
} else {
    echo "        <tr>\n            <td colspan=\"3\" class=\"text-center\">";
    echo AdminLang::trans("global.norecordsfound");
    echo "</td>\n        </tr>\n    ";
}
echo "</table>\n";

?>