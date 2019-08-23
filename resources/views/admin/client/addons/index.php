<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo $tabStart;
echo "<form action=\"";
echo routePath("admin-addons-index");
echo "\" method=\"post\">\n\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
echo AdminLang::trans("fields.addon");
echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"addon\" class=\"form-control select-inline\">\n                    <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                    ";
foreach ($addonsList as $addonReference => $addonName) {
    $selected = "";
    if ($addonReference == $criteria["addon"]) {
        $selected = " selected=\"selected\"";
    }
    echo "<option value=\"" . $addonReference . "\"" . $selected . ">" . $addonName . "</option>";
}
echo "                </select>\n            </td>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
echo AdminLang::trans("fields.producttype");
echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"type\" class=\"form-control select-inline\">\n                    <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                    <option value=\"hostingaccount\"";
echo $criteria["type"] == "hostingaccount" ? " selected" : "";
echo ">\n                        ";
echo AdminLang::trans("orders.sharedhosting");
echo "                    </option>\n                    <option value=\"reselleraccount\"";
echo $criteria["type"] == "reselleraccount" ? " selected" : "";
echo ">\n                        ";
echo AdminLang::trans("orders.resellerhosting");
echo "                    </option>\n                    <option value=\"server\"";
echo $criteria["type"] == "server" ? " selected" : "";
echo ">\n                        ";
echo AdminLang::trans("orders.server");
echo "                    </option>\n                    <option value=\"other\"";
echo $criteria["type"] == "other" ? " selected" : "";
echo ">\n                        ";
echo AdminLang::trans("orders.other");
echo "                    </option>\n                </select>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.product");
echo "</td>\n            <td class=\"fieldarea\">\n                <select name=\"package\" class=\"form-control select-inline\">\n                    ";
echo $products;
echo "                </select>\n            </td>\n            <td class=\"fieldlabel\" width=\"15%\">\n                ";
echo AdminLang::trans("fields.server");
echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"server\" class=\"form-control select-inline\">\n                    <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                    ";
echo $servers;
echo ";\n                </select>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.paymentmethod");
echo "</td>\n            <td class=\"fieldarea\">";
echo $paymentMethods;
echo "</td>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.billingcycle");
echo "</td>\n            <td class=\"fieldarea\">";
echo $cycles;
echo "</td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.status");
echo "</td>\n            <td class=\"fieldarea\">";
echo $statuses;
echo "</td>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.customfield");
echo "</td>\n            <td class=\"fieldarea\">\n                <select name=\"customfield\" class=\"form-control select-inline\">\n                    <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                    ";
echo $customFields;
echo "                </select>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
echo AdminLang::trans("fields.domain");
echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"domain\" class=\"form-control input-250\" value=\"";
echo $criteria["domain"];
echo "\">\n            </td>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.customfieldvalue");
echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"customfieldvalue\" class=\"form-control input-250\" value=\"";
echo $criteria["customfieldvalue"];
echo "\">\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.clientname");
echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"clientname\" class=\"form-control input-250\" value=\"";
echo $criteria["clientname"];
echo "\">\n            </td>\n            <td class=\"fieldlabel\"></td>\n            <td class=\"fieldarea\"></td>\n        </tr>\n    </table>\n\n    <div class=\"btn-container\">\n        <button type=\"submit\" class=\"btn btn-default\">\n            ";
echo AdminLang::trans("global.search");
echo "        </button>\n    </div>\n\n</form>\n";
echo $tabEnd;
echo "<br />\n";
echo $tableOutput;

?>