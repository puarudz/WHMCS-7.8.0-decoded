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
$gateway = WHMCS\Module\Gateway::factory("paypalpaymentsproref");
$gatewayParams = $gateway->getParams();
$callbacksuccess = false;
$pares = $_REQUEST["PaRes"];
$invoiceid = $_REQUEST["MD"];
$storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("paypalpaymentsproref");
$orderData = $storage->getValue("order_data", array());
if (strcasecmp("", $pares) != 0 && $pares != NULL && isset($orderData["Centinel_TransactionId"])) {
    if ($gatewayParams["sandbox"]) {
        $mapurl = "https://centineltest.cardinalcommerce.com/maps/txns.asp";
    } else {
        $mapurl = "https://paypal.cardinalcommerce.com/maps/txns.asp";
    }
    $currency = "";
    if ($gatewayParams["currency"] == "USD") {
        $currency = "840";
    }
    if ($gatewayParams["currency"] == "GBP") {
        $currency = "826";
    }
    if ($gatewayParams["currency"] == "EUR") {
        $currency = "978";
    }
    if ($gatewayParams["currency"] == "CAD") {
        $currency = "124";
    }
    $postfields = array();
    $postfields["MsgType"] = "cmpi_authenticate";
    $postfields["Version"] = "1.7";
    $postfields["ProcessorId"] = $gatewayParams["processorid"];
    $postfields["MerchantId"] = $gatewayParams["merchantid"];
    $postfields["TransactionPwd"] = $gatewayParams["transpw"];
    $postfields["TransactionType"] = "C";
    $postfields["PAResPayload"] = $pares;
    $postfields["OrderId"] = $orderData["Centinel_OrderId"];
    $postfields["TransactionId"] = $orderData["Centinel_TransactionId"];
    $queryString = "<CardinalMPI>\n";
    foreach ($postfields as $name => $value) {
        $queryString .= "<" . $name . ">" . $value . "</" . $name . ">\n";
    }
    $queryString .= "</CardinalMPI>";
    $data = "cmpi_msg=" . urlencode($queryString);
    $response = curlCall($mapurl, $data);
    $xmlarray = XMLtoArray($response);
    $xmlarray = $xmlarray["CARDINALMPI"];
    $errorno = $xmlarray["ERRORNO"];
    $paresstatus = $xmlarray["PARESSTATUS"];
    $sigverification = $xmlarray["SIGNATUREVERIFICATION"];
    $cavv = $xmlarray["CAVV"];
    $eciflag = $xmlarray["ECIFLAG"];
    $xid = $xmlarray["XID"];
    if ((strcasecmp("0", $errorno) == 0 || strcasecmp("1140", $errorno) == 0) && strcasecmp("Y", $sigverification) == 0 && (strcasecmp("Y", $paresstatus) == 0 || strcasecmp("A", $paresstatus) == 0)) {
        logTransaction($gatewayParams["paymentmethod"], $_REQUEST, "Auth Passed");
        $auth = array("paresstatus" => $paresstatus, "cavv" => $cavv, "eciflag" => $eciflag, "xid" => $xid);
        $params = getCCVariables($invoiceid);
        $cDetails = $storage->getAndDeleteValue("Centinel_Details", array());
        if (!empty($cDetails)) {
            $params["cardtype"] = $cDetails["cardtype"];
            $params["cardnum"] = $cDetails["cardnum"];
            $params["cardexp"] = $cDetails["cardexp"];
            $params["cccvv"] = $cDetails["cccvv"];
            $params["cardstart"] = $cDetails["cardstart"];
            $params["cardissuenum"] = $cDetails["cardissuenum"];
        }
        $result = paypalpaymentsproref_capture($params, $auth);
        if ($result["status"] == "success") {
            logTransaction($gatewayParams["paymentmethod"], $result["rawdata"], "Successful");
            addInvoicePayment($invoiceid, $result["transid"], "", "", "paypalpaymentsproref", "on");
            sendMessage("Credit Card Payment Confirmation", $invoiceid);
            $callbacksuccess = true;
        } else {
            logTransaction($gatewayParams["paymentmethod"], $result["rawdata"], "Failed");
        }
    } else {
        if (strcasecmp("N", $paresstatus) == 0) {
            logTransaction($gatewayParams["paymentmethod"], $_REQUEST, "Auth Failed");
        } else {
            logTransaction($gatewayParams["paymentmethod"], $_REQUEST, "Unexpected Status, Capture Anyway");
            $auth = array("paresstatus" => $paresstatus, "cavv" => $cavv, "eciflag" => $eciflag, "xid" => $xid);
            $params = getCCVariables($invoiceid);
            $cDetails = $storage->getAndDeleteValue("Centinel_Details", array());
            if (!empty($cDetails)) {
                $params["cardtype"] = $cDetails["cardtype"];
                $params["cardnum"] = $cDetails["cardnum"];
                $params["cardexp"] = $cDetails["cardexp"];
                $params["cccvv"] = $cDetails["cccvv"];
                $params["cardstart"] = $cDetails["cardstart"];
                $params["cardissuenum"] = $cDetails["cardissuenum"];
            }
            $result = paypalpaymentsproref_capture($params, $auth);
            if ($result["status"] == "success") {
                logTransaction($gatewayParams["paymentmethod"], $result["rawdata"], "Successful");
                addInvoicePayment($invoiceid, $result["transid"], "", "", "paypalpaymentsproref", "on");
                sendMessage("Credit Card Payment Confirmation", $invoiceid);
                $callbacksuccess = true;
            } else {
                logTransaction($gatewayParams["paymentmethod"], $result["rawdata"], "Failed");
            }
        }
    }
} else {
    logTransaction($gatewayParams["paymentmethod"], $_REQUEST, "Error");
}
if (!$callbacksuccess) {
    sendMessage("Credit Card Payment Failed", $invoiceid);
}
callback3DSecureRedirect($invoiceid, $callbacksuccess);

?>