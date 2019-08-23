<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$GATEWAYMODULE["finansbankname"] = "finansbank";
$GATEWAYMODULE["finansbankvisiblename"] = "Turkish Finansbank";
$GATEWAYMODULE["finansbanktype"] = "CC";
function finansbank_activate()
{
    defineGatewayField("finansbank", "text", "merchantid", "", "Merchant ID", "20", "");
    defineGatewayField("finansbank", "text", "merchantpw", "", "Merchant Password", "20", "");
    defineGatewayField("finansbank", "text", "merchantnumber", "", "Merchant Number", "20", "");
    defineGatewayField("finansbank", "text", "currency", "", "Currency", "10", "");
    defineGatewayField("finansbank", "yesno", "testmode", "", "Test Mode", "", "");
}
function finansbank_capture($params)
{
    if ($params["testmode"] == "on") {
        $gateway_url = "https://testserver.fbwebpos.com/servlet/cc5ApiServer";
    } else {
        $gateway_url = "https://www.fbwebpos.com/servlet/cc5ApiServer";
    }
    $name = $params["merchantid"];
    $password = $params["merchantpw"];
    $clientid = $params["merchantnumber"];
    $lip = GetHostByName($REMOTE_ADDR);
    $email = $params["clientdetails"]["email"];
    $oid = $params["invoiceid"];
    $type = "Auth";
    $ccno = $params["cardnum"];
    $ccay = substr($params["cardexp"], 0, 2);
    $ccyil = substr($params["cardexp"], 2, 2);
    $tutar = $params["amount"];
    $cv2 = $params["cccvv"];
    $taksit = "";
    $request = "DATA=<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<CC5Request>\n<Name>{NAME}</Name>\n<Password>{PASSWORD}</Password>\n<ClientId>{CLIENTID}</ClientId>\n<IPAddress>{IP}</IPAddress>\n<Email>{EMAIL}</Email>\n<Mode>P</Mode>\n<OrderId>{OID}</OrderId>\n<GroupId></GroupId>\n<TransId></TransId>\n<UserId></UserId>\n<Type>{TYPE}</Type>\n<Number>{CCNO}</Number>\n<Expires>{CCTAR}</Expires>\n<Cvv2Val>{CV2}</Cvv2Val>\n<Total>{TUTAR}</Total>\n<Currency>840</Currency>\n<Taksit>{TAKSIT}</Taksit>\n<BillTo>\n<Name></Name>\n<Street1></Street1>\n<Street2></Street2>\n<Street3></Street3>\n<City></City>\n<StateProv></StateProv>\n<PostalCode></PostalCode>\n<Country></Country>\n<Company></Company>\n<TelVoice></TelVoice>\n</BillTo>\n<ShipTo>\n<Name></Name>\n<Street1></Street1>\n<Street2></Street2>\n<Street3></Street3>\n<City></City>\n<StateProv></StateProv>\n<PostalCode></PostalCode>\n<Country></Country>\n</ShipTo>\n<Extra></Extra>\n</CC5Request>\n";
    $request = str_replace("{NAME}", $name, $request);
    $request = str_replace("{PASSWORD}", $password, $request);
    $request = str_replace("{CLIENTID}", $clientid, $request);
    $request = str_replace("{IP}", $lip, $request);
    $request = str_replace("{OID}", $oid, $request);
    $request = str_replace("{TYPE}", $type, $request);
    $request = str_replace("{CCNO}", $ccno, $request);
    $request = str_replace("{CCTAR}", $ccexpirymonth . "/" . $ccexpiryyear, $request);
    $request = str_replace("{CV2}", $cccvv, $request);
    $request = str_replace((string) $total, $tutar, $request);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gateway_url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        logTransaction($params["paymentmethod"], "Error => " . $error, "Error");
        sendMessage("Credit Card Payment Failed", $params["invoiceid"]);
        $result = "error";
        return $result;
    }
    curl_close($ch);
    $Response = "";
    $OrderId = "";
    $AuthCode = "";
    $ProcReturnCode = "";
    $ErrMsg = "";
    $HOSTMSG = "";
    $response_tag = "Response";
    $posf = strpos($result, "<" . $response_tag . ">");
    $posl = strpos($result, "</" . $response_tag . ">");
    $posf = $posf + strlen($response_tag) + 2;
    $Response = substr($result, $posf, $posl - $posf);
    $response_tag = "OrderId";
    $posf = strpos($result, "<" . $response_tag . ">");
    $posl = strpos($result, "</" . $response_tag . ">");
    $posf = $posf + strlen($response_tag) + 2;
    $OrderId = substr($result, $posf, $posl - $posf);
    $response_tag = "AuthCode";
    $posf = strpos($result, "<" . $response_tag . ">");
    $posl = strpos($result, "</" . $response_tag . ">");
    $posf = $posf + strlen($response_tag) + 2;
    $AuthCode = substr($result, $posf, $posl - $posf);
    $response_tag = "ProcReturnCode";
    $posf = strpos($result, "<" . $response_tag . ">");
    $posl = strpos($result, "</" . $response_tag . ">");
    $posf = $posf + strlen($response_tag) + 2;
    $ProcReturnCode = substr($result, $posf, $posl - $posf);
    $response_tag = "ErrMsg";
    $posf = strpos($result, "<" . $response_tag . ">");
    $posl = strpos($result, "</" . $response_tag . ">");
    $posf = $posf + strlen($response_tag) + 2;
    $ErrMsg = substr($result, $posf, $posl - $posf);
    $debugdata = "Action => Auth\nClient => " . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\nResponse => " . $Response . "\nOrderId => " . $OrderId . "\nAuthCode => " . $AuthCode . "\nProcReturnCode => " . $ProcReturnCode . "\nErrMsg => " . $ErrMsg;
    if ($Response === "Approved") {
        return array("status" => "success", "transid" => $transid, "rawdata" => $debugdata);
    }
    return array("status" => "declined", "rawdata" => $debugdata);
}

?>