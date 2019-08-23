<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<form method=\"post\" action=\"";
echo routePath("admin-setup-notifications-provider-save", $provider->getName());
echo "\">\n    ";
echo generate_token();
echo "\n    ";
if ($errorMsg) {
    echo "        <div class=\"alert alert-danger\">\n            ";
    echo $errorMsg;
    echo "        </div>\n    ";
}
echo "\n    ";
foreach ($provider->settings() as $key => $values) {
    echo "\n        <div class=\"provider-config-field\">\n            <strong>";
    echo $values["FriendlyName"];
    echo "</strong>\n            ";
    echo moduleConfigFieldOutput(array_merge($values, array("Name" => "settings[" . $key . "]", "Value" => isset($settings[$key]) ? $settings[$key] : NULL)));
    echo "        </div>\n\n    ";
}
echo "\n</form>\n\n<style>\n.provider-config-field {\n    margin: 0 0 20px 0;\n}\n.provider-config-field strong {\n    display: block;\n    margin: 5px 0;\n}\n.provider-config-field input {\n    display: block;\n    margin: 5px 0;\n    max-width: 100%;\n    width: 100%;\n}\n</style>\n<script type=\"text/javascript\">\n    \$('#divDisableButton').remove();\n    \$('#modalAjaxLoader').before('<div id=\"divDisableButton\" class=\"pull-left\"><button class=\"btn btn-danger disable-provider";
echo $provider->isActive() ? "" : " disabled";
echo "\" data-provider=\"";
echo $provider->getName();
echo "\" ";
echo $provider->isActive() ? "" : "disabled=\"disabled\"";
echo ">";
echo AdminLang::trans("global.disable");
echo "</button></div>');\n    \$('.disable-provider').off('click').on('click', function() {\n        var provider = \$(this).data('provider');\n        WHMCS.http.jqClient.post(\n            '";
echo routePath("admin-setup-notifications-provider-disable");
echo "',\n            {\n                'token': csrfToken,\n                'provider': provider\n            },\n            function(data) {\n                if (data.successMsg) {\n                    jQuery.growl.notice({ title: data.successMsgTitle, message: data.successMsg });\n                }\n                \$('#modalAjax').modal('hide');\n                getNotificationProvidersStatus();\n            },\n            'json'\n        );\n    });\n</script>\n";

?>