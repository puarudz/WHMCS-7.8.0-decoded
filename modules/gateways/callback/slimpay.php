<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$whmcs = App::self();
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$responseBody = file_get_contents("php://input");
$invoiceId = 0;
$additionalVars = "";
try {
    $transactionStatus = "Invalid Request";
    $invoiceId = 0;
    $params = array();
    $apiUrl = $paymentStatus = "";
    $apiToken = NULL;
    $schemeDetails = array();
    if (!$responseBody && ($reference = WHMCS\Session::getAndDelete("SlimPay"))) {
        $transientDataName = WHMCS\TransientData::getInstance()->retrieveByData($reference);
        $invoiceId = (int) str_replace(array("slimPayOrder"), "", $transientDataName);
        if ($invoiceId) {
            $invoice = new WHMCS\Invoice($invoiceId);
            $params = $invoice->getGatewayInvoiceParams();
            $apiUrl = "https://api.slimpay.net/";
            if ($params["sandbox"]) {
                $apiUrl = "https://api-sandbox.slimpay.net/";
            }
            $schemeDetails = slimpay_get_payment_scheme_details($params);
            $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
            $responseBody = slimpay_send_request("https://api.slimpay.net/alps#get-orders", $apiToken, array("creditorReference" => $params["creditor_reference"], "reference" => $reference));
            $paymentStatus = $responseBody["state"];
        }
    } else {
        if ($responseBody) {
            $responseBody = WHMCS\Filter\Json::safeDecode($responseBody, true);
            $transientDataName = WHMCS\TransientData::getInstance()->retrieveByData($responseBody["id"]);
            $paymentStatus = $responseBody["state"];
            $invoiceId = (int) str_replace(array("slimPayOrder"), "", $transientDataName);
            if ($invoiceId) {
                $invoice = new WHMCS\Invoice($invoiceId);
                $params = $invoice->getGatewayInvoiceParams();
                $apiUrl = "https://api.slimpay.net/";
                if ($params["sandbox"]) {
                    $apiUrl = "https://api-sandbox.slimpay.net/";
                }
                $schemeDetails = slimpay_get_payment_scheme_details($params);
                $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
            }
        } else {
            throw new WHMCS\Exception("Invalid Access Attempt");
        }
    }
    if ($apiToken && $paymentStatus) {
        if (strpos($paymentStatus, "closed.aborted") === 0) {
            WHMCS\TransientData::getInstance()->delete($transientDataName);
            $transactionStatus = "Mandate Abandoned";
        } else {
            if (strpos($paymentStatus, "closed.completed") === 0) {
                $responseBody = slimpay_send_request($responseBody["_links"]["https://api.slimpay.net/alps#get-payment"]["href"], $apiToken);
                $status = $responseBody["executionStatus"];
                if (array_key_exists("pendingSuccessOnOrder", $params) && $params["pendingSuccessOnOrder"] && !in_array($status, array("rejected", "notprocessed"))) {
                    $status = "processed";
                }
                switch ($status) {
                    case "rejected":
                    case "notprocessed":
                        WHMCS\TransientData::getInstance()->delete($transientDataName);
                        $additionalVars = "&paymentfailed=true";
                        $transactionStatus = "Payment Rejected";
                        break;
                    case "processed":
                        $dueDate = WHMCS\Carbon::parse($responseBody["executionDate"])->tz(date_default_timezone_get());
                        if ($dueDate <= WHMCS\Carbon::now()) {
                            $clientCurrency = $params["clientdetails"]["currency"];
                            $amount = $responseBody["amount"];
                            $paymentCurrency = $params["currencyId"];
                            if ($paymentCurrency && $clientCurrency != $paymentCurrency) {
                                $amount = convertCurrency($amount, $paymentCurrency, $clientCurrency);
                            }
                            $additionalVars = "&paymentsuccess=true";
                            $transactionStatus = "Payment Approved";
                            addTransaction($params["userid"], 0, "Invoice Payment", $amount, 0, 0, "slimpay", $responseBody["reference"], $invoiceId);
                            WHMCS\TransientData::getInstance()->delete($transientDataName);
                            $clientModel = $params["clientdetails"]["model"];
                            if (empty($params["gatewayid"])) {
                                slimpay_get_and_save_mandate($responseBody["_links"]["https://api.slimpay.net/alps#get-mandate"]["href"], $apiToken, $clientModel);
                            }
                        }
                        break;
                    default:
                        $invoiceModel = WHMCS\Billing\Invoice::findOrFail($invoiceId);
                        $invoiceModel->status = "Payment Pending";
                        $invoiceModel->save();
                        $transactionStatus = "Payment Pending";
                }
            }
        }
    } else {
        $transactionStatus = "Invalid/Missing Invoice Id";
    }
} catch (Exception $e) {
    $responseBody = $e->getMessage();
    $transactionStatus = "Error";
}
logTransaction("slimpay", $responseBody, $transactionStatus);
$file = "clientarea.php";
$vars = "";
if ($invoiceId) {
    $file = "viewinvoice.php";
    $vars = "id=" . $invoiceId . $additionalVars;
}
redirSystemURL($vars, $file);
WHMCS\Terminus::getInstance()->doExit();

?>