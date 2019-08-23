<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$values = array("inputCardType" => "unknown", "inputCardNumber" => "", "inputCardExpiry" => "", "inputCardStart" => "", "inputCardIssueNumber" => "");
$readOnly = "";
$gatewayToken = "";
if (isset($payMethod)) {
    $payment = $payMethod->payment;
    $expiry = $payment->getExpiryDate();
    if ($expiry) {
        $values["inputCardExpiry"] = $expiry->format("m / y");
    }
    $startDate = $payment->getStartDate();
    if ($startDate) {
        $values["inputCardStart"] = $startDate->format("m / y");
    }
    $readOnly = "readonly";
    $values["inputCardNumber"] = $payment->getMaskedCardNumber();
    $values["inputCardIssueNumber"] = $payment->getIssueNumber();
    if ($payment instanceof WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
        $values["inputGatewayId"] = $payment->getRemoteToken();
        $gatewayToken = $payment->getRemoteToken();
    } else {
        $values["inputGatewayId"] = "no token";
    }
    foreach ($values as $key => $value) {
        $values[$key] = "value=\"" . $value . "\"";
    }
    $values["inputCardType"] = $payment->getCardType();
}
echo "<div class=\"row\">\n    <div class=\"col-sm-12 form-group\">\n        <label for=\"inputCardNumber\">\n            ";
echo AdminLang::trans("fields.cardnum");
echo "        </label>\n        <div class=\"input-group\" style=\"width: 100%\">\n            <input id=\"inputCardNumber\"\n                type=\"tel\"\n                name=\"ccnumber\"\n                autocomplete=\"off\"\n                class=\"form-control cc-number-field ";
echo strtolower($values["inputCardType"]);
echo "\"\n                ";
echo $values["inputCardNumber"];
echo "                ";
echo $readOnly;
echo "                placeholder=\"4444 5555 6666 1234\"/>\n            <span class=\"input-group-btn\">\n                ";
if ($payMethod && $payMethod->payment instanceof WHMCS\Payment\PayMethod\Adapter\CreditCard) {
    echo "                <button class=\"btn btn-default min-height-34\" id=\"btnShowCcHashControls\" type=\"button\">\n                    <i class=\"far fa-unlock\"></i>\n                </button>\n                <button class=\"btn btn-default hidden copy-to-clipboard min-height-34\"\n                   data-clipboard-target=\"#inputCardNumber\" id=\"btnCopyCcNumber\" type=\"button\">\n                    <img src=\"../assets/img/clippy.svg\" alt=\"Copy to clipboard\" width=\"15\">\n                </button>\n                ";
}
echo "            </span>\n        </div>\n        <span class=\"field-error-msg\">";
echo AdminLang::trans("clients.ccInvalid");
echo "</span>\n    </div>\n\n    <div class=\"col-sm-12 form-group\" id=\"ccHashControls\" style=\"display: none\">\n        <label for=\"inputCcHash\">\n            ";
echo AdminLang::trans("clients.entercchash");
echo "        </label>\n        <div class=\"input-group\">\n            <input id=\"inputCcHash\"\n                   type=\"password\"\n                   name=\"cchash\"\n                   autocomplete=\"off\"\n                   class=\"form-control\"/>\n            <span class=\"input-group-btn\">\n                <a class=\"btn btn-default\" id=\"btnDecryptCcData\" type=\"button\"><i class=\"fas fa-check\"></i></a>\n            </span>\n        </div>\n    </div>\n</div>\n";
if ($startDateEnabled || $issueNumberEnabled) {
    echo "<div class=\"row\">\n    <div class=\"col-sm-6\">\n        ";
    if ($startDateEnabled) {
        echo "        <div class=\"form-group\">\n            <label for=\"inputCardStart\">\n                ";
        echo AdminLang::trans("fields.startdate");
        echo "            </label>\n            <div>\n                <input type=\"text\"\n                       id=\"inputCardStart\"\n                       name=\"ccstartdate\"\n                       class=\"form-control\"\n                       ";
        echo $values["inputCardStart"];
        echo "                />\n            </div>\n        </div>\n        ";
    }
    echo "    </div>\n    <div class=\"col-sm-6\">\n        ";
    if ($issueNumberEnabled) {
        echo "        <div class=\"form-group\">\n            <label for=\"ccissuenum\">\n                ";
        echo AdminLang::trans("fields.issueno");
        echo "            </label>\n            <div>\n                <input type=\"text\"\n                       id=\"ccissuenum\"\n                       name=\"ccissuenum\"\n                       ";
        echo $values["inputCardIssueNumber"];
        echo "                       class=\"form-control\"\n                       autocomplete=\"off\"\n                       maxlength=\"4\"\n                       placeholder=\"\"\n                       title=\"\"\n                />\n            </div>\n\n        </div>\n        ";
    }
    echo "    </div>\n</div>\n";
}
echo "<div class=\"row\">\n    <div class=\"col-sm-6\">\n        <div class=\"form-group\">\n            <label for=\"inputCardExpiry\">\n                ";
echo AdminLang::trans("fields.expdate");
echo "            </label>\n            <div>\n                <input type=\"text\"\n                       id=\"inputCardExpiry\"\n                       name=\"ccexpirydate\"\n                       autocomplete=\"cc-exp\"\n                       class=\"form-control\"\n                       placeholder=\"MM / YY\"\n                    ";
echo $values["inputCardExpiry"];
echo "                />\n            </div>\n            <span class=\"field-error-msg\">";
echo AdminLang::trans("clients.ccExpiryInvalid");
echo "</span>\n        </div>\n    </div>\n    ";
if ($payMethodType === "RemoteCreditCard") {
    echo "        <div class=\"col-sm-6\">\n            <div class=\"form-group\">\n                <label for=\"cardcvv\">\n                    ";
    echo AdminLang::trans("fields.cardcvv");
    echo "                </label>\n                <div>\n                    <input type=\"tel\"\n                           id=\"cardcvv\"\n                           name=\"cardcvv\"\n                           class=\"form-control\"\n                           autocomplete=\"cc-cvc\"\n                           maxlength=\"4\"\n                           placeholder=\"123\"\n                    />\n                </div>\n                <span class=\"field-error-msg\">";
    echo AdminLang::trans("clients.cvvInvalid");
    echo "</span>\n            </div>\n        </div>\n    ";
}
echo "</div>\n";
if ($gatewayToken) {
    echo "    <div class=\"row\">\n        <div class=\"col-sm-12\">\n            <div class=\"form-group\">\n                <label for=\"inputGatewayToken\">Gateway Token</label>\n                <input class=\"form-control\" id=\"inputGatewayToken\" type=\"text\" value=\"";
    echo WHMCS\Input\Sanitize::encodeToCompatHTML($gatewayToken);
    echo "\" readonly/>\n            </div>\n        </div>\n    </div>\n    ";
}
echo "<div id=\"containerStorageInputControl\">\n";
if (!empty($gatewayInputControl)) {
    echo $gatewayInputControl;
} else {
    echo WHMCS\View\Asset::jsInclude("jquery.payment.js");
}
echo "</div>\n\n<script>\n(function (\$) {\n    \$(document).ready(function () {\n        var modal = \$('#modalAjax'),\n            ccNumberFieldEnabled = '";
echo empty($payMethod);
echo "',\n            ccForm = \$('#frmCreditCardDetails');\n\n        if (ccForm.find('#inputCardNumber').length) {\n            ccForm.find('#inputCardNumber').payment('formatCardNumber');\n            ccForm.find('#inputCardStart').payment('formatCardExpiry');\n            ccForm.find('#inputCardExpiry').payment('formatCardExpiry');\n            ccForm.find('#cardcvv').payment('formatCardCVC');\n            ccForm.find('#ccissuenum').payment('restrictNumeric');\n\n            \$.fn.showInputError = function () {\n                this.parents('.form-group').addClass('has-error').find('.field-error-msg').show();\n                return this;\n            };\n\n            window.creditCardValidate = function () {\n                ccForm.find('.form-group').removeClass('has-error');\n                ccForm.find('.field-error-msg').hide();\n\n                var cardNumber = ccForm.find('#inputCardNumber').val(),\n                    cardType = \$.payment.cardType(cardNumber),\n                    expiryDate = ccForm.find('#inputCardExpiry').payment('cardExpiryVal'),\n                    cvcInput = ccForm.find('#cardcvv'),\n                    cvc = '',\n                    validateCvc = false,\n                    complete = true;\n\n                if (cvcInput.length) {\n                    validateCvc = true;\n                    cvc = ccForm.find('#cardcvv').val();\n                }\n                if (cvc) {\n                    validateCvc = false;\n                }\n\n                if (ccNumberFieldEnabled && !\$.payment.validateCardNumber(cardNumber)) {\n                    ccForm.find('#inputCardNumber').showInputError();\n                    complete = false;\n                }\n                if (!\$.payment.validateCardExpiry(expiryDate)) {\n                    ccForm.find('#inputCardExpiry').showInputError();\n                    complete = false;\n                }\n                if (validateCvc && !\$.payment.validateCardCVC(cvc, cardType)) {\n                    ccForm.find('#cardcvv').showInputError();\n                    complete = false;\n                }\n                return complete;\n            };\n\n            addAjaxModalSubmitEvents('creditCardValidate');\n        }\n\n        \$('#btnShowCcHashControls').click(function() {\n            \$('#ccHashControls').slideToggle();\n        });\n\n        \$('#btnDecryptCcData').click(function() {\n            \$('.gateway-errors').slideUp();\n\n            WHMCS.http.jqClient.jsonPost({\n                url: '";
echo routePath("admin-client-paymethods-decrypt-cc-data", $client->id, $payMethod->id);
echo "',\n                data: \$('#frmCreditCardDetails').serialize(),\n                success: function (results) {\n                    if (results.ccnum) {\n                        \$('#inputCardNumber').val(results.ccnum);\n                        ccNumberFieldEnabled = 1;\n\n                        \$('#btnShowCcHashControls').addClass('hidden');\n                        \$('#btnCopyCcNumber').removeClass('hidden');\n\n                        \$('#ccHashControls').slideUp();\n                    } else if (results.errorMsg) {\n                        \$('.gateway-errors').html(results.errorMsg).removeClass('hidden').slideDown();\n                    }\n                },\n                always: function () {\n                    \$('#inputCcHash').val('');\n                }\n            });\n        });\n\n        if (!\$(modal).data('cc-autoclean')) {\n            \$(modal).data('cc-autoclean', true);\n\n            \$(modal).on('hide.bs.modal', function () {\n                \$('#inputCardNumber').val('');\n                removeAjaxModalSubmitEvents('creditCardValidate');\n            });\n        }\n    });\n})(jQuery);\n</script>\n";

?>