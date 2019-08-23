<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<form method=\"post\" action=\"";
echo routePath("admin-setup-notifications-rule-save");
echo "\" class=\"notification-rules\">\n    ";
echo generate_token();
echo "    <input type=\"hidden\" name=\"rule_id\" value=\"";
echo $rule->id;
echo "\">\n\n    <div class=\"input-group\">\n        <span class=\"input-group-addon\" id=\"rule-name\"><strong>";
echo AdminLang::trans("notifications.ruleName");
echo "</strong></span>\n        <input type=\"text\" name=\"description\" class=\"form-control\" placeholder=\"A name or description you can use to identify this rule.\" value=\"";
echo e($rule->description);
echo "\" aria-describedby=\"rule-name\">\n    </div>\n\n    <div class=\"section-container\">\n\n        <h2>";
echo AdminLang::trans("notifications.event");
echo "</h2>\n\n        <p>";
echo AdminLang::trans("notifications.eventDescription");
echo "</p>\n\n        <ul class=\"nav nav-pills event-types\">\n            ";
foreach ($eventTypes as $eventTypeName => $eventTypeDisplayName) {
    echo "                <li";
    echo $rule->event_type == $eventTypeName ? " class=\"active\"" : "";
    echo ">\n                    <a href=\"#\" data-name=\"";
    echo $eventTypeName;
    echo "\">\n                        ";
    echo $eventTypeDisplayName;
    echo "                    </a>\n                </li>\n            ";
}
echo "        </ul>\n\n        ";
foreach ($events as $eventType => $eventOptions) {
    echo "            <select name=\"events[";
    echo $eventType;
    echo "][]\" class=\"form-control events ";
    echo $eventType;
    echo " hidden\" multiple=\"true\">\n                ";
    foreach ($eventOptions as $eventName => $eventDisplayName) {
        echo "                    <option value=\"";
        echo $eventName;
        echo "\"";
        echo in_array($eventName, $rule->events) ? " selected" : "";
        echo ">\n                        ";
        echo $eventDisplayName;
        echo "                    </option>\n                ";
    }
    echo "            </select>\n        ";
}
echo "\n    </div>\n\n    <div class=\"section-container\">\n\n        <h2>";
echo AdminLang::trans("fields.conditions");
echo "</h2>\n        <p>";
echo AdminLang::trans("notifications.conditionsDescription");
echo "</p>\n        ";
foreach ($conditions as $event => $fields) {
    echo "            <div class=\"conditions ";
    echo $event;
    echo " hidden\">\n                <div class=\"row field-rows\">\n                    ";
    foreach ($fields as $label => $output) {
        echo "                        <div class=\"col-sm-3\">\n                            <span>";
        echo $label;
        echo "</span>\n                            ";
        echo $output;
        echo "                        </div>\n                    ";
    }
    echo "                </div>\n            </div>\n        ";
}
echo "\n    </div>\n\n    <div class=\"section-container\">\n\n        <h2>";
echo AdminLang::trans("notifications.settings");
echo "</h2>\n\n        <ul class=\"nav nav-pills providers\">\n            ";
foreach ($providers as $provider) {
    echo "                <li";
    echo $rule->provider == $provider->name ? " class=\"active\"" : "";
    echo "><a href=\"#\" data-name=\"";
    echo $provider->name;
    echo "\">";
    echo $provider->initObject()->getName();
    echo "</a></li>\n            ";
}
echo "        </ul>\n";
foreach ($providers as $provider) {
    echo "<div class=\"provider-config " . $provider->name . " hidden\"><div class=\"row field-rows\">";
    $notificationsInterface = new WHMCS\Module\Notification();
    $notificationsInterface->load($provider->name);
    $obj = $provider->initObject();
    foreach ($obj->notificationSettings() as $setting => $options) {
        echo "                <div class=\"col-sm-6\">\n                    <span>";
        echo $options["FriendlyName"];
        echo "</span>\n                    ";
        switch ($options["Type"]) {
            case "dynamic":
                $settingParts = explode("|", $rule->provider_config[$setting], 2);
                echo "<select class=\"form-control dynamic-lookup\" data-provider=\"" . $provider->name . "\" data-field=\"" . $setting . "\"><option value=\"" . $settingParts[0] . "\">" . $settingParts[1] . "</option></select><input type=\"hidden\" name=\"provider_config[" . $provider->name . "][" . $setting . "]\" value=\"" . $rule->provider_config[$setting] . "\" id=\"providerconfig-" . $setting . "\">";
                break;
            case "yesno":
                echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"provider_config[" . $provider->name . "][" . $setting . "]\"" . ($rule->provider_config[$setting] ? " checked" : "") . "> Enable</label>";
                break;
            case "textarea":
                $cols = isset($options["Cols"]) ? $options["Cols"] : "60";
                $rows = isset($options["Rows"]) ? $options["Rows"] : "5";
                $value = $rule->provider_config[$setting];
                if (!$value && isset($options["Default"])) {
                    $value = $options["Default"];
                }
                echo "<textarea class=\"form-control\" name=\"provider_config[" . $provider->name . "][" . $setting . "]\" cols=\"" . $cols . "\" rows=\"" . $rows . "\">" . $value . "</textarea>";
                break;
            case "system":
                break;
            default:
                echo "<input type=\"text\" name=\"provider_config[" . $provider->name . "][" . $setting . "]\" class=\"form-control\" value=\"" . $rule->provider_config[$setting] . "\">";
                break;
        }
        echo "                    <div>\n                        ";
        echo $options["Description"];
        echo "                    </div>\n                </div>\n            ";
    }
    echo "            </div>\n        </div>\n    ";
}
echo "\n    </div>\n\n    <input type=\"hidden\" name=\"eventtype\" value=\"";
echo $rule->event_type;
echo "\" id=\"inputEventType\">\n    <input type=\"hidden\" name=\"notificationProvider\" value=\"";
echo $rule->provider;
echo "\" id=\"inputNotificationProvider\">\n</form>\n\n<style>\n.notification-rules h2 {\n    margin: 0 0 15px 0;\n}\n.notification-rules .nav-pills > li > a {\n    border: 1px solid #ddd;\n    margin: 0 4px 5px 0;\n}\n.notification-rules .field-rows span {\n    margin-bottom: 2px;\n    font-weight: bold;\n}\n.notification-rules .field-rows input,\n.notification-rules .field-rows select {\n    margin-bottom: 5px;\n}\n.notification-rules .section-container {\n    margin: 15px -15px;\n    padding: 15px 15px 0;\n    border-top: 2px dashed #ccc;\n}\n</style>\n<script>\n\$(document).ready(function() {\n    \$('.event-types a').click(function(e) {\n        e.preventDefault();\n        var eventName = \$(this).data('name');\n        \$('.event-types li').removeClass('active');\n        \$(this).parent('li').addClass('active');\n        \$('.events').hide();\n        \$('.events.' + eventName).removeClass('hidden').fadeIn();\n        \$('#inputEventType').val(eventName);\n        \$('.conditions').hide();\n        \$('.conditions.' + eventName).removeClass('hidden').fadeIn();\n    });\n    \$('.event-types li.active a').click();\n    \$('.providers a').click(function(e) {\n        e.preventDefault();\n        \$('.providers li').removeClass('active');\n        \$(this).parent('li').addClass('active');\n        \$('#inputNotificationProvider').val(\$(this).data('name'));\n        \$('.provider-config').hide();\n        \$('.provider-config.' + \$(this).data('name')).removeClass('hidden').fadeIn();\n    });\n    \$('.providers li.active a').click();\n\n    \$('.dynamic-lookup').each(function(index) {\n        var \$provider = \$(this).data('provider');\n        var \$field = \$(this).data('field');\n\n        \$(this).selectize({\n            valueField: 'id',\n            labelField: 'name',\n            searchField: 'name',\n            options: [],\n            create: false,\n            loadThrottle: 300,\n            preload: true,\n            render: {\n                option: function(item, escape) {\n                    var description = '';\n                    if (typeof item.description !== 'undefined' && item.description !== '') {\n                        description = escape(item.description);\n                    }\n                    return '<div>' +\n                        '<span>' + escape(item.name) + '</span><br>' +\n                        '<small><em>' + description + ' ' + escape(item.id) + '</em></small>' +\n                        '</div>';\n                }\n            },\n            onItemAdd: function(value, item) {\n                \$('#providerconfig-' + \$field).val(value + '|' + item[0].textContent);\n            },\n            load: function(query, callback) {\n                \$.ajax({\n                    url: '";
echo routePath("admin-setup-notifications-provider-dynamic-field");
echo "',\n                    type: 'POST',\n                    dataType: 'json',\n                    data: {\n                        provider: \$provider,\n                        field: \$field,\n                        q: query,\n                        token: '";
echo generate_token("plain");
echo "'\n                    },\n                    error: function() {\n                        callback();\n                    },\n                    success: function(result) {\n                        callback(result.values);\n                    }\n                });\n            }\n        });\n    });\n});\n</script>\n";

?>