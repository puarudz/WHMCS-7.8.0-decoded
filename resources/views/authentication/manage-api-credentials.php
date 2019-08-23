<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>\n    ";
echo AdminLang::trans("apicreds.introduction");
echo "</p>\n<div role=\"tabpanel\">\n    <ul class=\"nav nav-tabs\" role=\"tablist\">\n        <li role=\"presentation\" class=\"active\">\n            <a href=\"#tabManageCredentials\" id=\"btnManageCredentials\" aria-controls=\"tabManageCredentials\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-sign-in-alt\"></i> ";
echo AdminLang::trans("apicreds.title");
echo "            </a>\n        </li>\n        <li role=\"presentation\">\n            <a href=\"#tabManageRoles\" id=\"btnManageRoles\" aria-controls=\"tabManageRoles\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-cubes\"></i> ";
echo AdminLang::trans("apirole.title");
echo "            </a>\n        </li>\n    </ul>\n    <br />\n    <div class=\"tab-content\">\n        <div role=\"tabpanel\" class=\"tab-pane fade in active\" id=\"tabManageCredentials\">\n            ";
echo $this->insert("partials/section-api-credentials");
echo "        </div>\n        <div role=\"tabpanel\" class=\"tab-pane fade\" id=\"tabManageRoles\">\n            ";
echo $this->insert("partials/section-api-roles");
echo "        </div>\n    </div>\n</div>\n\n";

?>