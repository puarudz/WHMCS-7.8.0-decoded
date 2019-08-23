<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<form method=\"post\" action=\"";
echo routePath("admin-setup-authz-api-devices-generate");
echo "\" id=\"frmCreateCredentials\">\n    <input type=\"hidden\" name=\"token\" value=\"";
echo $csrfToken;
echo "\">\n    <div class=\"form-group\">\n        <label for=\"inputAdmin\">";
echo AdminLang::trans("apicreds.adminUser");
echo "</label>\n        <select id=\"inputAdmin\" name=\"admin_id\" class=\"form-control enhanced\" style=\"width:100%;\">\n            ";
echo $adminUserSelectOptions;
echo "        </select>\n    </div>\n    ";
echo $this->insert("partials/attributes-api-credentials");
echo "</form>\n";

?>