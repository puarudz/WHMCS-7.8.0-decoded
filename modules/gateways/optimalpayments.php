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
function optimalpayments_MetaData()
{
    return array("DisplayName" => "Optimal Payments", "APIVersion" => "1.1");
}
function optimalpayments_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Optimal Payments"), "accountnumber" => array("FriendlyName" => "Account Number", "Type" => "text", "Size" => "20"), "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "merchantpw" => array("FriendlyName" => "Merchant Password", "Type" => "text", "Size" => "20"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function optimalpayments_3dsecure($params)
{
    $cardType = optimalpayments_cardtype($params["cardtype"]);
    if ($cardType == "JC" || $cardType == "VI" || $cardType == "MC") {
        $xml = "<ccEnrollmentLookupRequestV1\n    xmlns=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\"\n    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n    xsi:schemaLocation=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\">\n    <merchantAccount>\n    <accountNum>" . $params["accountnumber"] . "</accountNum>\n    <storeID>" . $params["merchantid"] . "</storeID>\n    <storePwd>" . $params["merchantpw"] . "</storePwd>\n    </merchantAccount>\n    <merchantRefNum>" . $params["invoiceid"] . "</merchantRefNum>\n    <amount>" . $params["amount"] . "</amount>\n    <card>\n    <cardNum>" . $params["cardnum"] . "</cardNum>\n    <cardExpiry>\n    <month>" . substr($params["cardexp"], 0, 2) . "</month>\n    <year>20" . substr($params["cardexp"], 2, 2) . "</year>\n    </cardExpiry>\n    <cardType>" . $cardType . "</cardType>\n    ";
        if ($params["cccvv"]) {
            $xml .= "<cvdIndicator>1</cvdIndicator>\n        <cvd>" . $params["cccvv"] . "</cvd>\n        ";
        } else {
            $xml .= "<cvdIndicator>0</cvdIndicator>\n        ";
        }
        $xml .= "</card>\n    </ccEnrollmentLookupRequestV1>";
        $url = "https://webservices.optimalpayments.com/creditcardWS/CreditCardServlet/v1";
        if ($params["testmode"]) {
            $url = "https://webservices.test.optimalpayments.com/creditcardWS/CreditCardServlet/v1";
        }
        $query_str = "txnMode=ccTDSLookup&txnRequest=" . urlencode($xml);
        $data = curlCall($url, $query_str);
        $xmlData = XMLtoArray($data);
        $xmlData = $xmlData["CCTXNRESPONSEV1"];
        if ($xmlData["CODE"] == "0") {
            logTransaction($params["paymentmethod"], $data, "Lookup Successful");
            $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("optimalpayments");
            $storage->setValue("optimalpaymentsconfirmationnumber", $xmlData["CONFIRMATIONNUMBER"]);
            if ($xmlData["TDSRESPONSE"]["ENROLLMENTSTATUS"] == "Y") {
                $storage->setValue("invoicedata." . (int) $params["invoiceid"], $params);
                $code = "<form method=\"post\" action=\"" . $xmlData["TDSRESPONSE"]["ACSURL"] . "\">\n    <input type=hidden name=\"PaReq\" value=\"" . $xmlData["TDSRESPONSE"]["PAYMENTREQUEST"] . "\">\n    <input type=hidden name=\"TermUrl\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/optimalpayments.php\">\n    <input type=hidden name=\"MD\" value=\"" . $params["invoiceid"] . "\">\n    <noscript>\n    <div class=\"errorbox\"><b>JavaScript is currently disabled or is not supported by your browser.</b><br />Please click the continue button to proceed with the processing of your transaction.</div>\n    <p align=\"center\"><input type=\"submit\" value=\"Continue >>\" /></p>\n    </noscript>\n    </form>";
                return $code;
            }
            $captureResult = optimalpayments_capture($params);
            if ($captureResult["status"] == "success") {
                addInvoicePayment($params["invoiceid"], $captureResult["transid"], "", "", "optimalpayments", "on");
                sendMessage("Credit Card Payment Confirmation", $params["invoiceid"]);
            }
            logTransaction($params["paymentmethod"], $captureResult["rawdata"], ucfirst($captureResult["status"]));
            return $captureResult["status"];
        }
        logTransaction($params["paymentmethod"], $data, "Failed");
        $code = "<form method=\"post\" action=\"" . $params["systemurl"] . "/modules/gateways/callback/optimalpayments.php\">\n    <input type=\"hidden\" name=\"MD\" value=\"" . $params["invoiceid"] . "\" />\n    <input type=\"hidden\" name=\"failed\" value=\"true\" />\n    <noscript>\n    <div class=\"errorbox\"><b>JavaScript is currently disabled or is not supported by your browser.</b><br />Please click the continue button to proceed with the processing of your transaction.</div>\n    <p align=\"center\"><input type=\"submit\" value=\"Continue >>\" /></p>\n    </noscript>\n    </form>";
        return $code;
    }
    $captureResult = optimalpayments_capture($params);
    if ($captureResult["status"] == "success") {
        addInvoicePayment($params["invoiceid"], $captureResult["transid"], "", "", "optimalpayments", "on");
        sendMessage("Credit Card Payment Confirmation", $params["invoiceid"]);
    }
    logTransaction($params["paymentmethod"], $captureResult["rawdata"], ucfirst($captureResult["status"]));
    return $captureResult["status"];
}
function optimalpayments_capture($params)
{
    global $remote_ip;
    $url = "https://webservices.optimalpayments.com/creditcardWS/CreditCardServlet/v1";
    if ($params["testmode"]) {
        $url = "https://webservices.test.optimalpayments.com/creditcardWS/CreditCardServlet/v1";
    }
    $cardType = optimalpayments_cardtype($params["cardtype"]);
    if ($params["country"] == "US") {
        $state = "<state>" . $params["clientdetails"]["state"] . "</state>";
    } else {
        $state = "<region>" . $params["clientdetails"]["state"] . "</region>";
    }
    $xml = "<ccAuthRequestV1 xmlns=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\"\nxmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\nxsi:schemaLocation=\"http://www.optimalpayments.com/creditcard/xmlschema/v1\">\n<merchantAccount>\n<accountNum>" . $params["accountnumber"] . "</accountNum>\n<storeID>" . $params["merchantid"] . "</storeID>\n<storePwd>" . $params["merchantpw"] . "</storePwd>\n</merchantAccount>\n<merchantRefNum>" . $params["invoiceid"] . "</merchantRefNum>\n<amount>" . $params["amount"] . "</amount>\n<card>\n<cardNum>" . $params["cardnum"] . "</cardNum>\n<cardExpiry>\n<month>" . substr($params["cardexp"], 0, 2) . "</month>\n<year>20" . substr($params["cardexp"], 2, 2) . "</year>\n</cardExpiry>\n<cardType>" . $cardType . "</cardType>\n";
    if ($params["cccvv"]) {
        $xml .= "<cvdIndicator>1</cvdIndicator>\n<cvd>" . $params["cccvv"] . "</cvd>\n";
    } else {
        $xml .= "<cvdIndicator>0</cvdIndicator>\n";
    }
    $xml .= "</card>\n<billingDetails>\n<cardPayMethod>WEB</cardPayMethod>\n<firstName>" . $params["clientdetails"]["firstname"] . "</firstName>\n<lastName>" . $params["clientdetails"]["lastname"] . "</lastName>\n<street>" . $params["clientdetails"]["address1"] . "</street>\n<city>" . $params["clientdetails"]["city"] . "</city>\n" . $state . "\n<country>" . $params["clientdetails"]["country"] . "</country>\n<zip>" . $params["clientdetails"]["postcode"] . "</zip>\n<phone>" . $params["clientdetails"]["phonenumber"] . "</phone>\n<email>" . $params["clientdetails"]["email"] . "</email>\n</billingDetails>\n<recurring>\n<recurringIndicator>R</recurringIndicator>\n</recurring>\n<customerIP>" . $remote_ip . "</customerIP>\n</ccAuthRequestV1>";
    $query_str = "txnMode=ccPurchase&txnRequest=" . urlencode($xml);
    $data = curlCall($url, $query_str);
    $xmlData = XMLtoArray($data);
    $xmlData = $xmlData["CCTXNRESPONSEV1"];
    if ($xmlData["CODE"] == "0") {
        return array("status" => "success", "transid" => $xmlData["txnNumber"], "rawdata" => $xmlData);
    }
    return array("status" => "declined", "rawdata" => $xmlData);
}
function optimalpayments_cardtype($cardType)
{
    $cardType = strtolower($cardType);
    if ($cardType == "visa") {
        $cardType = "VI";
    } else {
        if ($cardType == "mastercard") {
            $cardType = "MC";
        } else {
            if ($cardType == "american express") {
                $cardType = "AM";
            } else {
                if ($cardType == "diners club") {
                    $cardType = "DC";
                } else {
                    if ($cardType == "discover") {
                        $cardType = "DI";
                    } else {
                        if ($cardType == "jcb") {
                            $cardType = "JC";
                        } else {
                            if ($cardType == "visa debit") {
                                $cardType = "VD";
                            } else {
                                if ($cardType == "solo") {
                                    $cardType = "SO";
                                } else {
                                    if ($cardType == "maestro") {
                                        $cardType = "MD";
                                    } else {
                                        if ($cardType == "switch") {
                                            $cardType = "SW";
                                        } else {
                                            if ($cardType == "visa electron") {
                                                $cardType = "VE";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $cardType;
}

?>