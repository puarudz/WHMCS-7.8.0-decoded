<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$values = array("inputAccountType" => "", "inputBankName" => "", "inputBankAcctHolderName" => "", "inputRoutingNumber" => "", "inputAccountNumber" => "");
$readOnly = "";
if (isset($payMethod)) {
    $payment = $payMethod->payment;
    $values["inputBankName"] = $payment->getBankName();
    $values["inputBankAcctHolderName"] = $payment->getAccountHolderName();
    $values["inputRoutingNumber"] = $payment->getRoutingNumber();
    $values["inputAccountNumber"] = $payment->getAccountNumber();
    foreach ($values as $key => $value) {
        $values[$key] = "value=\"" . $value . "\"";
    }
    $values["inputAccountType"] = $payment->getAccountType();
}
echo "\n<div class=\"payMethodTypeForm typeBankAccount row\">\n    <div class=\"form-group col-sm-12\">\n        <label for=\"inputAccountType\">Account Type</label>\n        <select class=\"form-control\" name=\"bankaccttype\">\n            <option value=\"Checking\"";
echo $values["inputAccountType"] === "Checking" ? " selected" : "";
echo ">Checking</option>\n            <option value=\"Savings\"";
echo $values["inputAccountType"] === "Savings" ? " selected" : "";
echo ">Savings</option>\n        </select>\n    </div>\n\n    <div class=\"form-group col-sm-12\">\n        <label for=\"inputBankAcctHolderName\">Account Holder Name</label>\n        <input type=\"text\"\n               id=\"inputBankAcctHolderName\"\n               name=\"bankacctholdername\"\n               class=\"form-control\"\n            ";
echo $values["inputBankAcctHolderName"];
echo "        >\n    </div>\n\n    <div class=\"form-group col-sm-12\">\n        <label for=\"inputBankName\">Bank Name</label>\n        <input type=\"text\"\n               id=\"inputBankName\"\n               name=\"bankname\"\n               class=\"form-control\"\n            ";
echo $values["inputBankName"];
echo "        >\n    </div>\n\n    <div class=\"form-group col-sm-12\">\n        <label for=\"inputRoutingNumber\">Sort Code / Routing Number</label>\n        <input type=\"text\"\n               id=\"inputRoutingNumber\"\n               name=\"bankroutingnum\"\n               data-enforce-format=\"number\"\n               class=\"form-control\"\n            ";
echo $values["inputRoutingNumber"];
echo "        >\n    </div>\n\n    <div class=\"form-group col-sm-12\">\n        <label for=\"inputAccountNumber\">Account Number</label>\n        <input type=\"text\"\n               id=\"inputAccountNumber\"\n               name=\"bankacctnum\"\n               data-enforce-format=\"number\"\n               class=\"form-control\"\n            ";
echo $values["inputAccountNumber"];
echo "        >\n    </div>\n</div>\n\n";
echo WHMCS\View\Asset::jsInclude("jquery.payment.js");
echo "\n<script>\n(function (\$) {\n    \$(document).ready(function () {\n        \$('input[data-enforce-format=\"number\"]').payment('restrictNumeric');\n    });\n})(jQuery);\n</script>\n";

?>