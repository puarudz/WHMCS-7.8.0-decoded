<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\TCO;

class Standard
{
    public function link(array $params = array())
    {
        $code = "";
        if ($params["recurringBilling"] != "disablerecur") {
            $recurrings = getRecurringBillingValues($params["invoiceid"]);
            if ($recurrings) {
                $code .= "<form action=\"" . $params["systemurl"] . "/modules/gateways/tco.php?recurring=1\" method=\"post\">\n        <input type=\"hidden\" name=\"invoiceid\" value=\"" . $params["invoiceid"] . "\" />\n        <input type=\"submit\" class=\"btn btn-primary\" value=\"" . \Lang::trans("invoicesubscriptionpayment") . "\" />\n        </form>";
            }
        }
        if ($params["recurringBilling"] == "forcerecur" && $code) {
            return $code;
        }
        $lang = $params["clientdetails"]["language"];
        if (!$lang) {
            $lang = \WHMCS\Config\Setting::getValue("Language");
        }
        $lang = Helper::languageInput($lang);
        $url = "https://www.2checkout.com/checkout";
        if ($params["sandbox"] == "on") {
            $url = "https://sandbox.2checkout.com/checkout";
        }
        $receiptUrl = $params["systemurl"] . "/modules/gateways/callback/2checkout.php";
        $code .= "<form action=\"" . $url . "/purchase\" method=\"post\">\n    <input type=\"hidden\" name=\"x_login\" value=\"" . $params["vendornumber"] . "\">\n    <input type=\"hidden\" name=\"x_invoice_num\" value=\"" . $params["invoiceid"] . "\">\n    <input type=\"hidden\" name=\"x_amount\" value=\"" . $params["amount"] . "\">\n    <input type=\"hidden\" name=\"currency_code\" value=\"" . $params["currency"] . "\">\n    <input type=\"hidden\" name=\"c_name\" value=\"" . $params["description"] . "\">\n    <input type=\"hidden\" name=\"c_description\" value=\"" . $params["description"] . "\">\n    <input type=\"hidden\" name=\"c_price\" value=\"" . $params["amount"] . "\">\n    <input type=\"hidden\" name=\"c_tangible\" value=\"N\">\n    <input type=\"hidden\" name=\"x_First_Name\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n    <input type=\"hidden\" name=\"x_Last_Name\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n    <input type=\"hidden\" name=\"x_Email\" value=\"" . $params["clientdetails"]["email"] . "\">\n    <input type=\"hidden\" name=\"x_Address\" value=\"" . $params["clientdetails"]["address1"] . "\">\n    <input type=\"hidden\" name=\"x_City\" value=\"" . $params["clientdetails"]["city"] . "\">\n    <input type=\"hidden\" name=\"x_State\" value=\"" . $params["clientdetails"]["state"] . "\">\n    <input type=\"hidden\" name=\"x_Zip\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n    <input type=\"hidden\" name=\"x_Country\" value=\"" . $params["clientdetails"]["country"] . "\">\n    <input type=\"hidden\" name=\"x_Phone\" value=\"" . $params["clientdetails"]["phonenumber"] . "\">\n    <input type=\"hidden\" name=\"fixed\" value=\"Y\">\n    <input type=\"hidden\" name=\"return_url\" value=\"" . $params["systemurl"] . "/cart.php\">\n    <input type=\"hidden\" name=\"return_url\" value=\"" . $params["systemurl"] . "/cart.php\">\n    " . $lang . "\n    <input type=\"hidden\" name=\"x_receipt_link_url\" value=\"" . $receiptUrl . "\">";
        if ($params["demomode"] == "on") {
            $code .= "<input type=\"hidden\" name=\"demo\" value=\"Y\">";
        }
        $code .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"" . \Lang::trans("invoiceoneoffpayment") . "\" />\n    </form>";
        return $code;
    }
    public function callback(array $params = array())
    {
        $gatewaymodule = "tco";
        if ($params["secretword"]) {
            $string_to_hash = $_REQUEST["sale_id"] . $params["vendornumber"] . $_REQUEST["invoice_id"] . $params["secretword"];
            $check_key = strtoupper(md5($string_to_hash));
            if (hash_equals($check_key, $_POST["md5_hash"]) === false) {
                logTransaction($params["paymentmethod"], $_POST, "MD5 Hash Failure");
                return NULL;
            }
        }
        $message_type = $_POST["message_type"];
        $serviceid = $_POST["vendor_order_id"];
        $transid = $_POST["sale_id"];
        $recurringtransid = $transid . "-" . $_POST["invoice_id"];
        $amount = $_POST["invoice_list_amount"] ? $_POST["invoice_list_amount"] : $_POST["item_list_amount_1"];
        $recurstatus = trim($_POST["item_rec_status_1"]);
        $invoiceid = $_POST["item_id_1"] ? $_POST["item_id_1"] : $_POST["item_id_2"];
        $currency = $_POST["list_currency"];
        try {
            $currency = \WHMCS\Billing\Currency::where("code", $currency)->firstOrFail();
        } catch (\Exception $e) {
            logTransaction($params["paymentmethod"], $_POST, "Unrecognised Currency");
            return NULL;
        }
        if (in_array($message_type, array("FRAUD_STATUS_CHANGED", "ORDER_CREATED", "RECURRING_INSTALLMENT_SUCCESS"))) {
            if ($recurringtransid && $serviceid || $message_type == "RECURRING_INSTALLMENT_SUCCESS") {
                $invoiceid = findInvoiceID($serviceid, $transid);
            }
            $invoiceid = checkCbInvoiceID($invoiceid, $params["paymentmethod"]);
            $invoice = \WHMCS\Billing\Invoice::with("client", "client.currencyrel")->find($invoiceid);
        }
        $message_type = $_POST["message_type"];
        $fee = 0;
        if ($message_type == "FRAUD_STATUS_CHANGED" && !$params["skipfraudcheck"]) {
            $fraud_status = $_POST["fraud_status"];
            if ($fraud_status == "pass") {
                logTransaction($params["paymentmethod"], $_POST, "Fraud Status Pass");
                checkCbTransID($transid);
                $amount = Helper::convertCurrency($amount, $currency, $invoice);
                $fee = Helper::convertCurrency($fee, $currency, $invoice);
                $invoice->addPayment($amount, $transid, $fee, $gatewaymodule);
            } else {
                logTransaction($params["paymentmethod"], $_POST, "Fraud Status Fail");
            }
        } else {
            if ($message_type == "ORDER_CREATED" && $params["skipfraudcheck"]) {
                logTransaction($params["paymentmethod"], $_POST, "Payment Success");
                checkCbTransID($transid);
                $amount = Helper::convertCurrency($amount, $currency, $invoice);
                $fee = Helper::convertCurrency($fee, $currency, $invoice);
                $invoice->addPayment($amount, $transid, $fee, $gatewaymodule);
            } else {
                if ($message_type == "RECURRING_INSTALLMENT_SUCCESS") {
                    checkCbTransID($recurringtransid);
                    if (!$invoiceid && !$serviceid) {
                        logTransaction($params["paymentmethod"], array_merge(array("InvoiceLookup" => "No Service ID Found in Callback"), $_POST), "Recurring Error");
                    } else {
                        if (!$invoiceid) {
                            logTransaction($params["paymentmethod"], array_merge(array("InvoiceLookup" => "No invoice match found for Service ID " . $serviceid . " or Subscription ID"), $_POST), "Recurring Error");
                        }
                    }
                    logTransaction($params["paymentmethod"], $_POST, "Recurring Success");
                    $amount = Helper::convertCurrency($amount, $currency, $invoice);
                    $fee = Helper::convertCurrency($fee, $currency, $invoice);
                    $invoice->addPayment($amount, $recurringtransid, $fee, $gatewaymodule);
                    if ($serviceid && $transid) {
                        update_query("tblhosting", array("subscriptionid" => $transid), array("id" => $serviceid));
                    }
                } else {
                    if ($message_type == "RECURRING_INSTALLMENT_FAILED") {
                        logTransaction($params["paymentmethod"], $_POST, "Recurring Failed");
                    } else {
                        logTransaction($params["paymentmethod"], $_POST, "Notification Only");
                    }
                }
            }
        }
    }
    public function clientCallback(array $params = array())
    {
        if ($params["secretword"]) {
            $string_to_hash = $params["secretword"] . $params["vendornumber"] . $_REQUEST["x_trans_id"] . $_REQUEST["x_amount"];
            $check_key = strtoupper(md5($string_to_hash));
            if (hash_equals($check_key, $_REQUEST["x_MD5_Hash"]) === false) {
                logTransaction($params["paymentmethod"], $_REQUEST, "MD5 Hash Failure");
                redirSystemURL("action=invoices", "clientarea.php");
            }
        }
        $outputText = "Payment Processing Completed. However it may take a while for 2CheckOut fraud verification" . " to complete and the payment to be reflected on your account." . " Please wait while you are redirected back to the client area...";
        $return = "<html>\n<head>\n<title>" . \WHMCS\Config\Setting::getValue("CompanyName") . "</title>\n</head>\n<body>\n<p>" . $outputText . "</p>\n";
        $responseCode = (int) \App::getFromRequest("x_response_code");
        if ($responseCode == 1) {
            $invoiceid = checkCbInvoiceID(\App::getFromRequest("x_invoice_num"), $params["paymentmethod"]);
            $baseUrl = \App::getSystemURL() . "viewinvoice.php?id=" . $invoiceid;
            if ($params["skipfraudcheck"]) {
                $return .= "<meta http-equiv=\"refresh\" content=\"2;url=" . $baseUrl . "&paymentsuccess=true\">";
            } else {
                $return .= "<meta http-equiv=\"refresh\" content=\"2;url=" . $baseUrl . "&pendingreview=true\">";
            }
        } else {
            $baseUrl = \App::getSystemURL() . "/clientarea.php?action=invoices";
            logTransaction($params["paymentmethod"], $_REQUEST, "Unsuccessful");
            $return .= "<meta http-equiv=\"refresh\" content=\"2;url=" . $baseUrl . "\">";
        }
        $return .= "\n</body>\n</html>";
        echo $return;
    }
}

?>