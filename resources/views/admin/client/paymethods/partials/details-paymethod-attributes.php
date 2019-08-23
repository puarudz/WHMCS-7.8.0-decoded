<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$isDefault = "";
$description = "";
$gatewayName = "";
$defaultDisabled = "";
if (isset($payMethod)) {
    $isDefault = $payMethod->isDefaultPayMethod() ? "checked" : "";
    $description = "value=\"" . $payMethod->getDescription() . "\"";
    $gatewayToken = "";
    $payment = $payMethod->payment;
    $gateway = $payMethod->getGateway();
    if ($gateway) {
        $gatewayName = $gateway->getConfiguration()["FriendlyName"]["Value"];
    }
} else {
    if ($forceDefault) {
        $isDefault = "checked=\"checked\"";
        $defaultDisabled = "disabled=\"disabled\"";
    }
}
if ($gatewayName) {
    echo "    <div class=\"form-group\">\n        <label>Gateway</label><br>";
    echo $gatewayName;
    echo "    </div>\n    ";
}
echo "<div class=\"row\">\n    <div class=\"col-sm-12\">\n        <div class=\"form-group\">\n            <label for=\"inputDescription\">Description</label>\n            <input type=\"text\"\n                id=\"inputDescription\"\n                name=\"description\"\n                ";
echo $description;
echo "                class=\"form-control\"\n                placeholder=\"Optional\">\n        </div>\n    </div>\n</div>\n\n";
$this->insert("client/paymethods/partials/details-billing-contact");
echo "\n<div class=\"row\">\n    <div class=\"col-sm-12\">\n        <label class=\"bottom-margin-10\">\n            <input type=\"checkbox\" id=\"inputIsDefault\" name=\"isDefault\" ";
echo $isDefault;
echo " ";
echo $defaultDisabled;
echo ">\n            Use by Default\n        </label>\n    </div>\n</div>\n";
if ($storageGateway) {
    echo "    <input type=\"hidden\" name=\"user_id\" value=\"";
    echo $client->id;
    echo "\">\n    <input type=\"hidden\" name=\"storageGateway\" value=\"";
    echo $storageGateway;
    echo "\" />\n    ";
}

?>