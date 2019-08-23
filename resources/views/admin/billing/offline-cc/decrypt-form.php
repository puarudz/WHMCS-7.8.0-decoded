<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$client = $invoice->client;
$payMethods = $invoice->client->payMethods->localCreditCards();
echo "\n<div class=\"row\">\n    <div class=\"col-xs-6\">\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\">\n                ";
echo AdminLang::trans("emailtpls.typeinvoice");
echo "                <a href=\"invoices.php?action=edit&id=";
echo urlencode($invoice->id);
echo "\" target=\"_blank\">\n                    #";
echo $invoice->id;
echo "                </a>\n            </div>\n            <div class=\"panel-body\">\n                <div class=\"row\">\n                    <div class=\"col-xs-6\">\n                        ";
echo AdminLang::trans("mergefields.datecreated");
echo ":\n                    </div>\n                    <div class=\"col-xs-6\">\n                        ";
echo $invoice->dateCreated->toAdminDateFormat();
echo "                    </div>\n                </div>\n\n                <div class=\"row\">\n                    <div class=\"col-xs-6\">\n                        ";
echo AdminLang::trans("fields.duedate");
echo ":\n                    </div>\n                    <div class=\"col-xs-6\">\n                        ";
echo $invoice->dateDue->toAdminDateFormat();
echo "                    </div>\n                </div>\n\n                <div class=\"row\">\n                    <div class=\"col-xs-6\">\n                        ";
echo AdminLang::trans("fields.subtotal");
echo ":\n                    </div>\n                    <div class=\"col-xs-6\">\n                        ";
echo formatCurrency($invoice->subtotal, $client->currencyId);
echo "                    </div>\n                </div>\n\n                <div class=\"row\">\n                    <div class=\"col-xs-6\">\n                        ";
echo AdminLang::trans("general.tabcredit");
echo ":\n                    </div>\n                    <div class=\"col-xs-6\">\n                        ";
echo formatCurrency($invoice->credit, $client->currencyId);
echo "                    </div>\n                </div>\n\n                <div class=\"row\">\n                    <div class=\"col-xs-6\">\n                        ";
echo AdminLang::trans("fields.tax");
echo ":\n                    </div>\n                    <div class=\"col-xs-6\">\n                        ";
echo formatCurrency($invoice->tax1, $client->currencyId);
echo "                    </div>\n                </div>\n\n                <div class=\"row\">\n                    <div class=\"col-xs-6\">\n                        <b>";
echo AdminLang::trans("fields.total");
echo ":</b>\n                    </div>\n                    <div class=\"col-xs-6\">\n                        <b>";
echo formatCurrency($invoice->total, $client->currencyId);
echo "</b>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n\n    <div class=\"col-xs-6\">\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\">\n                ";
echo $client->firstName . " " . $client->lastName;
if ($client->companyName) {
    echo " (" . $client->companyName . ")";
}
echo "            </div>\n            <div class=\"panel-body\">\n                ";
echo $client->email;
echo "<br>\n                ";
echo $client->address1;
echo $client->address2 ? ", " . $client->address2 : "";
echo "<br>\n                ";
echo $client->city;
echo ", ";
echo $client->state;
echo ", ";
echo $client->postcode;
echo "<br>\n\n                ";
echo $client->countryName;
echo "<br>\n                ";
echo $client->phoneNumber;
echo "            </div>\n        </div>\n    </div>\n</div>\n\n<div class=\"row\">\n    <div class=\"col-xs-12\">\n        <div class=\"panel panel-default\">\n            <div class=\"panel-body\">\n                ";
if (0 < count($payMethods)) {
    echo "\n                <div class=\"row\" id=\"ccDecryptControls\">\n                    <div class=\"col-xs-12\">\n                        <p>";
    echo AdminLang::trans("offlineccp.entercchashmsg");
    echo "</p>\n                        <div class=\"alert alert-danger text-center cc-hash-errors\" style=\"display: none\"></div>\n                    </div>\n                    <div class=\"col-xs-6\">\n                        <select id=\"paymethod\" class=\"form-control\">\n                            ";
    $isDefault = true;
    foreach ($payMethods as $payMethod) {
        $isSelected = $invoice->payMethod && $payMethod->id === $invoice->payMethod->id;
        echo "                                <option\n                                    ";
        echo $isSelected ? "selected" : "";
        echo "                                    value=\"";
        echo $payMethod->id;
        echo "\">\n                                    ";
        echo $payMethod->payment->getDisplayName();
        echo "\n                                    ";
        if ($isSelected) {
            echo " (Selected)";
        } else {
            if ($isDefault) {
                echo " (Default)";
            }
        }
        echo "                                </option>\n                            ";
        $isDefault = false;
    }
    echo "                        </select>\n                    </div>\n                    <div class=\"col-xs-6\">\n                        <div class=\"input-group\">\n                            <input id=\"inputCcHash\"\n                                   type=\"password\"\n                                   name=\"cchash\"\n                                   autocomplete=\"off\"\n                                   placeholder=\"Paste CC Hash Here\"\n                                   class=\"form-control\"/>\n                            <span class=\"input-group-btn\">\n                                <a class=\"btn btn-default\" id=\"btnDecryptCcData\" type=\"button\"><i class=\"fas fa-check\"></i></a>\n                            </span>\n                        </div>\n                    </div>\n                </div>\n\n                <div class=\"row\" id=\"decryptedCcDataRow\" style=\"display: none;\">\n                    <div class=\"col-xs-6\" id=\"decryptedCcData\">\n                    </div>\n                    <div class=\"col-xs-6\">\n                        <div class=\"row\">\n                            <div class=\"col-xs-12\">\n                                    <input id=\"inputTransId\"\n                                           type=\"text\"\n                                           name=\"transid\"\n                                           autocomplete=\"off\"\n                                           placeholder=\"";
    echo AdminLang::trans("fields.transid");
    echo "\"\n                                           class=\"form-control\"/>\n                            </div>\n                            <div class=\"col-xs-12 top-margin-10\">\n                                <div class=\"btn-group\" role=\"group\">\n                                    <button\n                                        class=\"btn btn-success\"\n                                        id=\"btnTransactionSuccess\"\n                                        type=\"button\"\n                                        data-role=\"apply-transaction\" data-success=\"1\">\n                                            Successful\n                                    </button>\n                                    <button\n                                        class=\"btn btn-danger\"\n                                        id=\"btnTransactionFailure\"\n                                        type=\"button\"\n                                        data-role=\"apply-transaction\" data-success=\"0\">\n                                            Failed\n                                    </button>\n                                </div>\n\n                                <a class=\"btn btn-default pull-right\" id=\"hideDecryptedCcData\" type=\"button\">\n                                    Go Back\n                                </a>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n\n                ";
} else {
    echo "This user has no credit cards saved yet.";
}
echo "            </div>\n        </div>\n    </div>\n</div>\n\n<script>\n(function (\$) {\n    \$(document).ready(function () {\n        \$('#btnDecryptCcData').click(function() {\n            var self = this;\n\n            \$(self).addClass('disabled');\n            \$('.cc-hash-errors').slideUp();\n\n            WHMCS.http.jqClient.jsonPost({\n                url: '";
echo routePath("admin-billing-offline-cc-decrypt", $invoice->id);
echo "',\n                data: {\n                    'cchash': \$('#inputCcHash').val(),\n                    'paymethod': \$('#paymethod').val(),\n                    'token': '";
echo generate_token("plain");
echo "'\n                },\n                success: function (results) {\n                    if (results.body) {\n                        \$('#decryptedCcData').html(results.body);\n\n                        \$('#ccDecryptControls').slideUp(function() {\n                            \$('#decryptedCcDataRow').slideDown();\n                        });\n                    } else if (results.errorMsg) {\n                        \$('.cc-hash-errors').html(results.errorMsg).removeClass('hidden').slideDown();\n                    }\n                },\n                always: function () {\n                    \$(self).removeClass('disabled');\n                    \$('#inputCcHash').val('');\n                }\n            });\n        });\n\n        \$('#hideDecryptedCcData').click(function() {\n            \$('#decryptedCcData').html('');\n\n            \$('#decryptedCcDataRow').slideUp(function() {\n                \$('#ccDecryptControls').slideDown();\n            });\n        });\n\n        \$('[data-role=\"apply-transaction\"]').click(function() {\n            var self = this;\n\n            \$(self).addClass('disabled');\n\n            var transactionSuccess = \$(self).data('success');\n\n            WHMCS.http.jqClient.jsonPost({\n                url: '";
echo routePath("admin-billing-offline-cc-apply-transaction", $invoice->id);
echo "',\n                data: {\n                    'success': transactionSuccess,\n                    'paymethod': \$('#offlineTransactionPayMethod').val(),\n                    'token': '";
echo generate_token("plain");
echo "',\n                    'transid': \$('#inputTransId').val()\n                },\n                success: function (results) {\n                    \$('#modalAjax').modal('hide');\n                    window.location.reload();\n                },\n                always: function () {\n                    \$(self).removeClass('disabled');\n                }\n            });\n        });\n\n        var modal = \$('#modalAjax');\n\n        if (!\$(modal).data('cc-autoclean')) {\n            \$(modal).data('cc-autoclean', true);\n\n            \$(modal).on('hide.bs.modal', function () {\n                \$('#decryptedCcData').html('');\n            });\n        }\n    });\n})(jQuery);\n</script>\n";

?>