<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<button aria-label=\"Close\" class=\"close\" data-dismiss=\"modal\" type=\"button\"><span aria-hidden=\"true\">&times;</span></button>\n\n<div class=\"logo\"><img src=\"../assets/img/marketconnect/";
echo $serviceOffering["vendorSystemName"];
echo "/logo.png\" style=\"max-height:";
echo $serviceOffering["vendorSystemName"] == "sitelock" ? "68" : "85";
echo "px;\"></div>\n<div class=\"title\">\n    <h3>";
echo $serviceOffering["serviceTitle"];
echo "</h3>\n    <h4>From ";
echo $serviceOffering["vendorName"];
echo "</h4>\n</div>\n<div class=\"clearfix\"></div>\n\n<div>\n    <ul class=\"nav nav-tabs\" role=\"tablist\">\n        <li class=\"active\" role=\"presentation\">\n            <a aria-controls=\"overview\" data-toggle=\"tab\" href=\"#overview\" role=\"tab\">Overview</a>\n        </li>\n        <li role=\"presentation\">\n            <a aria-controls=\"products\" data-toggle=\"tab\" href=\"#products\" role=\"tab\">Products</a>\n        </li>\n        <li role=\"presentation\">\n            <a aria-controls=\"settings\" data-toggle=\"tab\" href=\"#settings\" role=\"tab\">Promotion Settings</a>\n        </li>\n        <li role=\"presentation\">\n            <a aria-controls=\"other\" data-toggle=\"tab\" href=\"#other\" role=\"tab\">Other Settings</a>\n        </li>\n        <li class=\"pull-right\" role=\"presentation\">\n            <a aria-controls=\"deactivate\" class=\"deactivate btn-deactivate\" data-toggle=\"tab\" href=\"#deactivate\" role=\"tab\" data-service=\"";
echo $serviceOffering["vendorSystemName"];
echo "\">Deactivate</a>\n        </li>\n    </ul>\n    <div class=\"tab-content\">\n        <div class=\"tab-pane active\" id=\"overview\" role=\"tabpanel\">\n            <div class=\"content-padded\">\n                <h3>You are selling ";
echo $serviceOffering["vendorName"];
echo "</h3>\n                <h4>";
echo $serviceOffering["tagLine"];
echo "</h4>\n\n                <br><br>\n\n                ";
if ($serviceOffering["supportsSso"]) {
    echo "                    <div class=\"sso-container\">\n                        <div class=\"row\">\n                            <div class=\"col-sm-6 col-sm-offset-3\">\n                                <button class=\"btn btn-default btn-lg btn-block btn-sso-service\" data-service=\"";
    echo $serviceOffering["vendorSystemName"];
    echo "\">\n                                    Login to ";
    echo $serviceOffering["vendorName"];
    echo " Control Panel\n                                </button>\n                            </div>\n                        </div>\n                    </div>\n                ";
}
echo "                ";
if ($serviceOffering["serviceList"]) {
    echo "                    <div class=\"sso-container\">\n                        <div class=\"row\">\n                            <div class=\"col-sm-4 text-right\" style=\"padding-top:6px;\">\n                                Choose Domain to Manage:\n                            </div>\n                            <div class=\"col-sm-6 text-left\">\n                                <select id=\"serviceId\" name=\"serviceId\" data-value-field=\"id\" placeholder=\"Search by domain or client name\" class=\"form-control service-select\"></select>\n                            </div>\n                            <div class=\"col-sm-2\">\n                                <button type=\"button\" id=\"goButton\" value=\"Go\" class=\"btn btn-default btn-sso-client-service pull-left\">Go</button>\n                            </div>\n                        </div>\n                    </div>\n                ";
}
echo "            </div>\n        </div>\n        <div class=\"tab-pane\" id=\"products\" role=\"tabpanel\">\n            <div class=\"content-padded\">\n            ";
$this->insert("shared/configuration-products", array("currency" => $currency, "billingCycles" => $billingCycles, "products" => $products, "serviceTerms" => $serviceTerms));
echo "            </div>\n        </div>\n        <div class=\"tab-pane\" id=\"settings\" role=\"tabpanel\">\n            <div class=\"content-padded\">\n            ";
$this->insert("shared/configuration-promotions", array("serviceOffering" => $serviceOffering, "service" => $service));
echo "            </div>\n        </div>\n        <div class=\"tab-pane\" id=\"other\" role=\"tabpanel\">\n            <div class=\"content-padded\">\n                ";
$this->insert("shared/configuration-general-settings", array("serviceOffering" => $serviceOffering, "service" => $service));
echo "            </div>\n        </div>\n    </div>\n</div>\n\n<script>\n\$(document).ready(function() {\n    \$(\".product-status\").bootstrapSwitch({size: 'small', onText: 'Active', onColor: 'success', offText: 'Disabled'});\n    \$(\".promo-switch\").bootstrapSwitch({size: 'mini'});\n    \$(\".setting-switch\").bootstrapSwitch({size: 'mini'});\n\n    var searchSelectize = \$('.service-select').selectize(\n        {\n            valueField: jQuery('.service-select').attr('data-value-field'),\n            labelField: 'name',\n            searchField: ['name', 'domain'],\n            create: false,\n            maxItems: 1,\n            render: {\n                item: function(item, escape) {\n                    return '<div><span class=\"name\">' + escape(item.name) +\n                        ' - ' + escape(item.domain) +\n                        ' - #' + escape(item.id) + '</span></div>';\n                },\n                option: function(item, escape) {\n                    return '<div><span class=\"name\">' + escape(item.name) +\n                        ' - ' + escape(item.domain) +\n                        ' - #' + escape(item.id) + '</span></div>';\n                }\n            },\n            load: function(query, callback) {\n                jQuery.ajax({\n                    url: window.location.href,\n                    type: 'POST',\n                    dataType: 'json',\n                    data: {\n                        token: csrfToken,\n                        action: 'getServices',\n                        service: '";
echo $serviceOffering["vendorSystemName"];
echo "',\n                        query: query\n                    },\n                    error: function() {\n                        callback();\n                    },\n                    success: function(res) {\n                        callback(res);\n                    }\n                });\n            },\n            onChange: function(value) {\n                var go = \$('#goButton');\n                if (go.length) {\n                    if (value.length && value !== currentValue) {\n                        go.click();\n                    }\n                }\n            },\n            onFocus: function() {\n                currentValue = searchSelectize.getValue();\n                searchSelectize.clear();\n            },\n            onBlur: function()\n            {\n                if (searchSelectize.getValue() === '') {\n                    searchSelectize.setValue(currentValue);\n                }\n            }\n        });\n    var currentValue = '';\n\n    if (searchSelectize.length) {\n        /**\n         * selectize assigns any items to an array. In order to be able to run additional\n         * functions on this (like auto-submit and clear).\n         *\n         * @link https://github.com/brianreavis/selectize.js/blob/master/examples/api.html\n         */\n        searchSelectize = searchSelectize[0].selectize;\n    }\n});\n</script>\n";

?>