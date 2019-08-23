<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$i = 0;
$sidebarList = array();
$tabContent = array();
$roleName = $roleDescription = $roleId = "";
if (isset($role)) {
    $roleName = sprintf("value=\"%s\"", $role->role);
    $roleDescription = $role->description ?: "";
    $roleId = $role->id;
}
$btnTextNone = AdminLang::trans("adminroles.checkall");
$btnTextAll = AdminLang::trans("adminroles.uncheckall");
foreach ($apiCatalog->getGroupedActions() as $group => $groupDetails) {
    $tabId = "tab" . ucfirst($group);
    $sidebarList[] = sprintf("<li class=\"%s\"><a href=\"#%s\" data-toggle=\"tab\">%s</a></li>", $i ? "" : "active", $tabId, $groupDetails["name"]);
    $tabItems = array();
    $checkedInGroup = 0;
    foreach ($groupDetails["actions"] as $action => $actionDetails) {
        if ($action == "setconfigurationvalue") {
            continue;
        }
        if (isset($role)) {
            $checked = $role->isAllowed($action) ? "checked" : "";
        } else {
            $checked = $actionDetails["default"] ? "checked" : "";
        }
        if ($checked) {
            $checkedInGroup++;
        }
        $name = $actionDetails["name"];
        $tabItems[] = "<div class=\"col-sm-6\">\n    <label class=\"checkbox-inline\" >\n        <input id=\"" . $action . "\" name=\"allow[" . $action . "]\" type=\"checkbox\" " . $checked . "> " . $name . "\n        <a href=\"https://developers.whmcs.com/api-reference/" . $action . "/\" target=\"_blank\">\n            <i class=\"fas fa-book\"></i>\n        </a>\n    </label>\n</div>";
    }
    $allInGroupSelected = $tabItems && count($tabItems) === $checkedInGroup;
    if ($allInGroupSelected) {
        $btnClassActive = "toggle-active";
    } else {
        $btnClassActive = "";
    }
    $btnSelectAll = sprintf("<div class=\"btn-check-all btn btn-sm btn-link %s\"\n            data-checkbox-container=\"%s\"\n            data-btn-toggle-on=\"1\"\n            id=\"btnSelectAll-%s\">%s</div>", $btnClassActive, $tabId, $tabId, $btnTextNone);
    $btnDeselectAll = sprintf("<div class=\"btn-check-all btn btn-sm btn-link %s\"\n            data-checkbox-container=\"%s\"\n            id=\"btnSelectAll-%s\">%s</div>", $btnClassActive, $tabId, $tabId, $btnTextAll);
    $tabContent[] = sprintf("<div class=\"tab-pane %s\" id=\"%s\">\n            <h2>%s</h2>\n            <div class=\"scroll-container\">\n                <div class=\"row\">%s</div>\n            </div>\n            <br>\n            %s %s\n        </div>", $i ? "" : "active", $tabId, $groupDetails["name"], implode("\n", $tabItems), $btnSelectAll, $btnDeselectAll);
    $i++;
}
echo "<script>\n    jQuery(document).ready(function() {\n        WHMCS.form.register();\n    });\n</script>\n<form class=\"form-horizontal\" name=\"frmApiRoleManage\" action=\"";
echo routePath("admin-setup-authz-api-roles-create");
echo "\">\n    <input type=\"hidden\" name=\"token\" value=\"";
echo $csrfToken;
echo "\">\n    <input type=\"hidden\" name=\"roleId\" value=\"";
echo $roleId;
echo "\">\n    <div class=\"form-group\">\n        <label for=\"inputName\" class=\"col-sm-2 control-label\">";
echo AdminLang::trans("apirole.roleName");
echo "</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" class=\"form-control\" id=\"inputName\" name=\"roleName\" placeholder=\"";
echo AdminLang::trans("apirole.roleName");
echo "\" ";
echo $roleName;
echo ">\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputDescription\" class=\"col-sm-2 control-label\">";
echo AdminLang::trans("fields.description");
echo "</label>\n        <div class=\"col-sm-10\">\n            <textarea class=\"form-control\" id=\"inputDescription\" name=\"roleDescription\" placeholder=\"";
echo AdminLang::trans("apirole.descriptionPlaceholder");
echo "\">";
echo $roleDescription;
echo "</textarea>\n        </div>\n    </div>\n    <h2 class=\"api-permissions-heading\">\n        ";
echo AdminLang::trans("apirole.allowedApiActions");
echo "    </h2>\n    <div class=\"row api-permissions\">\n        <!-- sidebar nav -->\n        <div class=\"col-sm-3\">\n            <nav class=\"nav-sidebar\">\n                <ul class=\"nav\">\n                    ";
echo implode("\n", $sidebarList);
echo "                </ul>\n            </nav>\n        </div>\n        <!-- tab content -->\n        <div class=\"col-sm-9\">\n            <div class=\"tab-content\">\n                ";
echo implode("\n", $tabContent);
echo "            </div>\n        </div>\n    </div>\n</form>\n";

?>