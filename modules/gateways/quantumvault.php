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
if (isset($_POST["ajax"]) && $_POST["ajax"] == "true") {
    $response = _quantumvault_http_post("secure.quantumgateway.com", "/cgi/ilf_refresh.php", array("ip" => $_POST["ip"], "k" => $_POST["k"]), 443);
}
function quantumvault_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "Quantum Vault"), "loginid" => array("FriendlyName" => "Login ID", "Type" => "text", "Size" => "20"), "transkey" => array("FriendlyName" => "Restrict Key", "Type" => "text", "Size" => "20", "Description" => "In the Processing Settings area of your QG Account"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "20", "Description" => "Go to Processing Settings > Inline Frame API, set API Enabled = Y and generate Username & API Key"), "apikey" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "20"), "vaultkey" => array("FriendlyName" => "Vault Key", "Type" => "text", "Size" => "20", "Description" => "Set in Processing Tools > Secure Vault > Vault Config"), "md5hash" => array("FriendlyName" => "MD5 Hash", "Type" => "text", "Size" => "20", "Description" => "Also in the Processing Settings area of your Quantum Account"), "testmode" => array("FriendlyName" => "Test Module", "Type" => "yesno"));
}
function quantumvault_nolocalcc()
{
}
function quantumvault_remoteinput($params)
{
    if (!array_key_exists("amount", $params) || !$params["amount"]) {
        return "<div class=\"alert alert-info\">You must be paying an invoice to add a new stored card</p>";
    }
    $code = "<form method=\"post\" action=\"https://secure.quantumgateway.com/cgi/qgwdbe.php\">\n<input type=\"hidden\" name=\"gwlogin\" value=\"" . $params["loginid"] . "\" />\n<input type=\"hidden\" name=\"RestrictKey\" value=\"" . $params["transkey"] . "\" />\n<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\" />\n<input type=\"hidden\" name=\"ID\" value=\"" . $params["invoiceid"] . "\" />\n<input type=\"hidden\" name=\"FNAME\" value=\"" . $params["clientdetails"]["firstname"] . "\" />\n<input type=\"hidden\" name=\"LNAME\" value=\"" . $params["clientdetails"]["lastname"] . "\" />\n<input type=\"hidden\" name=\"BADDR1\" value=\"" . $params["clientdetails"]["address1"] . "\" />\n<input type=\"hidden\" name=\"BCITY\" value=\"" . $params["clientdetails"]["city"] . "\" />\n<input type=\"hidden\" name=\"BSTATE\" value=\"" . $params["clientdetails"]["state"] . "\" />\n<input type=\"hidden\" name=\"BZIP1\" value=\"" . $params["clientdetails"]["postcode"] . "\" />\n<input type=\"hidden\" name=\"BCOUNTRY\" value=\"" . $params["clientdetails"]["country"] . "\" />\n<input type=\"hidden\" name=\"PHONE\" value=\"" . $params["clientdetails"]["phonenumber"] . "\" />\n<input type=\"hidden\" name=\"BCUST_EMAIL\" value=\"" . $params["clientdetails"]["email"] . "\" />\n<input type=\"hidden\" name=\"AddToVault\" value=\"Y\" />\n<input type=\"hidden\" name=\"cust_id\" value=\"" . $params["clientdetails"]["id"] . "\" />\n<input type=\"hidden\" name=\"trans_method\" value=\"CC\" />\n<input type=\"hidden\" name=\"ResponseMethod\" value=\"GET\" />\n<input type=\"hidden\" name=\"post_return_url_approved\" value=\"" . $params["systemurl"] . "modules/gateways/callback/quantumvault.php\" />\n<input type=\"hidden\" name=\"post_return_url_declined\" value=\"" . $params["systemurl"] . "modules/gateways/callback/quantumvault.php\" />\n<noscript>\n<input type=\"submit\" value=\"Click here to continue &raquo;\" />\n</noscript>\n</form>";
    return $code;
}
function quantumvault_remoteupdate($params)
{
    if (!$params["gatewayid"]) {
        return "<p align=\"center\">You must pay your first invoice via credit card before you can update your stored card details here...</p>";
    }
    $quantum = quantumvault_getCode($params["apiusername"], $params["apikey"], "650", "450", "0", "0", $params["gatewayid"], "CustomerEdit");
    return $quantum["script"] . $quantum["iframe"];
}
function quantumvault_capture($params)
{
    if (!$params["gatewayid"]) {
        return array("status" => "failed", "rawdata" => "No Card Stored for this Client in Vault");
    }
    $url = "https://secure.quantumgateway.com/cgi/xml_requester.php";
    $xml = "<QGWRequest>\n<Authentication>\n<GatewayLogin>" . $params["loginid"] . "</GatewayLogin>\n<GatewayKey>" . $params["vaultkey"] . "</GatewayKey>\n</Authentication>\n<Request>\n<RequestType>CreateTransaction</RequestType>\n<TransactionType>CREDIT</TransactionType>\n<ProcessType>SALES</ProcessType>\n<CustomerID>" . $params["gatewayid"] . "</CustomerID>\n<Memo>Invoice Number " . $params["invoiceid"] . "</Memo>\n<Amount>" . $params["amount"] . "</Amount>\n</Request>\n</QGWRequest>";
    $data = curlCall($url, "xml=" . $xml);
    $results = XMLtoArray($data);
    if ($results["QGWREQUEST"]["RESULT"]["STATUS"] == "APPROVED") {
        return array("status" => "success", "transid" => $results["QGWREQUEST"]["RESULT"]["TRANSACTIONID"], "rawdata" => $results["QGWREQUEST"]["RESULT"]);
    }
    return array("status" => "error", "rawdata" => $data);
}
function quantumvault_refund($params)
{
    if (!$params["gatewayid"]) {
        return array("status" => "failed", "rawdata" => "No Card Stored for this Client in Vault");
    }
    $url = "https://secure.quantumgateway.com/cgi/xml_requester.php";
    $xml = "<QGWRequest>\n<Authentication>\n<GatewayLogin>" . $params["loginid"] . "</GatewayLogin>\n<GatewayKey>" . $params["transkey"] . "</GatewayKey>\n</Authentication>\n<Request>\n<RequestType>ShowTransactionDetails</RequestType>\n<TransactionID>" . $params["transid"] . "</TransactionID>\n</Request>\n</QGWRequest>";
    $data = curlCall($url, "xml=" . $xml);
    $results = XMLtoArray($data);
    $cclastfour = $results["QGWREQUEST"]["RESULT"]["CREDITCARDNUMBER"];
    $xml = "<QGWRequest>\n<Authentication>\n<GatewayLogin>" . $params["loginid"] . "</GatewayLogin>\n<GatewayKey>" . $params["transkey"] . "</GatewayKey>\n</Authentication>\n<Request>\n<RequestType>ProcessSingleTransaction</RequestType>\n<ProcessType>RETURN</ProcessType>\n<TransactionType>CREDIT</TransactionType>\n<PaymentType>CC</PaymentType>\n<CustomerID>" . $params["gatewayid"] . "</CustomerID>\n<TransactionID>" . $params["transid"] . "</TransactionID>\n<CreditCardNumber>" . $cclastfour . "</CreditCardNumber>\n<Amount>" . $params["amount"] . "</Amount>\n</Request>\n</QGWRequest>";
    $data = curlCall($url, "xml=" . $xml);
    $results = XMLtoArray($data);
    if ($results["QGWREQUEST"]["RESULT"]["STATUS"] == "APPROVED") {
        return array("status" => "success", "transid" => $results["QGWREQUEST"]["RESULT"]["TRANSACTIONID"], "rawdata" => $results["QGWREQUEST"]["RESULT"]);
    }
    return array("status" => "error", "rawdata" => $data);
}
function _quantumvault_http_post($host, $path, $data, $port = 80)
{
    $url = "https://secure.quantumgateway.com" . $path;
    $result = curlCall($url, $data);
    $response = explode("\r\n\r\n", $result, 2);
    $response[1] = $response[0];
    return $response;
}
function quantumvault_getCode($API_Username, $API_Key, $width, $height, $amount = "0", $id = "0", $custid = "0", $method = "0", $addtoVault = "N", $skipshipping = "N")
{
    $thereturn = array();
    $random = rand(1111111111, 9999999999.0);
    $random = (int) $random;
    $response = _quantumvault_http_post("secure.quantumgateway.com", "/cgi/ilf_authenticate.php", array("API_Username" => $API_Username, "API_Key" => $API_Key, "randval" => $random, "lastip" => $_SERVER["REMOTE_ADDR"]), 443);
    if (is_array($response) && $response[1] != "error") {
        $extrapars = "";
        if ($method != "0") {
            $extrapars .= "&METHOD=" . $method;
        }
        if ($addtoVault != "N") {
            $extrapars .= "&AddToVault=" . $addtoVault;
        }
        if ($skipshipping != "N") {
            $extrapars .= "&skip_shipping_info=" . $skipshipping;
        }
        if ($custid != "0") {
            $extrapars .= "&CustomerID=" . urlencode($custid);
        }
        if ($amount != "0") {
            $extrapars .= "&Amount=" . $amount;
        }
        if ($id != "0") {
            $extrapars .= "&ID=" . urlencode($id);
        }
        $extrapars .= "&skip_shipping_info=Y&ilf_API_Style=2";
        $thereturn["iframe"] = "<iframe src=\"https://secure.quantumgateway.com/cgi/ilf.php?k=" . $response[1] . "&ip=" . $_SERVER["REMOTE_ADDR"] . $extrapars . "\" height=\"" . $height . "\" width=\"" . $width . "\" frameborder=\"0\"></iframe><br/>";
        $thereturn["script"] = "\n<script type=\"text/javascript\">\nfunction refreshSession(thek, theip) {\n    var randomnumber=Math.random();\n    WHMCS.http.jqClient.post(\"modules/gateways/quantumvault.php?cachebuster=\"+randomnumber, { ajax: \"1\", ip: theip, k: thek } );\n}\nsetInterval(\"refreshSession('" . $response[1] . "','" . $_SERVER["REMOTE_ADDR"] . "')\",20000);\n</script>\n";
    }
    return $thereturn;
}
function quantumvault_adminstatusmsg($vars)
{
    $gatewayid = $vars["gatewayid"];
    if ($gatewayid) {
        return array("type" => "info", "title" => "Quantum Vault Profile", "msg" => "This customer has a Quantum Vault Profile storing their card" . " details for automated recurring billing with ID " . $gatewayid);
    }
}

?>