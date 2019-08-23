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
function sagepaytokens_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "SagePay Tokens"), "vendorid" => array("FriendlyName" => "Vendor ID", "Type" => "text", "Size" => "20"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function sagepaytokens_storeremote($params)
{
    $subdomain = $params["testmode"] ? "test" : "live";
    if ($params["action"] == "delete") {
        if (!$params["gatewayid"] || $params["gatewayid"] == "x") {
            return array("status" => "error", "rawdata" => "Clear Attempt but No Existing Token Stored");
        }
        $url = "https://" . $subdomain . ".sagepay.com/gateway/service/removetoken.vsp";
        $fields = array();
        $fields["VPSProtocol"] = "3.00";
        $fields["TxType"] = "REMOVETOKEN";
        $fields["Vendor"] = $params["vendorid"];
        $fields["Token"] = $params["gatewayid"];
        $results = sagepaytokens_call($url, $fields);
        if ($results["Status"] == "OK") {
            return array("status" => "success", "rawdata" => $results, "gatewayid" => "");
        }
        return array("status" => "error", "rawdata" => $results);
    }
    if ($params["action"] == "update") {
        $url = "https://" . $subdomain . ".sagepay.com/gateway/service/removetoken.vsp";
        $fields = array();
        $fields["VPSProtocol"] = "3.00";
        $fields["TxType"] = "REMOVETOKEN";
        $fields["Vendor"] = $params["vendorid"];
        $fields["Token"] = $params["gatewayid"];
        $results = sagepaytokens_call($url, $fields);
    }
    $url = "https://" . $subdomain . ".sagepay.com/gateway/service/directtoken.vsp";
    $fields = array();
    $fields["VPSProtocol"] = "3.00";
    $fields["TxType"] = "TOKEN";
    $fields["Vendor"] = $params["vendorid"];
    $fields["Currency"] = $params["currency"];
    $fields["CardHolder"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $fields["CardNumber"] = $params["cardnum"];
    if ($params["cardstart"]) {
        $fields["StartDate"] = $params["cardstart"];
    }
    $fields["ExpiryDate"] = $params["cardexp"];
    if ($params["cardissuenum"]) {
        $fields["IssueNumber"] = $params["cardissuenum"];
    }
    if ($params["cardcvv"]) {
        $fields["CV2"] = $params["cardcvv"];
    }
    $fields["CardType"] = sagepaytokens_getcardtype($params["cardtype"]);
    $results = sagepaytokens_call($url, $fields);
    if ($results["Status"] == "OK") {
        return array("status" => "success", "rawdata" => $results, "gatewayid" => $results["Token"]);
    }
    return array("status" => "error", "rawdata" => $results);
}
function sagepaytokens_3dsecure($params)
{
    $subdomain = $params["testmode"] ? "test" : "live";
    $url = "https://" . $subdomain . ".sagepay.com/gateway/service/vspdirect-register.vsp";
    $fields = array();
    $fields["VPSProtocol"] = "3.00";
    $fields["TxType"] = "PAYMENT";
    $fields["Vendor"] = $params["vendorid"];
    $fields["VendorTxCode"] = $params["invoiceid"] . "-" . date("YmdHis");
    $fields["Amount"] = $params["amount"];
    $fields["Currency"] = $params["currency"];
    $fields["Description"] = $params["companyname"] . " - Invoice #" . $params["invoiceid"];
    $fields["Token"] = $params["gatewayid"];
    $fields["StoreToken"] = "1";
    if ($params["cccvv"]) {
        $fields["CV2"] = $params["cccvv"];
    }
    $fields["BillingSurname"] = $params["clientdetails"]["lastname"];
    $fields["BillingFirstnames"] = $params["clientdetails"]["firstname"];
    $fields["BillingAddress1"] = $params["clientdetails"]["address1"];
    $fields["BillingAddress2"] = $params["clientdetails"]["address2"];
    $fields["BillingCity"] = $params["clientdetails"]["city"];
    if ($params["clientdetails"]["country"] == "US") {
        $fields["BillingState"] = $params["clientdetails"]["state"];
    }
    $fields["BillingPostCode"] = $params["clientdetails"]["postcode"];
    $fields["BillingCountry"] = $params["clientdetails"]["country"];
    $fields["BillingPhone"] = $params["clientdetails"]["phonenumber"];
    $fields["CustomerEMail"] = $params["clientdetails"]["email"];
    if (filter_var($remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $fields["ClientIPAddress"] = $remote_ip;
    }
    $fields["CardType"] = sagepaytokens_getcardtype($params["cardtype"]);
    $results = sagepaytokens_call($url, $fields);
    $baseStatus = $results["Status"];
    switch ($baseStatus) {
        case "3DAUTH":
            logTransaction("SagePay Tokens 3DAuth", $results, "Ok");
            WHMCS\Module\Storage\EncryptedTransientStorage::forModule("sagepaytokens")->setValue("sagepaytokensinvoiceid", $params["invoiceid"]);
            $code = "<form method=\"post\" action=\"" . $results["ACSURL"] . "\">\n            <input type=\"hidden\" name=\"PaReq\" value=\"" . $results["PAReq"] . "\">\n            <input type=\"hidden\" name=\"TermUrl\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/sagepaytokens.php?invoiceid=" . $params["invoiceid"] . "\">\n            <input type=\"hidden\" name=\"MD\" value=\"" . $results["MD"] . "\">\n            <noscript>\n            <div class=\"errorbox\"><b>JavaScript is currently disabled or is not supported by your browser.</b><br />Please click the continue button to proceed with the processing of your transaction.</div>\n            <p align=\"center\"><input type=\"submit\" value=\"Continue >>\" /></p>\n            </noscript>\n            </form>";
            return $code;
        case "OK":
            addInvoicePayment($params["invoiceid"], $response["VPSTxId"], "", "", "sagepaytokens", "on");
            logTransaction($params["paymentmethod"], $results, "Successful");
            sendMessage("Credit Card Payment Confirmation", $params["invoiceid"]);
            $result = "success";
            return $result;
        case "NOTAUTHED":
            $transactionStatus = "Not Authed";
            break;
        case "REJECTED":
            $transactionStatus = "Rejected";
            break;
        case "FAIL":
            $transactionStatus = "Failed";
            break;
        default:
            $transactionStatus = "Error";
            break;
    }
    logTransaction($params["paymentmethod"], $results, $transactionStatus);
    sendMessage("Credit Card Payment Failed", $params["invoiceid"]);
    $result = "declined";
    return $result;
}
function sagepaytokens_capture($params)
{
    global $remote_ip;
    $subdomain = $params["testmode"] ? "test" : "live";
    $url = "https://" . $subdomain . ".sagepay.com/gateway/service/vspdirect-register.vsp";
    $fields = array();
    $fields["VPSProtocol"] = "3.00";
    $fields["TxType"] = "PAYMENT";
    $fields["Vendor"] = $params["vendorid"];
    $fields["VendorTxCode"] = $params["invoiceid"] . "-" . date("YmdHis");
    $fields["Amount"] = $params["amount"];
    $fields["Currency"] = $params["currency"];
    $fields["Description"] = $params["companyname"] . " - Invoice #" . $params["invoiceid"];
    $fields["Token"] = $params["gatewayid"];
    $fields["StoreToken"] = "1";
    if ($params["cccvv"]) {
        $fields["CV2"] = $params["cccvv"];
    }
    $fields["BillingSurname"] = $params["clientdetails"]["lastname"];
    $fields["BillingFirstnames"] = $params["clientdetails"]["firstname"];
    $fields["BillingAddress1"] = $params["clientdetails"]["address1"];
    $fields["BillingAddress2"] = $params["clientdetails"]["address2"];
    $fields["BillingCity"] = $params["clientdetails"]["city"];
    if ($params["clientdetails"]["country"] == "US") {
        $fields["BillingState"] = $params["clientdetails"]["state"];
    }
    $fields["BillingPostCode"] = $params["clientdetails"]["postcode"];
    $fields["BillingCountry"] = $params["clientdetails"]["country"];
    $fields["BillingPhone"] = $params["clientdetails"]["phonenumber"];
    $fields["CustomerEMail"] = $params["clientdetails"]["email"];
    if (filter_var($remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $fields["ClientIPAddress"] = $remote_ip;
    }
    $fields["CardType"] = sagepaytokens_getcardtype($params["cardtype"]);
    $fields["ApplyAVSCV2"] = "2";
    $fields["Apply3DSecure"] = "2";
    $fields["AccountType"] = "C";
    if ($params["cardtype"] == "Maestro" || $params["cardtype"] == "Solo") {
        $fields["AccountType"] = "M";
    }
    if ($params["cardtype"] == "American Express" || $params["cardtype"] == "Laser") {
        $fields["AccountType"] = "E";
    }
    $results = sagepaytokens_call($url, $fields);
    if ($results["Status"] == "OK") {
        return array("status" => "success", "rawdata" => $results, "transid" => $results["VPSTxId"]);
    }
    return array("status" => "error", "rawdata" => $results);
}
function sagepaytokens_call($url, $fields)
{
    $data = curlCall($url, $fields);
    $lines = explode("\n", $data);
    $results = array();
    foreach ($lines as $line) {
        $line = explode("=", $line, 2);
        $results[trim($line[0])] = trim($line[1]);
    }
    return $results;
}
function sagepaytokens_getcardtype($cardtype)
{
    if ($cardtype == "Visa") {
        $cardtype = "VISA";
    } else {
        if ($cardtype == "MasterCard") {
            $cardtype = "MC";
        } else {
            if ($cardtype == "American Express") {
                $cardtype = "AMEX";
            } else {
                if ($cardtype == "Diners Club") {
                    $cardtype = "DC";
                } else {
                    if ($cardtype == "Discover") {
                        $cardtype = "DC";
                    } else {
                        if ($cardtype == "EnRoute") {
                            $cardtype = "VISA";
                        } else {
                            if ($cardtype == "JCB") {
                                $cardtype = "JCB";
                            } else {
                                if ($cardtype == "Delta") {
                                    $cardtype = "DELTA";
                                } else {
                                    if ($cardtype == "Solo") {
                                        $cardtype = "SOLO";
                                    } else {
                                        if ($cardtype == "Switch") {
                                            $cardtype = "SWITCH";
                                        } else {
                                            if ($cardtype == "Maestro") {
                                                $cardtype = "MAESTRO";
                                            } else {
                                                if ($cardtype == "Electron") {
                                                    $cardtype = "UKE";
                                                } else {
                                                    if ($cardtype == "Visa Electron") {
                                                        $cardtype = "UKE";
                                                    } else {
                                                        if ($cardtype == "Visa Delta") {
                                                            $cardtype = "DELTA";
                                                        } else {
                                                            if ($cardtype == "Visa Debit") {
                                                                $cardtype = "DELTA";
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
                }
            }
        }
    }
    return $cardtype;
}

?>