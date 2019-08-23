<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>\n    <a id=\"btnNewAPICredentials\"\n        href=\"";
echo routePath("admin-setup-authz-api-device-new");
echo "\"\n        class=\"btn btn-success open-modal\"\n        data-modal-title=\"";
echo addslashes(AdminLang::trans("apicredentials.create"));
echo "\"\n        data-btn-submit-id=\"NewAPICredentials-Generate\"\n        data-btn-submit-label=\"";
echo addslashes(AdminLang::trans("apicredentials.generate"));
echo "\"\n    >\n        <i class=\"fas fa-plus fa-fw\"></i> ";
echo AdminLang::trans("apicredentials.create");
echo "    </a>\n</p>\n\n<table id=\"tblDevice\" class=\"table display data-driven table-themed\"\n       data-ajax-url=\"";
echo routePath("admin-setup-authz-api-devices-list");
echo "\"\n       data-on-draw-rebind-confirmation=\"true\"\n       data-lang-empty-table=\"";
echo AdminLang::trans("apicredentials.noCredentials");
echo "\"\n       data-auto-width=\"false\"\n       data-columns='";
echo json_encode(array(array("width" => "27%"), array("width" => "20%"), array("width" => "16%"), array("width" => "17%"), array("width" => "12%"), array("width" => "8%", "orderable" => 0)));
echo "'\n       >\n    <thead>\n    <tr>\n        <th>Identifier</th>\n        <th>Description</th>\n        <th>Admin User</th>\n        <th>Roles</th>\n        <th>Last Access</th>\n        <th></th>\n    </tr>\n    </thead>\n</table>\n\n<!-- successful form return body will want to use image; this cache in browser for smoother UX -->\n<img class=\"hide\" src=\"../assets/img/clippy.svg\" alt=\"Copy to clipboard\" width=\"15\" />\n\n<script>\n    showError = function(xhr) {\n        jQuery.growl.error({ title: 'Error', message: xhr.responseJSON ?  xhr.responseJSON.data : 'Internal Error' });\n    };\n\n    jQuery(document).ready(function() {\n        WHMCS.ui.dataTable.getTableById('tblDevice', {\"searching\": true});\n        jQuery('#btnNewAPICredentials').click(function (e) {\n            // ensure not previous content\n            jQuery('#inputDescription').val('');\n\n            // ensure generate button can be seen and clicked\n            jQuery('#NewAPICredentials-Generate').show().removeClass('disabled hidden');\n        });\n\n        // When dataTable object receives content, (re)define button group binds\n        // that are expressed in the (new) table data\n        WHMCS.ui.dataTable.getTableById('tblDevice').on('draw.dt', function() {\n            jQuery('.inline-editable').editable({\n                mode: 'inline',\n                params: function(params) {\n                    params.action = 'savefield';\n                    params.token = csrfToken;\n                    return params;\n                },\n                error: showError\n            });\n        });\n    });\n</script>\n";

?>