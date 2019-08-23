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
function asiapay_config()
{
    $secureHashDescription = "";
    $hashKey = get_query_val("tblpaymentgateways", "value", array("setting" => "secureHashKey", "gateway" => "asiapay"));
    if (empty($hashKey)) {
        $secureHashDescription = "Secure Hash Key is required for additional security with Asia Pay.";
    }
    $configArray = array("FriendlyName" => array("Type" => "System", "Value" => "AsiaPay"), "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "secureHashKey" => array("FriendlyName" => "Secure Hash Key (if enabled)", "Type" => "text", "Size" => "40", "Description" => $secureHashDescription), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configArray;
}
function asiapay_link(array $params)
{
    if ($params["testmode"]) {
        $posturl = "https://test.paydollar.com/b2cDemo/eng/payment/payForm.jsp";
    } else {
        $posturl = "https://www.paydollar.com/b2c2/eng/payment/payForm.jsp";
        if (!$params["secureHashKey"]) {
            return "This payment method is not currently available";
        }
    }
    if (isset($params["cardtype"]) && 0 < strlen($params["cardtype"])) {
        if ($params["cardtype"] == "Visa") {
            $payMethod = "VISA";
        } else {
            if ($params["cardtype"] == "MasterCard") {
                $payMethod = "Master";
            } else {
                if ($params["cardtype"] == "Diners Club") {
                    $payMethod = "Diners";
                } else {
                    if ($params["cardtype"] == "American Express") {
                        $payMethod = "AMEX";
                    } else {
                        $payMethod = $params["cardtype"];
                    }
                }
            }
        }
    } else {
        $payMethod = "ALL";
    }
    $merchantId = $params["merchantid"];
    $amount = $params["amount"];
    $orderRef = $params["invoiceid"];
    $mpsMode = "NIL";
    $successUrl = $params["systemurl"] . "/modules/gateways/callback/asiapay.php";
    $failUrl = $params["systemurl"] . "/modules/gateways/callback/asiapay.php";
    $cancelUrl = $params["systemurl"] . "/modules/gateways/callback/asiapay.php";
    $payType = "N";
    $lang = "E";
    $currCodeArr = array("HKD" => 344, "SGD" => 702, "CNY" => 156, "JPY" => 392, "TWD" => 901, "AUD" => "036", "EUR" => 978, "GBP" => 826, "CAD" => 124, "MOP" => 446, "PHP" => 608, "THB" => 764, "MYR" => 458, "IDR" => 360, "KRW" => 410, "SAR" => 682, "NZD" => 784, "BND" => "096");
    if (array_key_exists($params["currency"], $currCodeArr)) {
        $currCode = $currCodeArr[$params["currency"]];
    } else {
        $currCode = 840;
    }
    if (isset($params["secureHashKey"]) && 0 < strlen(trim($params["secureHashKey"]))) {
        $hashArr = array($merchantId, $orderRef, $currCode, $amount, $payType, $params["secureHashKey"]);
        $hash = sha1(implode("|", $hashArr));
        $secureHashCode = "<input type=\"hidden\" name=\"secureHash\" value=\"" . $hash . "\">";
    } else {
        $secureHashCode = "";
    }
    $link = "<form name=\"payFormCcard\" method=\"post\" action=\"" . $posturl . "\">\n<input type=\"hidden\" name=\"merchantId\" value=\"" . $merchantId . "\">\n<input type=\"hidden\" name=\"amount\" value=\"" . $amount . "\" >\n<input type=\"hidden\" name=\"orderRef\" value=\"" . $orderRef . "\">\n<input type=\"hidden\" name=\"currCode\" value=\"" . $currCode . "\" >\n<input type=\"hidden\" name=\"mpsMode\" value=\"" . $mpsMode . "\" >\n<input type=\"hidden\" name=\"successUrl\" value=\"" . $successUrl . "\">\n<input type=\"hidden\" name=\"failUrl\" value=\"" . $failUrl . "\">\n<input type=\"hidden\" name=\"cancelUrl\" value=\"" . $cancelUrl . "\">\n<input type=\"hidden\" name=\"payType\" value=\"" . $payType . "\">\n<input type=\"hidden\" name=\"lang\" value=\"" . $lang . "\">\n<input type=\"hidden\" name=\"payMethod\" value=\"" . $payMethod . "\">\n" . $secureHashCode . "\n<input type=\"submit\" name=\"submit\" value=\"Submit\">\n</form>\n";
    return $link;
}

?>