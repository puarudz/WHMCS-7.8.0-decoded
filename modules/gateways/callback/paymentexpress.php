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
$gateway = WHMCS\Module\Gateway::factory("paymentexpress");
$gatewayParams = $gateway->getParams();
logTransaction($gatewayParams["paymentmethod"], $_REQUEST, "Received");
$url = "https://sec.paymentexpress.com/pxpay/pxaccess.aspx";
$xml = "<ProcessResponse>\n<PxPayUserId>" . $gatewayParams["pxpayuserid"] . "</PxPayUserId>\n<PxPayKey>" . $gatewayParams["pxpaykey"] . "</PxPayKey>\n<Response>" . WHMCS\Input\Sanitize::decode($_REQUEST["result"]) . "</Response>\n</ProcessResponse>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$outputXml = curl_exec($ch);
curl_close($ch);
$xmlresponse = XMLtoArray($outputXml);
$xmlresponse = $xmlresponse["RESPONSE"];
$success = $xmlresponse["SUCCESS"];
$invoiceid = (int) $xmlresponse["TXNDATA1"];
$transid = $xmlresponse["TXNID"];
if ($xmlresponse["SUCCESS"] == "1") {
    $invoiceid = checkCbInvoiceID($invoiceid, $gatewayParams["paymentmethod"]);
    $result = select_query("tblaccounts", "invoiceid", array("transid" => $transid));
    $data = mysql_fetch_array($result);
    $transinvoiceid = $data["invoiceid"];
    if ($transinvoiceid) {
        redirSystemURL("id=" . $invoiceid . "&paymentsuccess=true", "viewinvoice.php");
    }
    addInvoicePayment($invoiceid, $transid, "", "", "paymentexpress");
    logTransaction($gatewayParams["paymentmethod"], array_merge($_REQUEST, $xmlresponse), "Successful");
    redirSystemURL("id=" . $invoiceid . "&paymentsuccess=true", "viewinvoice.php");
} else {
    logTransaction($gatewayParams["paymentmethod"], array_merge($_REQUEST, $xmlresponse), "Unsuccessful");
    if ($invoiceid) {
        redirSystemURL("id=" . $invoiceid . "&paymentfailed=true", "viewinvoice.php");
    } else {
        redirSystemURL("action=invoices", "clientarea.php");
    }
}

?>