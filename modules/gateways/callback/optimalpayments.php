<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$whmcs->load_function("client");
$whmcs->load_function("cc");
$invoiceID = (int) $_REQUEST["MD"];
$callbackSuccess = false;
if (!$_REQUEST["failed"]) {
    $gateway = WHMCS\Module\Gateway::factory("optimalpayments");
    $gatewayParams = $gateway->getParams();
    logTransaction($gatewayParams["paymentmethod"], $_REQUEST, "Received");
    $paRes = $_REQUEST["PaRes"];
    $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("optimalpayments");
    $params = $storage->getValue("invoicedata." . (int) $invoiceID, array());
    if (empty($params)) {
        $params = getCCVariables($invoiceID);
    }
    $confNumber = $storage->getValue("optimalpaymentsconfirmationnumber");
    $xml = "<ccAuthenticateRequestV1\n    xmlns=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\"\n    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n    xsi:schemaLocation=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\">\n    <merchantAccount>\n    <accountNum>" . $gatewayParams["accountnumber"] . "</accountNum>\n    <storeID>" . $gatewayParams["merchantid"] . "</storeID>\n    <storePwd>" . $gatewayParams["merchantpw"] . "</storePwd>\n    </merchantAccount>\n    <confirmationNumber>" . $confNumber . "</confirmationNumber>\n    <paymentResponse>" . $paRes . "</paymentResponse>\n    </ccAuthenticateRequestV1>";
    $url = "https://webservices.optimalpayments.com/creditcardWS/CreditCardServlet/v1";
    if ($params["testmode"]) {
        $url = "https://webservices.test.optimalpayments.com/creditcardWS/CreditCardServlet/v1";
    }
    $query_str = "txnMode=ccTDSAuthenticate&txnRequest=" . urlencode($xml);
    $data = curlCall($url, $query_str);
    $xmlData = XMLtoArray($data);
    $xmlData = $xmlData["CCTXNRESPONSEV1"];
    $indicator = $xmlData["TDSAUTHENTICATERESPONSE"]["STATUS"];
    $cAvv = $xmlData["TDSAUTHENTICATERESPONSE"]["CAVV"];
    $xid = $xmlData["TDSAUTHENTICATERESPONSE"]["XID"];
    $eci = $xmlData["TDSAUTHENTICATERESPONSE"]["ECI"];
    logTransaction($gatewayParams["paymentmethod"], $data, "Authenticate Response");
    $cardType = optimalpayments_cardtype($params["cardtype"]);
    $xml = "<ccAuthRequestV1 xmlns=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\"\n    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n    xsi:schemaLocation=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\">\n    <merchantAccount>\n    <accountNum>" . $gatewayParams["accountnumber"] . "</accountNum>\n    <storeID>" . $gatewayParams["merchantid"] . "</storeID>\n    <storePwd>" . $gatewayParams["merchantpw"] . "</storePwd>\n    </merchantAccount>\n    <merchantRefNum>" . $params["invoiceid"] . "</merchantRefNum>\n    <amount>" . $params["amount"] . "</amount>\n    <card>\n    <cardNum>" . $params["cardnum"] . "</cardNum>\n    <cardExpiry>\n    <month>" . substr($params["cardexp"], 0, 2) . "</month>\n    <year>20" . substr($params["cardexp"], 2, 2) . "</year>\n    </cardExpiry>\n    <cardType>" . $cardType . "</cardType>\n    </card>\n    <authentication>\n    <indicator>" . $eci . "</indicator>\n    <cavv>" . $cAvv . "</cavv>\n    <xid>" . $xid . "</xid>\n    </authentication>\n    <billingDetails>\n    <cardPayMethod>WEB</cardPayMethod>\n    <firstName>" . $params["clientdetails"]["firstname"] . "</firstName>\n    <lastName>" . $params["clientdetails"]["lastname"] . "</lastName>\n    <street>" . $params["clientdetails"]["address1"] . "</street>\n    <city>" . $params["clientdetails"]["city"] . "</city>\n    <region>" . $params["clientdetails"]["state"] . "</region>\n    <country>" . $params["clientdetails"]["country"] . "</country>\n    <zip>" . $params["clientdetails"]["postcode"] . "</zip>\n    <phone>" . $params["clientdetails"]["phonenumber"] . "</phone>\n    <email>" . $params["clientdetails"]["email"] . "</email>\n    </billingDetails>\n    <recurringIndicator>R</recurringIndicator>\n    <customerIP>" . $remote_ip . "</customerIP>\n    <productType>M</productType>\n    </ccAuthRequestV1>";
    $query_str = "txnMode=ccPurchase&txnRequest=" . urlencode($xml);
    logTransaction($gatewayParams["paymentmethod"], $query_str, "Payment Request");
    $data = curlCall($url, $query_str);
    $xmlData = XMLtoArray($data);
    $xmlData = $xmlData["CCTXNRESPONSEV1"];
    if ($xmlData["CODE"] == "0") {
        addInvoicePayment($invoiceID, $transid, "", "", "optimalpayments", "on");
        logTransaction($gatewayParams["paymentmethod"], $data, "Approved");
        sendMessage("Credit Card Payment Confirmation", $invoiceID);
        $callbackSuccess = true;
    } else {
        logTransaction($gatewayParams["paymentmethod"], $data, "Declined");
        sendMessage("Credit Card Payment Failed", $invoiceID);
    }
}
callback3DSecureRedirect($invoiceID, $callbackSuccess);

?>