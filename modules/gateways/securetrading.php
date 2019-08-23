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
function securetrading_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "SecureTrading"), "username" => array("FriendlyName" => "Username", "Type" => "text", "Size" => "20"), "password" => array("FriendlyName" => "Password", "Type" => "text", "Size" => "20"), "siteref" => array("FriendlyName" => "Site Reference", "Type" => "text", "Size" => "20"));
    return $configarray;
}
function securetrading_capture($params)
{
    $gatewayusername = $params["username"];
    $gatewaypassword = $params["password"];
    $gatewaysiteref = $params["siteref"];
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<requestblock version=\"3.67\">\n<alias>" . $gatewayusername . "</alias>\n<request type=\"AUTH\">\n<operation>\n<sitereference>" . $gatewaysiteref . "</sitereference>\n<accounttypedescription>ECOM</accounttypedescription>\n</operation>\n<merchant>\n<orderreference>" . $params["invoiceid"] . "</orderreference>\n</merchant>\n<customer>\n<delivery/>\n<name>" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "</name>\n<email>" . $params["clientdetails"]["email"] . "</email>\n<ip>" . $_SERVER["REMOTE_ADDR"] . "</ip>\n</customer>\n<billing>\n<amount currencycode=\"" . $params["currency"] . "\">" . $params["amount"] * 100 . "</amount>\n<premise>" . $params["clientdetails"]["address1"] . "</premise>\n<street>" . $params["clientdetails"]["address2"] . "</street>\n<town>" . $params["clientdetails"]["city"] . "</town>\n<county>" . $params["clientdetails"]["state"] . "</county>\n<country>" . $params["clientdetails"]["country"] . "</country>\n<postcode>" . $params["clientdetails"]["postcode"] . "</postcode>\n<email>" . $params["clientdetails"]["email"] . "</email>\n<payment type=\"" . strtoupper($params["cardtype"]) . "\">\n<expirydate>" . substr($params["cardexp"], 0, 2) . "/20" . substr($params["cardexp"], 2, 2) . "</expirydate>\n<pan>" . $params["cardnum"] . "</pan>\n<securitycode>" . $params["cccvv"] . "</securitycode>\n</payment>\n<name>\n<middle> </middle>\n<last>" . $params["clientdetails"]["lastname"] . "</last>\n<first>" . $params["clientdetails"]["firstname"] . "</first>\n</name>\n</billing>\n<settlement/>\n</request>\n</requestblock>";
    $authstr = "Basic " . base64_encode($gatewayusername . ":" . $gatewaypassword);
    $headers = array("HTTP/1.1", "Host: webservices.securetrading.net", "Accept: text/xml", "Authorization: " . $authstr, "User-Agent: WHMCS Gateway Module", "Content-type: text/xml;charset=\"utf-8\"", "Content-length: " . strlen($xml), "Connection: close");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://webservices.securetrading.net:443/xml/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, (string) $gatewayusername . ":" . $gatewaypassword);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    $xmldata = XMLtoArray($data);
    if ($xmldata["RESPONSEBLOCK"]["RESPONSE"]["ERROR"]["CODE"] == "0") {
        $results["transid"] = $xmldata["RESPONSEBLOCK"]["RESPONSE"]["TRANSACTIONREFERENCE"];
        return array("status" => "success", "transid" => $results["transid"], "rawdata" => $data);
    }
    if ($xmldata["RESPONSEBLOCK"]["RESPONSE"]["ERROR"]["CODE"] == "99999") {
        $results["status"] = "error";
        return array("status" => "error", "rawdata" => $data);
    }
    return array("status" => "declined", "rawdata" => $data);
}
function securetrading_refund($params)
{
    $gatewayusername = $params["username"];
    $gatewaypassword = $params["password"];
    $gatewaysiteref = $params["siteref"];
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><requestblock version=\"3.67\"><alias>" . $gatewayusername . "</alias><request type=\"REFUND\"> <merchant> <orderreference>" . $params["invoiceid"] . "</orderreference> </merchant> <operation> <sitereference>" . $gatewaysiteref . "</sitereference> <parenttransactionreference>" . $params["transid"] . "</parenttransactionreference> </operation> <billing> <amount currencycode=\"" . $params["currency"] . "\">" . $params["amount"] * 100 . "</amount> </billing> </request> </requestblock>";
    $authstr = "Basic " . base64_encode($gatewayusername . ":" . $gatewaypassword);
    $headers = array("HTTP/1.1", "Host: webservices.securetrading.net", "Accept: text/xml", "Authorization: " . $authstr, "User-Agent: WHMCS Gateway Module", "Content-type: text/xml;charset=\"utf-8\"", "Content-length: " . strlen($xml), "Connection: close");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://webservices.securetrading.net:443/xml/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, (string) $gatewayusername . ":" . $gatewaypassword);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    $xmldata = XMLtoArray($data);
    if ($xmldata["RESPONSEBLOCK"]["RESPONSE"]["ERROR"]["CODE"] == "0") {
        $results["transid"] = $xmldata["RESPONSEBLOCK"]["RESPONSE"]["TRANSACTIONREFERENCE"];
        return array("status" => "success", "transid" => $results["transid"], "rawdata" => $data);
    }
    if ($xmldata["RESPONSEBLOCK"]["RESPONSE"]["ERROR"]["CODE"] == "99999") {
        $results["status"] = "error";
        return array("status" => "error", "rawdata" => $data);
    }
    return array("status" => "declined", "rawdata" => $data);
}

?>