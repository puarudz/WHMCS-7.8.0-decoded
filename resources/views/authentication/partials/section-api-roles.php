<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>\n    <a id=\"btnNewAPIRole\"\n       href=\"";
echo routePath("admin-setup-authz-api-roles-manage");
echo "\"\n       data-modal-title=\"";
echo AdminLang::trans("apirole.roleManagement");
echo "\"\n       data-modal-size=\"modal-lg\"\n       data-modal-class=\"modal-manage-api-role\"\n       data-btn-submit-id=\"btnSaveApiRole\"\n       data-datatable-reload-success=\"tblApiRoles\"\n       data-btn-submit-label=\"";
echo AdminLang::trans("global.save");
echo "\"\n       onclick=\"return false;\"\n       class=\"btn btn-success open-modal\">\n        <i class=\"fas fa-plus\"></i>&nbsp;";
echo AdminLang::trans("apirole.create");
echo "    </a>\n</p>\n\n<table id=\"tblApiRoles\" class=\"table display data-driven table-themed tbl-api-roles\"\n    data-ajax-url=\"";
echo routePath("admin-setup-authz-api-roles-list");
echo "\"\n    data-on-draw-rebind-confirmation=\"true\"\n    data-lang-empty-table=\"";
echo AdminLang::trans("apirole.noRolesDefined");
echo "\"\n    data-auto-width=\"false\"\n    data-order='[[ 1, \"asc\" ]]'\n    data-columns='";
echo json_encode(array(array("data" => "btnExpand", "className" => "details-control text-center", "orderable" => 0, "width" => "3%"), array("data" => "name", "className" => "details-control", "width" => "25%"), array("data" => "description", "className" => "details-control", "width" => "64%"), array("data" => "btnGroup", "orderable" => 0, "width" => "8%")));
echo "'\n>\n    <thead>\n        <tr class=\"text-center\">\n            <th></th>\n            <th>";
echo AdminLang::trans("apirole.roleName");
echo "</th>\n            <th>";
echo AdminLang::trans("fields.description");
echo "</th>\n            <th></th>\n        </tr>\n    </thead>\n    <tbody>\n    <tr>\n        <td colspan=\"4\" class=\"text-center\">";
echo AdminLang::trans("apirole.noRolesDefined");
echo "</td>\n    </tr>\n    </tbody>\n</table>\n<script>\n    \$(document).ready(function () {\n\n        var table = WHMCS.ui.dataTable.getTableById('tblApiRoles');\n\n        // Add event listener for opening and closing details\n        \$('#tblApiRoles tbody').on('click', 'td.details-control', function () {\n            var tr = \$(this).closest('tr');\n            var tdi = tr.find(\"i.fas\");\n            var row = table.row(tr);\n\n            if (row.child.isShown()) {\n                // This row is already open - close it\n                row.child.hide();\n                tr.removeClass('shown');\n                tdi.first().removeClass('fa-caret-down');\n                tdi.first().addClass('fa-caret-right');\n            }\n            else {\n                // Open this row\n                row.child(formatAllowedActions(row.data())).show();\n\n                if (!row.child().hasClass('allowed-permissions')) {\n                    row.child().addClass('allowed-permissions');\n                }\n                tr.addClass('shown');\n                tdi.first().removeClass('fa-caret-right');\n                tdi.first().addClass('fa-caret-down');\n            }\n        });\n\n        table.on(\"user-select\", function (e, dt, type, cell, originalEvent) {\n            if (\$(cell.node()).hasClass(\"details-control\")) {\n                e.preventDefault();\n            }\n        });\n    });\n\n    function formatAllowedActions(d) {\n        var actions = '';\n        for (var i=0; i<d.allowedActions.length; i++) {\n            actions = actions +\n                '<div class=\"col-sm-3\">' +\n                    d.allowedActions[i] +\n                '</div>'\n        }\n\n        return '<div class=\"container-fluid\">' +\n                    '<div class=\"title\">' +\n                        '";
echo AdminLang::trans("apirole.allowedApiActions");
echo "' +\n                    '</div>' +\n                    '<div class=\"row row-detail\">' +\n                        actions +\n                    '</div>' +\n                '</div>';\n    }\n</script>\n";

?>