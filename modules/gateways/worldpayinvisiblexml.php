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
$GATEWAYMODULE["worldpayinvisiblexmlname"] = "worldpayinvisiblexml";
$GATEWAYMODULE["worldpayinvisiblexmlvisiblename"] = "WorldPay Invisible XML";
$GATEWAYMODULE["worldpayinvisiblexmltype"] = "CC";
function worldpayinvisiblexml_activate()
{
    defineGatewayField("worldpayinvisiblexml", "text", "merchantcode1", "", "Merchant Code", "20", "First Transaction");
    defineGatewayField("worldpayinvisiblexml", "text", "merchantcode2", "", "Merchant Code", "20", "Recurring Maestro & Solo");
    defineGatewayField("worldpayinvisiblexml", "text", "merchantcode3", "", "Merchant Code", "20", "Recurring Any Other");
    defineGatewayField("worldpayinvisiblexml", "text", "merchantcodeamex", "", "Merchant Code", "20", "Amex Only Merchant Code");
    defineGatewayField("worldpayinvisiblexml", "text", "merchantpw", "", "Password", "20", "");
    defineGatewayField("worldpayinvisiblexml", "text", "instid", "", "Installation ID", "20", "");
    defineGatewayField("worldpayinvisiblexml", "yesno", "testmode", "", "Test Mode", "", "");
    defineGatewayField("worldpayinvisiblexml", "text", "cookiestore", "", "Cookie Path", "20", "Path to your cookie store WITH trailing slash. *THIS IS IMPORTANT*");
    defineGatewayField("worldpayinvisiblexml", "yesno", "cvvpass", "", "CVV Pass-through.", "", "Pass CVV after card authentication.");
}
function worldpayinvisiblexml_link($params)
{
    $code = "<form method=\"post\" action=\"" . $params["systemurl"] . "/creditcard.php\" name=\"paymentfrm\">\n<input type=\"hidden\" name=\"invoiceid\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\"></input>\n</form>";
    return $code;
}
function worldpayinvisiblexml_3dsecure($params)
{
    if ($params["cardtype"] == "American Express") {
        $merchantCode = $params["merchantcodeamex"];
    } else {
        $merchantCode = $params["merchantcode1"];
    }
    $password = $params["merchantpw"];
    $instId = $params["instid"];
    $cookiestore = $params["cookiestore"];
    if ($params["cardtype"] == "American Express") {
        $orderCode = "A-" . date("YmdHis") . "-" . $params["invoiceid"];
    } else {
        $orderCode = "E-" . date("YmdHis") . "-" . $params["invoiceid"];
    }
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
    $cvv = $params["cccvv"];
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
    $xml .= "</paymentDetails>";
    $xml .= "<shopper>";
    $xml .= "<shopperEmailAddress>" . $orderShopperEmail . "</shopperEmailAddress>";
    $xml .= "<browser>";
    $xml .= "<acceptHeader>" . $acceptHeader . "</acceptHeader>";
    $xml .= "<userAgentHeader>" . $userAgentHeader . "</userAgentHeader>";
    $xml .= "</browser>";
    $xml .= "</shopper>";
    $xml .= "</order></submit></paymentService>";
    if ($params["testmode"]) {
        $url = "https://secure-test.wp3.rbsworldpay.com/jsp/merchant/xml/paymentService.jsp";
    } else {
        $url = "https://secure.worldpay.com/jsp/merchant/xml/paymentService.jsp";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, $merchantCode . ":" . $password);
    curl_setopt($ch, CURLOPT_COOKIEJAR, (string) $cookiestore . $invoiceID . ".cookie");
    curl_setopt($ch, CURLOPT_TIMEOUT, 240);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result_tmp = curl_exec($ch);
    if (curl_error($ch)) {
        $result_tmp = "Curl Error: " . curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    logTransaction($params["paymentmethod"], $result_tmp, "Received");
    $result_arr = XMLtoArray($result_tmp);
    $PostUrl = $result_arr["PAYMENTSERVICE"]["REPLY"]["ORDERSTATUS"]["REQUESTINFO"]["REQUEST3DSECURE"]["ISSUERURL"];
    $PaReq = $result_arr["PAYMENTSERVICE"]["REPLY"]["ORDERSTATUS"]["REQUESTINFO"]["REQUEST3DSECURE"]["PAREQUEST"];
    $echoData = $result_arr["PAYMENTSERVICE"]["REPLY"]["ORDERSTATUS"]["ECHODATA"];
    $lastevent = $result_arr["PAYMENTSERVICE"]["REPLY"]["ORDERSTATUS"]["PAYMENT"]["LASTEVENT"];
    if (!$PaReq) {
        if ($lastevent == "AUTHORISED") {
            addInvoicePayment($invoiceID, $orderCode, $raworderAmount, "", "worldpayinvisiblexml", "on");
            logTransaction($params["paymentmethod"], $result_tmp, "Successful");
            sendMessage("Credit Card Payment Confirmation", $params["invoiceid"]);
            $result = "success";
        } else {
            logTransaction($params["paymentmethod"], $result_tmp, "Declined");
            sendMessage("Credit Card Payment Failed", $params["invoiceid"]);
            $result = "declined";
        }
        return $result;
    }
    delete_query("tblgatewaylog", array("gateway" => "WorldPay Invisible XML Callback", "result" => "echoData Not Found"));
    delete_query("tblgatewaylog", "gateway LIKE '%WPI%' AND date<='" . date("Y-m-d H:i:s", strtotime("-10 minutes") . "'"));
    delete_query("tblgatewaylog", array("gateway" => "WPIORDERCODE" . $params["invoiceid"]));
    delete_query("tblgatewaylog", array("gateway" => "WPIECHODATA" . $params["invoiceid"]));
    delete_query("tblgatewaylog", array("gateway" => "WPICPDATA" . $params["invoiceid"]));
    insert_query("tblgatewaylog", array("date" => "now()", "gateway" => "WPIORDERCODE" . $params["invoiceid"], "data" => $orderCode));
    insert_query("tblgatewaylog", array("date" => "now()", "gateway" => "WPIECHODATA" . $params["invoiceid"], "data" => $echoData));
    if ($params["cvvpass"]) {
        insert_query("tblgatewaylog", array("date" => "now()", "gateway" => "WPICPDATA" . $params["invoiceid"], "data" => $cvv));
    }
    $code = "<form action=\"" . $PostUrl . "\" method=\"post\">\n<input type=\"hidden\" name=\"PaReq\" value=\"" . $PaReq . "\" />\n<input type=\"hidden\" name=\"TermUrl\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/worldpayinvisiblexml.php\" />\n<input type=\"hidden\" name=\"MD\" value=\"" . $params["invoiceid"] . "\" />\n<!-- <input type=\"submit\" name=\"Click to Authenticate Card\"> -->\n</form>";
    return $code;
}
function worldpayinvisiblexml_capture($params)
{
    if ($params["cardtype"] == "Maestro" || $params["cardtype"] == "Solo") {
        $merchantCode = $params["merchantcode2"];
    } else {
        if ($params["cardtype"] == "American Express") {
            $merchantCode = $params["merchantcodeamex"];
        } else {
            $merchantCode = $params["merchantcode3"];
        }
    }
    $password = $params["merchantpw"];
    $instId = $params["instid"];
    $cookiestore = $params["cookiestore"];
    if ($params["cardtype"] == "Maestro" || $params["cardtype"] == "Solo") {
        $orderCode = "M-" . date("YmdHis") . "-" . $params["invoiceid"];
    } else {
        if ($params["cardtype"] == "American Express") {
            $orderCode = "A-" . date("YmdHis") . "-" . $params["invoiceid"];
        } else {
            $orderCode = "R-" . date("YmdHis") . "-" . $params["invoiceid"];
        }
    }
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
    $cvv = $params["cccvv"];
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
    $xml .= "</paymentDetails>";
    $xml .= "<shopper>";
    $xml .= "<shopperEmailAddress>" . $orderShopperEmail . "</shopperEmailAddress>";
    $xml .= "<browser>";
    $xml .= "<acceptHeader>" . $acceptHeader . "</acceptHeader>";
    $xml .= "<userAgentHeader>" . $userAgentHeader . "</userAgentHeader>";
    $xml .= "</browser>";
    $xml .= "</shopper>";
    $xml .= "</order></submit></paymentService>";
    if ($params["testmode"]) {
        $url = "https://secure-test.wp3.rbsworldpay.com/jsp/merchant/xml/paymentService.jsp";
    } else {
        $url = "https://secure.worldpay.com/jsp/merchant/xml/paymentService.jsp";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, $merchantCode . ":" . $password);
    curl_setopt($ch, CURLOPT_COOKIEJAR, (string) $cookiestore . $invoiceID . ".cookie");
    curl_setopt($ch, CURLOPT_TIMEOUT, 240);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result_tmp = curl_exec($ch);
    if (curl_error($ch)) {
        $result_tmp = "Curl Error: " . curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    $result_arr = XMLtoArray($result_tmp);
    $lastevent = $result_arr["PAYMENTSERVICE"]["REPLY"]["ORDERSTATUS"]["PAYMENT"]["LASTEVENT"];
    if ($lastevent == "AUTHORISED") {
        return array("status" => "success", "transid" => $orderCode, "rawdata" => $result_tmp);
    }
    return array("status" => "declined", "rawdata" => $result_tmp);
}

?>