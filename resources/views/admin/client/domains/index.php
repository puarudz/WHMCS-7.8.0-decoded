<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo $tabStart;
echo "<form action=\"";
echo routePath("admin-domains-index");
echo "\" method=\"post\">\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
echo AdminLang::trans("fields.domain");
echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"domain\" class=\"form-control input-300\" value=\"";
echo $criteria["domain"];
echo "\">\n            </td>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
echo AdminLang::trans("fields.status");
echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"status\" class=\"form-control select-inline\">\n                    <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                    ";
echo $statuses;
echo "                </select>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.registrar");
echo "</td>\n            <td class=\"fieldarea\">";
echo $registrars;
echo "</td>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("fields.clientname");
echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"clientname\" class=\"form-control input-250\" value=\"";
echo $criteria["clientname"];
echo "\">\n            </td>\n        </tr>\n    </table>\n\n    <div class=\"btn-container\">\n        <button type=\"submit\" class=\"btn btn-default\">\n            ";
echo AdminLang::trans("global.search");
echo "        </button>\n    </div>\n</form>\n";
echo $tabEnd;
echo "    <br />\n";
echo $tableOutput;

?>