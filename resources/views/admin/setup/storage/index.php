<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "\n<script type=\"application/javascript\">\n(function(\$, window) {\n    \$(document).ready(function () {\n        ";
if (in_array($action, array("confirmSave", "confirmDelete", "showConfigurations"))) {
    echo "            \$('#tabConfigControl').trigger('click');\n\n            if ('";
    echo $action;
    echo "'.indexOf('confirm') === 0) {\n                jQuery.growl.notice({\n                    title: '";
    echo AdminLang::trans("global.success");
    echo "',\n                    message: '";
    echo AdminLang::trans("storage.config." . $action);
    echo "'\n                });\n            }\n        ";
}
echo "\n        \$('#btnCreateNewConfig').click(function() {\n            var form = \$(this).parents('form');\n            var select = \$(form).find('select[name=\"provider\"]');\n            var providerType = \$(select).val();\n\n            var providerName = \$(select).find('option[value=\"' + providerType + '\"]').data('name');\n\n            openModal(\n                \$(form).attr('action'),\n                \$(form).serialize(),\n                '";
echo AdminLang::trans("storage.createNewConfiguration");
echo ": ' + providerName,\n                '',\n                '',\n                '";
echo AdminLang::trans("global.savechanges");
echo "',\n                'btnSaveStorageConfiguration'\n            );\n        });\n\n        \$('a.test-config').click(function (e) {\n            e.preventDefault();\n\n            var link = this;\n            \$(link).find('i').removeClass('fa-play-circle').addClass('fa-spinner fa-spin');\n\n            WHMCS.http.jqClient.post(\n                \$(link).attr('href'),\n                {\n                    'token': '";
echo generate_token("plain");
echo "',\n                    'id': \$(link).data('id')\n                },\n                function(data) {\n                    if (data.successMsg) {\n                        jQuery.growl.notice({ title: data.successMsgTitle, message: data.successMsg });\n                    } else if (data.errorMsg) {\n                        jQuery.growl.error({ title: data.errorMsgTitle, message: data.errorMsg });\n                    }\n                },\n                'json'\n            ).always(function () {\n                \$(link).find('i').removeClass('fa-spinner fa-spin').addClass('fa-play-circle');\n            });\n        });\n\n        \$('a.delete-config').click(function (e) {\n            e.preventDefault();\n            var link = this;\n\n            swal(\n                {\n                    title: \"";
echo AdminLang::trans("global.areYouSure");
echo "\",\n                    text: \"";
echo AdminLang::trans("storage.configDeleteConfirm");
echo "\",\n                    type: \"warning\",\n                    showCancelButton: true,\n                    confirmButtonColor: \"#DD6B55\",\n                    confirmButtonText: \"";
echo AdminLang::trans("global.delete");
echo "\"\n                },\n                function(){\n                    \$(link).find('i').removeClass('fa-times').addClass('fa-spinner fa-spin');\n\n                    WHMCS.http.jqClient.post(\n                        \$(link).attr('href'),\n                        {\n                            'token': '";
echo generate_token("plain");
echo "'\n                        },\n                        function(data) {\n                            if (data.successMsg) {\n                                window.location = '";
echo routePath("admin-setup-storage-index", "confirmDelete");
echo "';\n                            } else if (data.errorMsg) {\n                                jQuery.growl.error({ title: data.errorMsgTitle, message: data.errorMsg });\n                            }\n                        },\n                        'json'\n                    ).always(function () {\n                        \$(link).find('i').removeClass('fa-spinner fa-spin').addClass('fa-times');\n                    });\n                }\n            );\n        });\n\n        \$('select[data-asset-type]').change(function () {\n            var select = this;\n            var assetType = \$(select).data('asset-type');\n\n            var requiredMigrationControls = \$('.required-migration-controls[data-asset-type=\"' + assetType + '\"]');\n\n            if (parseInt(\$(select).val()) !== parseInt(\$(select).data('current-value'))) {\n                \$(requiredMigrationControls).fadeIn();\n            } else {\n                \$(requiredMigrationControls).fadeOut();\n            }\n        });\n\n        \$('.required-migration-controls .btn-migrate').click(function (e) {\n            e.preventDefault();\n\n            var link = \$(this);\n\n            var requiredMigrationControls = \$(this).parents('.required-migration-controls');\n            var assetType = \$(requiredMigrationControls).data('asset-type');\n            var ongoingMigrationControls = \$('.ongoing-migration-controls[data-asset-type=\"' + assetType + '\"]');\n            var assetSelect = \$('select[data-asset-type=\"' + assetType +'\"]');\n\n            swal(\n                {\n                    title: \"";
echo AdminLang::trans("global.areYouSure");
echo "\",\n                    text: \"";
echo AdminLang::trans("storage.migration.migrationConfirm");
echo "\",\n                    type: \"warning\",\n                    showCancelButton: true,\n                    cancelButtonText: \"";
echo AdminLang::trans("global.cancel");
echo "\",\n                    confirmButtonColor: \"#DD6B55\",\n                    confirmButtonText: \"";
echo AdminLang::trans("global.continue");
echo "\"\n                },\n                function () {\n                    \$(link).find('.button-title').hide();\n                    \$(link).find('i').show();\n                    \$(link).attr('disabled', 'disabled');\n\n                    WHMCS.http.jqClient.post(\n                        \$(link).attr('href'),\n                        {\n                            'token': '";
echo generate_token("plain");
echo "',\n                            'configuration_id': \$(assetSelect).val()\n                        },\n                        function(data) {\n                            if (data.migrationCompleted) {\n                                jQuery.growl.notice({\n                                    title: '";
echo AdminLang::trans("global.success");
echo "',\n                                    message: '";
echo AdminLang::trans("storage.migration.migrationCompleted");
echo "',\n                                    duration: 10000\n                                });\n\n                                \$(assetSelect).data('current-value', \$(assetSelect).val());\n\n                                \$(requiredMigrationControls).fadeOut();\n\n                            } else if (data.migrationInProgress) {\n                                jQuery.growl.notice({\n                                    title: '";
echo AdminLang::trans("global.success");
echo "',\n                                    message: '";
echo AdminLang::trans("storage.migration.migrationInProgress");
echo "',\n                                    duration: 10000\n                                });\n\n                                \$(assetSelect).attr('disabled', 'disabled');\n\n                                var progress = data.progress + '%';\n\n                                \$(ongoingMigrationControls).find('.progress-bar')\n                                    .css('width', progress)\n                                    .text(progress);\n\n                                var failureIcon = \$(ongoingMigrationControls).find('.failure-icon');\n\n                                if (data.failureReason) {\n                                    \$(failureIcon).attr(\n                                        'title',\n                                        data.failureReason + \"\\n\\n";
echo AdminLang::trans("storage.migration.willContinueOnceFixed");
echo "\"\n                                    ).show();\n                                } else {\n                                    \$(failureIcon).hide();\n                                }\n\n                                \$(requiredMigrationControls).fadeOut(function () {\n                                    \$(ongoingMigrationControls).fadeIn();\n                                });\n                            } else if (data.errorMsg) {\n                                jQuery.growl.error({ title: data.errorMsgTitle, message: data.errorMsg });\n                            }\n                        },\n                        'json'\n                    ).always(function () {\n                        \$(link).removeAttr('disabled');\n                        \$(link).find('i').hide();\n                        \$(link).find('.button-title').show();\n                    });\n                }\n            );\n        });\n\n        \$('.required-migration-controls .btn-switch').click(function (e) {\n            e.preventDefault();\n\n            var link = \$(this);\n\n            var requiredMigrationControls = \$(this).parents('.required-migration-controls');\n            var assetType = \$(requiredMigrationControls).data('asset-type');\n            var assetSelect = \$('select[data-asset-type=\"' + assetType +'\"]');\n\n            var switchConfirmMessage = \$(assetSelect).data('can-migrate')\n                ? \"";
echo AdminLang::trans("storage.migration.skipMigrationConfirm");
echo "\"\n                : \"";
echo AdminLang::trans("storage.migration.nonMigratableSwitchConfirm");
echo "\";\n\n            swal(\n                {\n                    title: \"";
echo AdminLang::trans("global.areYouSure");
echo "\",\n                    text: switchConfirmMessage,\n                    type: \"warning\",\n                    showCancelButton: true,\n                    cancelButtonText: \"";
echo AdminLang::trans("global.cancel");
echo "\",\n                    confirmButtonColor: \"#DD6B55\",\n                    confirmButtonText: \"";
echo AdminLang::trans("global.continue");
echo "\"\n                },\n                function () {\n                    WHMCS.http.jqClient.post(\n                        \$(link).attr('href'),\n                        {\n                            'token': '";
echo generate_token("plain");
echo "',\n                            'configuration_id': \$(assetSelect).val()\n                        },\n                        function(data) {\n                            if (data.successMsg) {\n                                jQuery.growl.notice({ title: data.successMsgTitle, message: data.successMsg});\n\n                                \$(assetSelect).data('current-value', \$(assetSelect).val());\n\n                                \$(requiredMigrationControls).fadeOut();\n                            } else if (data.errorMsg) {\n                                jQuery.growl.error({ title: data.errorMsgTitle, message: data.errorMsg });\n                            }\n                        },\n                        'json'\n                    );\n                }\n            );\n        });\n\n        \$('.required-migration-controls .btn-revert').click(function (e) {\n            var requiredMigrationControls = \$(this).parents('.required-migration-controls');\n            var assetType = \$(requiredMigrationControls).data('asset-type');\n            var assetSelect = \$('select[data-asset-type=\"' + assetType +'\"]');\n\n            \$(requiredMigrationControls).fadeOut(function () {\n                \$(assetSelect).val(\$(assetSelect).data('current-value'));\n            });\n        });\n\n        \$('.ongoing-migration-controls .btn-cancel-migration').click(function (e) {\n            e.preventDefault();\n            var link = this;\n            var ongoingMigrationControls = \$(this).parents('.ongoing-migration-controls');\n            var assetType = \$(ongoingMigrationControls).data('asset-type');\n\n            swal(\n                {\n                    title: \"";
echo AdminLang::trans("global.areYouSure");
echo "\",\n                    text: \"";
echo AdminLang::trans("storage.migration.cancelConfirm");
echo "\",\n                    type: \"warning\",\n                    showCancelButton: true,\n                    cancelButtonText: \"";
echo AdminLang::trans("global.no");
echo "\",\n                    confirmButtonColor: \"#DD6B55\",\n                    confirmButtonText: \"";
echo AdminLang::trans("global.yes");
echo "\"\n                },\n                function(){\n                    WHMCS.http.jqClient.post(\n                        \$(link).attr('href'),\n                        {\n                            'token': '";
echo generate_token("plain");
echo "'\n                        },\n                        function(data) {\n                            if (data.successMsg) {\n                                jQuery.growl.notice({ title: data.successMsgTitle, message: data.successMsg});\n\n                                var assetSelect = \$('select[data-asset-type=\"' + assetType +'\"]');\n\n                                \$(assetSelect).val(\$(assetSelect).data('current-value')).removeAttr('disabled');\n                                \$(ongoingMigrationControls).fadeOut();\n                            } else if (data.errorMsg) {\n                                jQuery.growl.error({ title: data.errorMsgTitle, message: data.errorMsg });\n                            }\n                        },\n                        'json'\n                    );\n                }\n            );\n        });\n\n        \$('.ongoing-migration-controls .failure-icon').click(function () {\n            swal(\n                {\n                    title: \"";
echo AdminLang::trans("global.error");
echo "\",\n                    text: \$(this).attr('title'),\n                    type: \"error\",\n                    showCancelButton: false,\n                    confirmButtonText: \"";
echo AdminLang::trans("global.close");
echo "\"\n                }\n            );\n        });\n\n        \$('.config-type-icon a.show-error').click(function (e) {\n            e.preventDefault();\n\n            var link = this;\n\n            swal(\n                {\n                    title: \"";
echo AdminLang::trans("global.error");
echo "\",\n                    text: \$(link).attr('title'),\n                    type: \"error\",\n                    showCancelButton: true,\n                    cancelButtonText: \"";
echo AdminLang::trans("global.cancel");
echo "\",\n                    confirmButtonColor: \"#DD6B55\",\n                    confirmButtonText: \"";
echo AdminLang::trans("global.dismiss");
echo "\"\n                },\n                function(){\n                    WHMCS.http.jqClient.post(\n                        \$(link).attr('href'),\n                        {\n                            'token': '";
echo generate_token("plain");
echo "'\n                        },\n                        function(data) {\n                            if (data.successMsg) {\n                                window.location = '";
echo routePath("admin-setup-storage-index", "showConfigurations");
echo "';\n                            } else if (data.errorMsg) {\n                                jQuery.growl.error({ title: data.errorMsgTitle, message: data.errorMsg });\n                            }\n                        },\n                        'json'\n                    );\n                }\n            );\n        });\n\n    }); // document.ready\n})(jQuery, window);\n</script>\n\n<div>\n    <div class=\"row\">\n        <div class=\"col-md-12 bottom-margin-5\">\n            <ul class=\"nav nav-tabs\" role=\"tablist\">\n                <li class=\"active\" role=\"presentation\">\n                    <a id=\"tabSettingsControl\"\n                       data-toggle=\"tab\"\n                       href=\"#tabSettings\"\n                       role=\"tab\"\n                    >\n                        ";
echo AdminLang::trans("storage.settings");
echo "                    </a>\n                </li>\n                <li role=\"presentation\">\n                    <a id=\"tabConfigControl\"\n                       data-toggle=\"tab\"\n                       href=\"#tabConfig\"\n                       role=\"tab\"\n                    >\n                        ";
echo AdminLang::trans("storage.configurations");
echo "                    </a>\n                </li>\n            </ul>\n            <div class=\"tab-content\">\n                <div class=\"tab-pane active\" id=\"tabSettings\">\n                    <div class=\"row\">\n                        <div class=\"col-md-12 bottom-margin-5\">\n                            <div class=\"alert alert-info top-margin-10 bottom-margin-10\" role=\"alert\">\n                                <span class=\"fa-stack\" style=\"margin-right: 10px;\">\n                                    <i class=\"far fa-circle fa-stack-2x\"></i>\n                                    <i class=\"fas fa-info fa-stack-1x\"></i>\n                                </span>\n                                <span>\n                                    ";
echo AdminLang::trans("storage.changeRequiresMigration");
echo "                                </span>\n                            </div>\n\n                            <form id=\"frmStorageSettings\" method=\"post\">\n\n                                <table class=\"form bottom-margin-10\"\n                                       width=\"100%\"\n                                       border=\"0\"\n                                       cellspacing=\"2\"\n                                       cellpadding=\"3\"\n                                       style=\"min-width: 390px;\"\n                                >\n                                    ";
foreach ($assetSettings as $setting) {
    echo "                                    <tr>\n                                        <td class=\"fieldlabel\" width=\"25%\">";
    echo AdminLang::trans("storage.assetTypes." . $setting["asset_type"]);
    echo "</td>\n                                        <td class=\"fieldarea\">\n                                            <select name=\"asset_setting[";
    echo $setting["asset_type"];
    echo "]\"\n                                                    class=\"form-control select-inline\"\n                                                    data-asset-type=\"";
    echo $setting["asset_type"];
    echo "\"\n                                                    data-current-value=\"";
    echo $setting["configuration"]["id"];
    echo "\"\n                                                    data-can-migrate=\"";
    echo $setting["can_migrate"] ? 1 : 0;
    echo "\"\n                                                    ";
    echo $setting["migration"] ? " disabled" : "";
    echo "                                            >\n                                                ";
    foreach ($storageConfigurations as $config) {
        echo "                                                    ";
        $selectedId = $setting["migration"] ? $setting["migrate_to_configuration"]["id"] : $setting["configuration"]["id"];
        echo "                                                    <option value=\"";
        echo $config["id"];
        echo "\"\n                                                        ";
        echo $selectedId == $config["id"] ? " selected" : "";
        echo "                                                    >\n                                                        ";
        echo $config["name"];
        echo "                                                    </option>\n                                                ";
    }
    echo "                                            </select>\n\n                                            <div class=\"asset-controls required-migration-controls\"\n                                                 style=\"display: none;\"\n                                                 data-asset-type=\"";
    echo $setting["asset_type"];
    echo "\">\n                                                ";
    if ($setting["can_migrate"]) {
        echo "                                                    <a class=\"btn btn-success btn-sm btn-migrate\"\n                                                       href=\"";
        echo routePath("admin-setup-storage-migration-start", $setting["asset_type"]);
        echo "\"\n                                                    >\n                                                        <i class=\"fas fa-spinner fa-spin\" style=\"display: none;\"></i>\n                                                        <span class=\"button-title\">";
        echo AdminLang::trans("storage.migration.migrate");
        echo "</span>\n                                                    </a>\n                                                ";
    }
    echo "                                                <a class=\"btn btn-danger btn-sm btn-switch\"\n                                                   href=\"";
    echo routePath("admin-setup-storage-migration-switch", $setting["asset_type"]);
    echo "\"\n                                                >\n                                                    <span class=\"button-title\">";
    echo AdminLang::trans("storage.migration.switch");
    echo "</span>\n                                                </a>\n                                                <a class=\"btn btn-default btn-sm btn-revert\">\n                                                    <span class=\"button-title\">";
    echo AdminLang::trans("storage.migration.revertChanges");
    echo "</span>\n                                                </a>\n                                            </div>\n\n                                            <div class=\"asset-controls ongoing-migration-controls\"\n                                                ";
    echo !$setting["migration"] ? " style=\"display: none\" " : "";
    echo "                                                 data-asset-type=\"";
    echo $setting["asset_type"];
    echo "\">\n                                                ";
    $migrationProgress = $setting["migration"] ? $setting["migration"]["progress"] : 0;
    echo "\n                                                <div class=\"failure-icon\"\n                                                     ";
    echo !$setting["migration"]["last_failure"] ? "style=\"display: none\"" : "";
    echo "                                                     title=\"";
    echo $setting["migration"]["last_failure"] ? $setting["migration"]["last_failure"] . "\n\n" . AdminLang::trans("storage.migration.willContinueOnceFixed") : "";
    echo "\">\n                                                    <i class=\"fas fa-exclamation-triangle\"></i>\n                                                </div>\n\n                                                <div class=\"progress migration-progress\">\n                                                    <div class=\"progress-bar progress-bar-success\" role=\"progressbar\"\n                                                         style=\"width: ";
    echo $migrationProgress;
    echo "%; min-width: 30px;\">\n                                                        ";
    echo $migrationProgress;
    echo "%\n                                                    </div>\n                                                </div>\n                                                <a class=\"btn btn-warning btn-sm btn-cancel-migration\"\n                                                   href=\"";
    echo routePath("admin-setup-storage-migration-cancel", $setting["asset_type"]);
    echo "\"\n                                                >\n                                                    <span class=\"button-title\">";
    echo AdminLang::trans("storage.migration.cancel");
    echo "</span>\n                                                </a>\n                                            </div>\n                                        </td>\n                                    </tr>\n                                    ";
}
echo "                                </table>\n                            </form>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"tab-pane\" id=\"tabConfig\">\n                    <div class=\"row top-margin-10\">\n                        ";
foreach ($storageConfigurations as $config) {
    echo "\n                            <div class=\"col-lg-3 col-md-4 col-sm-6 col-xs-12 storage-config-container\">\n                                <div class=\"panel ";
    echo $config["is_local"] ? "panel-success" : "panel-warning";
    echo " storage-config-panel\">\n                                    <div class=\"panel-heading\" style=\"font-size: 1.2em\">\n                                        <span>";
    echo $config["provider_name"];
    echo "</span>\n\n                                        <span class=\"storage-config-controls pull-right\">\n                                            <a class=\"test-config\"\n                                               href=\"";
    echo routePath("admin-setup-storage-test-configuration", $config["id"]);
    echo "\"\n                                               title=\"";
    echo AdminLang::trans("global.test");
    echo "\"\n                                            >\n                                                <i class=\"fas fa-fw fa-play-circle\"></i>\n                                            </a>\n                                            <a class=\"duplicate-config open-modal\"\n                                               href=\"";
    echo routePath("admin-setup-storage-duplicate-configuration", $config["id"]);
    echo "\"\n                                               title=\"";
    echo AdminLang::trans("global.duplicate");
    echo "\"\n                                               data-modal-title=\"";
    echo addslashes(AdminLang::trans("global.addnew") . ": " . $config["provider_name"]);
    echo "\"\n                                               data-btn-submit-id=\"btnSaveStorageConfiguration\"\n                                               data-btn-submit-label=\"";
    echo addslashes(AdminLang::trans("global.savechanges"));
    echo "\"\n                                            >\n                                                <i class=\"far fa-fw fa-clone\"></i>\n                                            </a>\n                                            <a class=\"edit-config open-modal\"\n                                               href=\"";
    echo routePath("admin-setup-storage-edit-configuration", $config["id"]);
    echo "\"\n                                               title=\"";
    echo AdminLang::trans("global.edit");
    echo "\"\n                                               data-modal-title=\"";
    echo addslashes(AdminLang::trans("storage.editConfiguration") . ": " . $config["provider_name"]);
    echo "\"\n                                               data-btn-submit-id=\"btnSaveStorageConfiguration\"\n                                               data-btn-submit-label=\"";
    echo addslashes(AdminLang::trans("global.savechanges"));
    echo "\"\n                                            >\n                                                <i class=\"fas fa-fw fa-cog\"></i>\n                                            </a>\n                                            <a class=\"delete-config\"\n                                               href=\"";
    echo routePath("admin-setup-storage-delete-configuration", $config["id"]);
    echo "\"\n                                               title=\"";
    echo AdminLang::trans("global.delete");
    echo "\"\n                                            >\n                                                <i class=\"fas fa-fw fa-times\"></i>\n                                            </a>\n                                        </span>\n                                    </div>\n                                    <div class=\"panel-body\">\n                                        <span class=\"fa-stack fa-2x config-type-icon pull-left\">\n                                            ";
    if ($config["error_message"]) {
        echo "                                                <a class=\"show-error\"\n                                                   title=\"";
        echo $config["error_message"];
        echo "\"\n                                                   href=\"";
        echo routePath("admin-setup-storage-dismiss-error", $config["id"]);
        echo "\">\n                                                    <i class=\"fal fa-exclamation-triangle config-error-icon\"></i>\n                                                </a>\n                                            ";
    } else {
        echo "                                                <i class=\"fal fa-square fa-stack-2x\"></i>\n                                                <i class=\"";
        echo $config["icon"];
        echo " fa-stack-1x\"></i>\n                                            ";
    }
    echo "                                        </span>\n                                        <div class=\"config-summary top-margin-5\">\n                                            ";
    echo $config["config_summary"];
    echo "                                        </div>\n                                    </div>\n                                </div>\n                            </div>\n\n                        ";
}
echo "\n                        <div class=\"col-lg-3 col-md-4 col-sm-6 col-xs-12\">\n                            <div class=\"panel panel-default storage-config-panel\">\n                                <div class=\"panel-heading\" style=\"font-size: 1.2em\">\n                                    <span>";
echo AdminLang::trans("storage.createNewConfiguration");
echo "</span>\n                                </div>\n                                <div class=\"panel-body\">\n                                    <span class=\"fa-stack fa-2x config-type-icon pull-left\">\n                                        <i class=\"fal fa-circle fa-stack-2x\"></i>\n                                        <i class=\"far fa-file fa-stack-1x\"></i>\n                                    </span>\n                                    <div class=\"config-summary\" style=\"padding-top:10px;\">\n                                        <form id=\"frmNewStorageConfig\"\n                                              action=\"";
echo routePath("admin-setup-storage-edit-configuration", 0);
echo "\">\n                                            <input type=\"hidden\" name=\"token\" value=\"";
echo $csrfToken;
echo "\">\n                                            <select class=\"form-control select-inline\" name=\"provider\">\n                                                ";
foreach ($providers as $provider => $name) {
    echo "                                                    <option value=\"";
    echo $provider;
    echo "\" data-name=\"";
    echo $name;
    echo "\">";
    echo $name;
    echo "</option>\n                                                ";
}
echo "                                            </select>\n                                            <button type=\"button\" id=\"btnCreateNewConfig\" class=\"btn btn-default\" style=\"margin-bottom:2px;\">\n                                                <i class=\"fas fa-plus\"></i>\n                                            </button>\n                                        </form>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n";

?>