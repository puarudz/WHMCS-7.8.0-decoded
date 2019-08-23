<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<div class=\"notification-providers\">\n    <div class=\"scrollable provider-count-";
echo count($notificationProviders) . (3 < count($notificationProviders) ? " scroll" : "");
echo "\">\n        ";
foreach ($notificationProviders as $provider) {
    $activeClasses = "ribbon active hidden";
    $disabledClasses = "ribbon disabled";
    if ($provider->isActive()) {
        $activeClasses = "ribbon active";
        $disabledClasses = "ribbon disabled hidden";
    }
    echo "<div class=\"provider-col\">                <div class=\"provider\" data-provider-name=\"";
    echo $provider->getName();
    echo "\">\n                    <div id=\"ribbon";
    echo $provider->getName();
    echo "Active\" class=\"";
    echo $activeClasses;
    echo "\"><span>";
    echo AdminLang::trans("status.active");
    echo "</span></div>\n                    <div id=\"ribbon";
    echo $provider->getName();
    echo "Inactive\" class=\"";
    echo $disabledClasses;
    echo "\"><span>";
    echo AdminLang::trans("status.inactive");
    echo "</span></div>\n                    <div class=\"logo\">\n                        <img src=\"..";
    echo $provider->getLogoPath();
    echo "\">\n                    </div>\n                    <a href=\"";
    echo routePath("admin-setup-notifications-provider", $provider->getName());
    echo "\" class=\"btn btn-default open-modal\" data-modal-title=\"";
    echo addslashes(AdminLang::trans("global.configure"));
    echo " ";
    echo $provider->getDisplayName();
    echo "\" data-btn-submit-id=\"btnConfigure\" data-btn-submit-label=\"";
    echo addslashes(AdminLang::trans("global.savechanges"));
    echo "\">";
    echo AdminLang::trans("global.configure");
    echo "</a>\n                </div>\n            </div>";
}
echo "    </div>\n</div>\n\n<h2>";
echo AdminLang::trans("notifications.rules");
echo "</h2>\n\n<p>\n    <a href=\"";
echo routePath("admin-setup-notifications-rule-create");
echo "\" class=\"btn btn-default open-modal\" data-modal-size=\"modal-lg\" data-modal-title=\"";
echo addslashes(AdminLang::trans("notifications.createNewRule"));
echo "\" data-btn-submit-id=\"CreateRule\" data-btn-submit-label=\"";
echo addslashes(AdminLang::trans("notifications.create"));
echo "\">\n        <i class=\"fas fa-plus fa-fw\"></i>\n        ";
echo AdminLang::trans("notifications.createNewRule");
echo "    </a>\n</p>\n\n<table id=\"tblNotificationRules\" class=\"table table-themed\">\n    <thead>\n        <tr>\n            <th>";
echo AdminLang::trans("fields.description");
echo "</th>\n            <th>";
echo AdminLang::trans("fields.events");
echo "</th>\n            <th>";
echo AdminLang::trans("fields.conditions");
echo "</th>\n            <th>";
echo AdminLang::trans("notifications.notificationMethod");
echo "</th>\n            <th>";
echo AdminLang::trans("fields.lastmodified");
echo "</th>\n            <th></th>\n            <th></th>\n        </tr>\n    </thead>\n</table>\n\n<script>\n\nfunction deleteRule(id) {\n    swal({\n        title: \"";
echo addslashes(AdminLang::trans("global.areYouSure"));
echo "\",\n        text: \"";
echo addslashes(AdminLang::trans("notifications.deleteAreYouSure"));
echo "\",\n        type: 'warning',\n        showCancelButton: true,\n        confirmButtonColor: \"#DD6B55\",\n        confirmButtonText: \"";
echo addslashes(AdminLang::trans("global.yes"));
echo "\"\n    },\n    function(){\n        WHMCS.http.jqClient.post('";
echo routePath("admin-setup-notifications-rule-delete");
echo "', 'rule_id=' + id + '";
echo generate_token("link");
echo "',\n            function(data) {\n                if (data.success) {\n                    jQuery.growl.error({ title: '', message: \"";
echo addslashes(AdminLang::trans("notifications.deleteConfirmation"));
echo "\" });\n                    \$('#tblNotificationRules').DataTable().ajax.reload();\n                }\n            }, 'json');\n    });\n}\n\nshowError = function(xhr) {\n        jQuery.growl.error({ title: 'Error', message: xhr.responseJSON ?  xhr.responseJSON.data : 'Internal Error' });\n    };\n\n\$(document).ready(function() {\n    \$('#tblNotificationRules').DataTable({\n        \"dom\": '<\"listtable\"it>pl',\n        \"oLanguage\": {\n            \"sEmptyTable\": \"";
echo addslashes(AdminLang::trans("notifications.noRulesSetup"));
echo "\",\n        },\n        \"ajax\": {\n            \"url\": '";
echo routePath("admin-setup-notifications-list");
echo "',\n            \"error\": showError,\n            \"complete\": function() {\n                \$(\".status-switch\").bootstrapSwitch({'size': 'small', 'onColor': 'success', 'onSwitchChange': function(event, state) {\n                    WHMCS.http.jqClient.post('";
echo routePath("admin-setup-notifications-rule-status");
echo "', 'id=' + \$(this).data('id') + '&state=' + state + '";
echo generate_token("link");
echo "');\n                }});\n            }\n        },\n        \"bAutoWidth\": false,\n        \"columns\": [\n            null,\n            null,\n            null,\n            null,\n            null,\n            { \"width\": \"50px\", \"orderable\": false },\n            { \"width\": \"100px\", \"orderable\": false }\n        ]\n    });\n    \$('#modalAjax').on('hide.bs.modal', function () {\n        \$('#tblNotificationRules').DataTable().ajax.reload();\n        getNotificationProvidersStatus();\n    });\n    ";
if ($activateModuleName) {
    echo "        \$('.notification-providers').find('.provider[data-provider-name=\"";
    echo $activateModuleName;
    echo "\"]').find('.open-modal').click();\n    ";
}
echo "});\n\nfunction getNotificationProvidersStatus()\n{\n    WHMCS.http.jqClient.get(\n        '";
echo routePath("admin-setup-notifications-providers-status");
echo "',\n        '',\n        function(data) {\n            var providers = data.providers;\n            \$.each(providers, function(index, value) {\n                if (value === 1) {\n                    \$('#ribbon' + index + 'Active').removeClass(\"hidden\");\n                    \$('#ribbon' + index + 'Inactive').addClass(\"hidden\");\n                } else {\n                    \$('#ribbon' + index + 'Active').addClass(\"hidden\");\n                    \$('#ribbon' + index + 'Inactive').removeClass(\"hidden\");\n                }\n            });\n        },\n        'json'\n    );\n}\n\n</script>\n\n<style>\n.table-themed {\n    border: 1px solid #ddd;\n}\n.table-themed th {\n    background-color: #fff;\n    font-size: 0.96em;\n}\n.table-themed tr.odd td {\n    background-color: #f8f8f8;\n}\n.table-themed tr:hover td {\n    background-color: #ecf3f8;\n}\n.table-themed td {\n    background-color: #fff;\n    font-size: 0.9em;\n}\n.notification-providers {\n    margin: 20px -15px;\n    padding: 15px 15px 10px 15px;\n    background-color: #f2f2f2;\n}\n.notification-providers .scrollable {\n    margin-left: -7px;\n    margin-right: -7px;\n    height: 190px;\n    white-space: nowrap;\n    overflow: auto;\n}\n.notification-providers .scrollable.scroll {\n    height: 205px;\n}\n.notification-providers .provider-col {\n    display: inline-block;\n    width: 350px;\n    padding-left: 7px;\n    padding-right: 7px;\n}\n.notification-providers .scrollable.provider-count-2 .provider-col {\n    width: 50%;\n}\n.notification-providers .scrollable.provider-count-3 .provider-col {\n    width: 33.33%;\n}\n.notification-providers .provider {\n    position: relative;\n    margin: 0;\n    padding: 12px;\n    background-color: #fff;\n    text-align: center;\n    border-radius: 4px;\n    height: 185px;\n    overflow: hidden;\n}\n.notification-providers .provider .logo {\n    text-align: center;\n    line-height: 125px;\n}\n.notification-providers .provider img {\n    max-width: 80%;\n    max-height: 100px;\n}\n\n.ribbon {\n  position: absolute;\n  right: -0; top: -0;\n  z-index: 1;\n  overflow: hidden;\n  width: 75px; height: 75px;\n  text-align: right;\n}\n.ribbon.active span {\n  font-size: 10px;\n  font-weight: bold;\n  color: #FFF;\n  text-transform: uppercase;\n  text-align: center;\n  line-height: 20px;\n  transform: rotate(45deg);\n  -webkit-transform: rotate(45deg);\n  width: 100px;\n  display: block;\n  background: #79A70A;\n  box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);\n  position: absolute;\n  top: 19px; right: -21px;\n}\n\n.ribbon.disabled span {\n  font-size: 10px;\n  font-weight: bold;\n  color: #FFF;\n  text-transform: uppercase;\n  text-align: center;\n  line-height: 20px;\n  transform: rotate(45deg);\n  -webkit-transform: rotate(45deg);\n  width: 100px;\n  display: block;\n  background: #808080;\n  box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);\n  position: absolute;\n  top: 19px; right: -21px;\n}\n</style>\n";

?>