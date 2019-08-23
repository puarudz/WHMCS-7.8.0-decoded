<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>";
echo AdminLang::trans("remoteAuthn.settingsDesc");
echo "</p>\n\n<div class=\"signin-apps-container\">\n    <div class=\"row\">\n";
foreach ($providers as $provider) {
    $this->insert("partials/settings-remote-providers", array("provider" => $provider));
}
echo "    </div>\n</div>\n\n<script>\n\$(document).ready(function() {\n    var \$providerForms = \$('.integration-provider');\n\n    /** Show modal - hide any previous error **/\n    \$('.modal-integration-provider').on('show.bs.modal', function (e) {\n        \$providerForms.find('.alert').hide();\n    })\n\n    /** Deactivate **/\n    \$providerForms.find('button[type=\"reset\"]').click(function(e) {\n        e.preventDefault();\n\n        var \$submittedFrm = \$(this).closest('form');\n        var \$provider = \$submittedFrm.data('provider');\n        var \$deactivateBtn = \$submittedFrm.find('button[type=\"reset\"]');\n        var \$saveActivateBtn = \$submittedFrm.find('button[type=\"submit\"]');\n\n        WHMCS.http.jqClient.post({\n            url: \"";
echo routePath("admin-setup-authn-deactivate");
echo "\",\n            data: \$submittedFrm.serialize(),\n            dataType: 'json'\n        }).done(function (data) {\n            if (data.status == 'success') {\n                \$('#integration-' + \$provider).modal('hide');\n                var \$btnProviderModal = \$('#btnProviderModal_' + \$provider);\n                \$btnProviderModal.html('";
echo AdminLang::trans("global.activate");
echo "')\n                    .addClass('btn-success')\n                    .removeClass('btn-primary');\n\n                \$deactivateBtn.addClass('hidden').hide();\n                \$saveActivateBtn.html('";
echo AdminLang::trans("global.saveAndActivate");
echo "');\n\n            } else {\n                showIntegrationProviderError(\$submittedFrm, '');\n            }\n        }).fail(function () {\n            showIntegrationProviderError(\$submittedFrm, '<strong>System Error.</strong> Please refresh the page and try again.');\n        });\n\n    });\n\n    /** Save & Activate **/\n    \$providerForms.submit(function(e) {\n        e.preventDefault();\n        var \$submittedFrm = \$(this);\n        var \$provider = \$submittedFrm.data('provider');\n        var \$saveActivateBtn = \$submittedFrm.find('button[type=\"submit\"]');\n        var \$saveActivateLabel = \$saveActivateBtn.html();\n        var \$deactivateBtn = \$submittedFrm.find('button[type=\"reset\"]');\n        \$saveActivateBtn.html('<i class=\"fas fa-spinner fa-spin\"></i> ' + \$saveActivateLabel).prop('disabled', true);\n\n        WHMCS.http.jqClient.post({\n            url: \"";
echo routePath("admin-setup-authn-activate");
echo "\",\n            data: \$submittedFrm.serialize(),\n            dataType: 'json'\n        }).done(function (data) {\n            \$saveActivateBtn.prop('disabled', false);\n            if (data.status == 'success') {\n                \$('#integration-' + \$provider).modal('hide');\n                var \$btnProviderModal = \$('#btnProviderModal_' + \$provider);\n                \$btnProviderModal.html('";
echo AdminLang::trans("home.manage");
echo "')\n                    .addClass('btn-primary')\n                    .removeClass('btn-success');\n\n                \$deactivateBtn.removeClass('hidden').show();\n                \$saveActivateBtn.html('";
echo AdminLang::trans("global.save");
echo "');\n\n            } else {\n                \$saveActivateBtn.html(\$saveActivateLabel);\n                showIntegrationProviderError(\$submittedFrm, '');\n            }\n        }).fail(function () {\n            \$saveActivateBtn.html(\$saveActivateLabel).prop('disabled', false);\n            showIntegrationProviderError(\$submittedFrm, '<strong>System Error.</strong> Please refresh the page and try again.');\n        });\n    });\n\n    ";
if ($moduleToActivate) {
    echo "        \$('#btnProviderModal_";
    echo $moduleToActivate;
    echo "').click();\n    ";
}
echo "});\n\nfunction showIntegrationProviderError(submittedFrm, content) {\n    var \$errorAlert = submittedFrm.find('.alert');\n\n    if (content) {\n        \$errorAlert.html(content);\n    }\n\n    if (!\$errorAlert.is(':visible')) {\n        \$errorAlert.hide().removeClass('hidden').slideDown();\n    } else {\n        WHMCS.ui.effects.errorShake(submittedFrm.find('.alert-container'));\n    }\n}\n</script>\n";

?>