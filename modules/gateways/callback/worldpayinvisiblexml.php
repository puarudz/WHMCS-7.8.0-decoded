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
$GATEWAY = getGatewayVariables("worldpayinvisiblexml");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$invoiceid = (int) $_REQUEST["MD"];
$result = select_query("tblgatewaylog", "data", array("gateway" => "WPIORDERCODE" . $invoiceid));
$data = mysql_fetch_array($result);
$orderCode = $data["data"];
$result = select_query("tblgatewaylog", "data", array("gateway" => "WPIECHODATA" . $invoiceid));
$data = mysql_fetch_array($result);
$echoData = $data["data"];
$result = select_query("tblgatewaylog", "data", array("gateway" => "WPICPDATA" . $invoiceid));
$data = mysql_fetch_array($result);
$cvv = $data["data"];
if (!$echoData) {
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "echoData Not Found");
    echo "An Error Occurred. Please Contact Support.";
    exit;
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Received");
delete_query("tblgatewaylog", array("gateway" => "WPIECHODATA" . $invoiceid));
delete_query("tblgatewaylog", array("gateway" => "WPIORDERCODE" . $invoiceid));
delete_query("tblgatewaylog", array("gateway" => "WPICPDATA" . $invoiceid));
$params = getCCVariables($invoiceid);
if ($params["cardtype"] == "American Express") {
    $merchantCode = $params["merchantcodeamex"];
} else {
    $merchantCode = $params["merchantcode1"];
}
$password = $params["merchantpw"];
$instId = $params["instid"];
$cookiestore = $params["cookiestore"];
$orderDescription = "Invoice #" . $params["invoiceid"];
$orderAmount = $params["amount"] * 100;
$raworderAmount = $params["amount"];
$invoiceID = $params["invoiceid"];
$orderShopperEmail = $params["clientdetails"]["email"];
$orderShopperID = $params["clientdetails"]["userid"];
$orderShopperFirstName = $params["clientdetails"]["firstname"];
$orderShopperSurname = $params["clientdetails"]["lastname"];
$orderShopperStreet = $params["clientdetails"]["address1"];
$orderShopperPostcode = $params["clientdetails"]["postcode"];
$orderShopperCity = $params["clientdetails"]["city"];
$orderShopperCountryCode = $params["clientdetails"]["country"];
$orderShopperTel = $params["clientdetails"]["phonenumber"];
$acceptHeader = $_SERVER["HTTP_ACCEPT"];
$userAgentHeader = $_SERVER["HTTP_USER_AGENT"];
$shopperIPAddress = is_null($_SERVER["REMOTE_ADDR"]) ? "127.0.0.1" : $_SERVER["REMOTE_ADDR"];
if ($params["cardtype"] == "American Express") {
    $cardType = "AMEX-SSL";
} else {
    if ($params["cardtype"] == "Diners Club") {
        $cardType = "DINERS-SSL";
    } else {
        if ($params["cardtype"] == "JCB") {
            $cardType = "JCB-SSL";
        } else {
            if ($params["cardtype"] == "MasterCard") {
                $cardType = "ECMC-SSL";
            } else {
                if ($params["cardtype"] == "Solo") {
                    $cardType = "SOLO_GB-SSL";
                } else {
                    if ($params["cardtype"] == "Maestro") {
                        $cardType = "MAESTRO-SSL";
                    } else {
                        $cardType = "VISA-SSL";
                    }
                }
            }
        }
    }
}
$id = time();
$xml = "<?xml version='1.0' encoding='UTF-8'?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN' 'http://dtd.worldpay.com/paymentService_v1.dtd'>";
$xml .= "<paymentService version='1.4' merchantCode='" . $merchantCode . "'>";
$xml .= "<submit>";
$xml .= "<order orderCode='" . $orderCode . "' installationId='" . $instId . "'>";
$xml .= "<description>" . $orderDescription . "</description>";
$xml .= "<amount value='" . $orderAmount . "' currencyCode='" . $params["currency"] . "' exponent='2'/>";
$xml .= "<orderContent><![CDATA[]]></orderContent>";
$xml .= "<paymentDetails>";
$xml .= "<" . $cardType . ">";
$xml .= "<cardNumber>" . $params["cardnum"] . "</cardNumber>";
$xml .= "<expiryDate><date month='" . substr($params["cardexp"], 0, 2) . "' year='20" . substr($params["cardexp"], 2, 2) . "'/></expiryDate>";
$xml .= "<cardHolderName>" . $orderShopperFirstName . " " . $orderShopperSurname . "</cardHolderName>";
if ($params["cardtype"] == "Maestro" || $params["cardtype"] == "Solo") {
    if ($params["cardstart"]) {
        $xml .= "<startDate><date month='" . substr($params["cardstart"], 0, 2) . "' year='20" . substr($params["cardstart"], 2, 2) . "'/></startDate>";
    }
    if ($params["cardissuenum"]) {
        $xml .= "<issueNumber>" . $params["cardissuenum"] . "</issueNumber>";
    }
}
$xml .= "<cvc>" . $cvv . "</cvc>";
$xml .= "<cardAddress>";
$xml .= "<address>";
$xml .= "<firstName>" . $orderShopperFirstName . "</firstName>";
$xml .= "<lastName>" . $orderShopperSurname . "</lastName>";
$xml .= "<street>" . $orderShopperStreet . "</street>";
$xml .= "<postalCode>" . $orderShopperPostcode . "</postalCode>";
$xml .= "<city>" . $orderShopperCity . "</city>";
$xml .= "<countryCode>" . $orderShopperCountryCode . "</countryCode>";
$xml .= "<telephoneNumber>" . $orderShopperTel . "</telephoneNumber>";
$xml .= "</address>";
$xml .= "</cardAddress>";
$xml .= "</" . $cardType . ">";
$xml .= "<session shopperIPAddress='" . $shopperIPAddress . "' id='" . $invoiceID . "'/>";
$xml .= "<info3DSecure>";
$xml .= "<paResponse>" . $_REQUEST["PaRes"] . "</paResponse>";
$xml .= "</info3DSecure>";
$xml .= "</paymentDetails>";
$xml .= "<shopper>";
$xml .= "<shopperEmailAddress>" . $orderShopperEmail . "</shopperEmailAddress>";
$xml .= "<browser>";
$xml .= "<acceptHeader>" . $acceptHeader . "</acceptHeader>";
$xml .= "<userAgentHeader>" . $userAgentHeader . "</userAgentHeader>";
$xml .= "</browser>";
$xml .= "</shopper>";
$xml .= "<echoData>" . $echoData . "</echoData>";
$xml .= "</order></submit></paymentService>";
if ($params["testmode"]) {
    $url = "https://secure-test.ims.worldpay.com/jsp/merchant/xml/paymentService.jsp";
} else {
    $url = "https://secure.worldpay.com/jsp/merchant/xml/paymentService.jsp";
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, $merchantCode . ":" . $password);
curl_setopt($ch, CURLOPT_COOKIEFILE, (string) $cookiestore . $invoiceID . ".cookie");
curl_setopt($ch, CURLOPT_TIMEOUT, 240);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$result_tmp = curl_exec($ch);
curl_close($ch);
$result_arr = XMLtoArray($result_tmp);
$lastevent = $result_arr["PAYMENTSERVICE"]["REPLY"]["ORDERSTATUS"]["PAYMENT"]["LASTEVENT"];
$callbacksuccess = false;
if ($lastevent == "AUTHORISED") {
    addInvoicePayment($invoiceID, $orderCode, $raworderAmount, "", "worldpayinvisiblexml", "on");
    logTransaction($GATEWAY["paymentmethod"], $result_tmp, "Successful");
    sendMessage("Credit Card Payment Confirmation", $invoiceid);
    $callbacksuccess = true;
} else {
    logTransaction($GATEWAY["paymentmethod"], $result_tmp, "Declined");
    sendMessage("Credit Card Payment Failed", $invoiceid);
}
unlink((string) $cookiestore . $invoiceID . ".cookie");
callback3DSecureRedirect($invoiceid, $callbacksuccess);

?>