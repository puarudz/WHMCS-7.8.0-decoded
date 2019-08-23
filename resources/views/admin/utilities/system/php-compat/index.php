<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<p>\n    ";
echo AdminLang::trans("phpCompatUtil.compatUtilDesc");
echo "</p>\n<br/>\n<div class=\"row\">\n    <div class=\"col-sm-3\">\n        <h2>";
echo AdminLang::trans("phpCompatUtil.report");
echo "</h2>\n    </div>\n    <div class=\"col-sm-6\">\n        <div id=\"containerLoading\" class=\"hidden text-center\">\n            <img src=\"";
echo WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/images/loader.gif";
echo "\" />\n        </div>\n    </div>\n    <div class=\"col-sm-3\">\n        <div class=\"pull-right text-right\" >\n            <button id=\"btnRescan\" type=\"button\" class=\"btn btn-primary btn-lg\">";
echo AdminLang::trans($needsInitialScan ? "phpCompatUtil.scan" : "phpCompatUtil.rescan");
echo "</button>\n            <div>\n                <span class=\"small\">";
echo AdminLang::trans("phpCompatUtil.updated");
echo "</span><br/>\n                <span id=\"txtLastScanned\" class=\"small\">";
echo $lastScanned;
echo "</span>\n            </div>\n        </div>\n    </div>\n</div>\n<div class=\"row\">\n    <div id=\"containerAllVersionsDetails\" class=\"col-sm-12\">\n        ";
if ($needsInitialScan) {
    echo "                <br/>\n                <div class=\"panel panel-default\">\n                    <div class=\"panel-body\">\n                    ";
    echo AdminLang::trans("phpCompatUtil.clickToScan");
    echo "                    </div>\n                </div>\n                ";
} else {
    $this->insert("utilities/system/php-compat/assessment/all-versions-details");
}
echo "    </div>\n</div>\n\n<script>\n    jQuery(document).ready(function() {\n        jQuery('#btnRescan').click(function (event) {\n            var btn = \$(this);\n            btn.prop(\"disabled\", true);\n\n            var containerLoader = \$('#containerLoading');\n            var containerDetails = \$('#containerAllVersionsDetails');\n\n            containerLoader.fadeOut('fast').removeClass('hidden').fadeIn();\n            containerDetails.fadeTo('fast', 0.5);\n\n            WHMCS.http.jqClient.post(\n                '";
echo routePath("admin-utilities-system-phpcompat-scan");
echo "',\n                { token: csrfToken }\n            )\n            .done(function(data) {\n                containerDetails.html(data.allVersionsHtml);\n                \$('.tblcompat').each(function (i, el){\n                    WHMCS.ui.dataTable.getTableById(el.id, {});\n                });\n                \$('#txtLastScanned').html(data.lastScanned);\n            })\n            .fail(function(data) {\n                var msg = '";
echo AdminLang::trans("phpCompatUtil.scanError");
echo "';\n                if (typeof data.responseJSON !== 'undefined' && data.responseJSON.errorMessage) {\n                    msg = data.responseJSON.errorMessage;\n                }\n\n                containerDetails.html(\n                    '<div class=\"panel panel-default\"><div class=\"panel-body\">' + msg + '</div></div>'\n                );\n            })\n            .always(function(){\n                btn.prop(\"disabled\", false);\n                btn.html(\"";
echo AdminLang::trans("phpCompatUtil.rescan");
echo "\");\n                containerDetails.fadeTo('slow', 1);\n                containerLoader.fadeOut('slow').addClass('hidden');\n            });\n        });\n    });\n</script>\n\n";

?>