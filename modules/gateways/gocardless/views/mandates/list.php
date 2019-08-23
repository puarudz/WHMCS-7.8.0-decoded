<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if ($message) {
    echo WHMCS\View\Helper::alert($message);
}
if (count($mandates) == 0) {
    echo WHMCS\View\Helper::alert("There are no mandates in the current list");
} else {
    echo "    <div id=\"divError\" class=\"alert alert-danger hidden\"></div>\n    <table class=\"table table-striped table-condensed\">\n        <thead>\n            <tr>\n                <th>Mandate ID</th>\n                <th>Reference</th>\n                <th>Status</th>\n                <th>Next Charge Date</th>\n                <th></th>\n            </tr>\n        </thead>\n        <tbody>\n            ";
    foreach ($mandates as $mandate) {
        echo "                <tr>\n                    <td>";
        echo $mandate["id"];
        echo "</td>\n                    <td>";
        echo $mandate["reference"];
        echo "</td>\n                    <td>";
        echo $mandate["status"];
        echo "</td>\n                    <td>\n                        ";
        if ($mandate["next_possible_charge_date"]) {
            echo WHMCS\Carbon::parse($mandate["next_possible_charge_date"])->toAdminDateFormat();
        } else {
            echo "-";
        }
        echo "                    </td>\n                    <td>\n                        <form action=\"";
        echo $routePath;
        echo "\">\n                            ";
        echo generate_token();
        echo "                            <input type=\"hidden\"\n                                   name=\"mandate_id\"\n                                   value=\"";
        echo $mandate["id"];
        echo "\"\n                            >\n                            ";
        if (array_key_exists("client_id", $mandate["metadata"])) {
            echo "                                <input type=\"hidden\"\n                                       name=\"client_id\"\n                                       value=\"";
            echo $mandate["metadata"]["client_id"];
            echo "\"\n                                >\n                            ";
        }
        echo "                        ";
        if ($mandate["status"] == "cancelled") {
            echo "                                <input type=\"hidden\" name=\"action\" value=\"reinstate\">\n                                <button type=\"button\" class=\"btn btn-default btn-process\">\n                                    Reinstate Mandate\n                                </button>\n                        ";
        } else {
            if (in_array($mandate["status"], $activeStatuses)) {
                echo "                            <input type=\"hidden\" name=\"action\" value=\"import\">\n                            <input type=\"hidden\"\n                                   name=\"customer\"\n                                   value=\"";
                echo $mandate["links"]["customer"];
                echo "\"\n                            >\n                            <button type=\"button\" class=\"btn btn-default btn-process\">\n                                Import Mandate\n                            </button>\n                        ";
            }
        }
        echo "                        </form>\n                    </td>\n                </tr>\n            ";
    }
    echo "        </tbody>\n    </table>\n    ";
}
echo "<script>\n    jQuery(document).ready(function() {\n        var modal = jQuery('#modalAjax');\n\n        if (modal.children('div[class=\"modal-dialog\"]').length) {\n            modal.children('div[class=\"modal-dialog\"]').addClass('modal-lg');\n        }\n        jQuery(document).off('click', '.btn-process');\n        jQuery(document).on('click', '.btn-process', function() {\n            var self = jQuery(this),\n                modalForm = self.closest('form'),\n                modal = jQuery('#modalAjax');\n            modal.find('.loader').show();\n            WHMCS.http.jqClient.jsonPost({\n                url: modalForm.attr('action'),\n                data: modalForm.serialize(),\n                success: function(data) {\n                    updateAjaxModal(data);\n                },\n                error: function (errorMessage) {\n                    var errorDiv = jQuery('#divError');\n                    if (errorDiv instanceof \"undefined'\") {\n                        modal.html('<div id=\"divError\" class=\"alert alert-danger hidden\"></div>')\n                        errorDiv = jQuery('#divError');\n                    }\n                    errorDiv.slideUp('fast');\n                    if (errorDiv.hasClass('hidden')) {\n                        errorDiv.removeClass('hidden');\n                    }\n                    errorDiv.html(errorMessage);\n                    errorDiv.slideDown('fast');\n                },\n                fail: function (failMessage) {\n                    var message = 'An error occurred while communicating with the server' +\n                        '. Please try again.';\n                    if (failMessage) {\n                        message = failMessage;\n                    }\n                    modal.find('.modal-body').html(message);\n                },\n                always: function() {\n                    modal.find('.loader').fadeOut();\n                }\n            });\n        });\n    });\n</script>\n";

?>