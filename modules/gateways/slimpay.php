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
function slimpay_MetaData()
{
    return array("failedEmail" => "Direct Debit Payment Failed", "successEmail" => "Direct Debit Payment Confirmation", "pendingEmail" => "Direct Debit Payment Pending");
}
function slimpay_config()
{
    $configArray = array("FriendlyName" => array("Type" => "System", "Value" => "SlimPay"), "app_id" => array("FriendlyName" => "App ID", "Type" => "text", "Size" => 40, "Description" => "Your SlimPay App ID"), "app_secret" => array("FriendlyName" => "App Secret", "Type" => "password", "Size" => 40, "Description" => "Your SlimPay App Secret"), "bacs_app_id" => array("FriendlyName" => "BACS App ID", "Type" => "text", "Size" => 40, "Description" => "Your SlimPay App ID for BACS payments"), "bacs_app_secret" => array("FriendlyName" => "BACS App Secret", "Type" => "password", "Size" => 40, "Description" => "Your SlimPay App Secret for BACS payments"), "creditor_reference" => array("FriendlyName" => "Creditor Reference", "Type" => "text", "Size" => 40, "Description" => "Your SlimPay Creditor Reference"), "sandbox" => array("FriendlyName" => "Sandbox", "Type" => "yesno", "Description" => "Tick to enable test mode"), "pendingSuccessOnOrder" => array("FriendlyName" => "Instant Activation for New Orders", "Type" => "yesno", "Description" => "Apply payment as soon as Direct Debit mandate is initiated for new orders"));
    return $configArray;
}
function slimpay_nolocalcc()
{
}
function slimpay_link(array $params)
{
    $apiUrl = "https://api.slimpay.net/";
    if ($params["sandbox"]) {
        $apiUrl = "https://api.preprod.slimpay.com/";
    }
    try {
        $schemeDetails = slimpay_get_payment_scheme_details($params);
        $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
        if (!$apiToken) {
            throw new WHMCS\Exception\Module\NotServicable("No API Token Available");
        }
        $initialResponseBody = slimpay_send_request($apiUrl, $apiToken);
    } catch (Exception $e) {
        logTransaction("slimpay", $e->getMessage(), "Error on Link Obtain", $params);
        return "An Unknown Error Occurred - Please contact Support";
    }
    $paymentCleared = Lang::trans("invoicePaymentPendingCleared");
    $orderReference = WHMCS\TransientData::getInstance()->retrieve("slimPayOrder" . $params["invoiceid"]);
    $paymentReference = WHMCS\TransientData::getInstance()->retrieve("slimPayPayment" . $params["invoiceid"]);
    if ($orderReference || $paymentReference) {
        try {
            if ($orderReference) {
                $uri = str_replace("{?creditorReference,reference}", "?creditorReference=" . $params["creditor_reference"] . "&reference=" . $orderReference, $initialResponseBody["_links"]["https://api.slimpay.net/alps#get-orders"]["href"]);
                $responseBody = slimpay_send_request($uri, $apiToken);
                $paymentStatus = $responseBody["state"];
                if (strpos($paymentStatus, "closed.aborted") === 0) {
                    WHMCS\TransientData::getInstance()->delete("slimPayOrder" . $params["invoiceid"]);
                } else {
                    if (strpos($paymentStatus, "closed.completed") === 0) {
                        $responseBody = slimpay_send_request($responseBody["_links"]["https://api.slimpay.net/alps#get-payment"]["href"], $apiToken);
                        $status = $responseBody["executionStatus"];
                        if (array_key_exists("pendingSuccessOnOrder", $params) && $params["pendingSuccessOnOrder"] && !in_array($status, array("rejected", "notprocessed"))) {
                            $status = "processed";
                        }
                        $clientModel = $params["clientdetails"]["model"];
                        slimpay_get_and_save_mandate($responseBody["_links"]["https://api.slimpay.net/alps#get-mandate"]["href"], $apiToken, $clientModel);
                        switch ($status) {
                            case "rejected":
                            case "notprocessed":
                                WHMCS\TransientData::getInstance()->delete("slimPayOrder" . $params["invoiceid"]);
                                logTransaction("slimpay", $responseBody, "Payment Failed", $params);
                                $invoice = WHMCS\Billing\Invoice::findOrFail($params["invoiceid"]);
                                $invoice->status = "Unpaid";
                                $invoice->save();
                                redir("id=" . $params["invoiceid"] . "&paymentfailed=true");
                                break;
                            case "processed":
                                $clientCurrency = $params["clientdetails"]["currency"];
                                $amount = $responseBody["amount"];
                                $paymentCurrency = $params["currencyId"];
                                if ($paymentCurrency && $clientCurrency != $paymentCurrency) {
                                    $amount = convertCurrency($amount, $paymentCurrency, $clientCurrency);
                                }
                                addTransaction($params["userid"], 0, "Invoice Payment", $amount, 0, 0, "slimpay", $responseBody["reference"], $params["invoiceid"]);
                                logTransaction("slimpay", $responseBody, "success", $params);
                                WHMCS\TransientData::getInstance()->delete("slimPayOrder" . $params["invoiceid"]);
                                redir("id=" . $params["invoiceid"] . "&paymentsuccess=true");
                                break;
                            default:
                                $invoice = WHMCS\Billing\Invoice::findOrFail($params["invoiceid"]);
                                $invoice->status = "Payment Pending";
                                $invoice->save();
                                return "<div class=\"alert alert-success\">\n    " . $paymentCleared . "\n</div>";
                        }
                    }
                }
            } else {
                if ($paymentReference) {
                    $uri = str_replace("{id}", $paymentReference, $initialResponseBody["_links"]["https://api.slimpay.net/alps#search-payment-by-id"]["href"]);
                    $responseBody = slimpay_send_request($uri, $apiToken);
                    switch ($responseBody["executionStatus"]) {
                        case "rejected":
                        case "notprocessed":
                            WHMCS\TransientData::getInstance()->delete("slimPayPayment" . $params["invoiceid"]);
                            $paymentReference = NULL;
                            $invoice = WHMCS\Billing\Invoice::findOrFail($params["invoiceid"]);
                            $invoice->status = "Unpaid";
                            $invoice->save();
                            break;
                        case "processed":
                            $dueDate = WHMCS\Carbon::parse($responseBody["executionDate"])->tz(date_default_timezone_get());
                            if ($dueDate <= WHMCS\Carbon::now()) {
                                $transactionId = $responseBody["reference"];
                                $clientCurrency = $params["clientdetails"]["currency"];
                                $amount = $responseBody["amount"];
                                $paymentCurrency = $params["currencyId"];
                                if ($paymentCurrency && $clientCurrency != $paymentCurrency) {
                                    $amount = convertCurrency($amount, $paymentCurrency, $clientCurrency);
                                }
                                addTransaction($params["userid"], 0, "Invoice Payment", $amount, 0, 0, "slimpay", $transactionId, $params["invoiceid"]);
                                WHMCS\TransientData::getInstance()->delete("slimPayPayment" . $params["invoiceid"]);
                                if (empty($params["gatewayid"])) {
                                    $clientModel = $params["clientdetails"]["model"];
                                    slimpay_get_and_save_mandate($responseBody["_links"]["https://api.slimpay.net/alps#get-mandate"]["href"], $apiToken, $clientModel);
                                }
                                redir("id=" . $params["invoiceid"] . "&paymentsuccess=true");
                            }
                            return "<div class=\"alert alert-success\">\n    " . $paymentCleared . "\n</div>";
                        default:
                            $invoice = WHMCS\Billing\Invoice::findOrFail($params["invoiceid"]);
                            $invoice->status = "Payment Pending";
                            $invoice->save();
                            return "<div class=\"alert alert-success\">\n    " . $paymentCleared . "\n</div>";
                    }
                }
            }
        } catch (Exception $e) {
            logTransaction("slimpay", $e->getMessage(), "Error on Payment Confirm", $params);
            return "An Unknown Error Occurred - Please contact Support";
        }
    }
    if (!App::getFromRequest("make_payment")) {
        $changeDetails = "";
        if ($params["clientdetails"]["gatewayid"]) {
            $langVar = Lang::trans("clientareaupdateyourdetails");
            $changeDetails = "<label class=\"checkbox-inline\">\n    <input name=\"new_details\" type=\"checkbox\" class=\"checkbox\" /> " . $langVar . "\n</label><br />";
        }
        return "<form method=\"POST\" name=\"paymentfrm\" action=\"" . $params["systemurl"] . "viewinvoice.php?id=" . $params["invoiceid"] . "\">\n    <input type=\"hidden\" name=\"make_payment\" value=\"true\">\n    " . $changeDetails . "\n    <button type=\"submit\" class=\"btn btn-success btn-sm\" id=\"btnPayNow\">\n        <i class=\"far fa-money-bill-alt\"></i>&nbsp; " . $params["langpaynow"] . "\n    </button>\n</form>";
    }
    $changeDetails = App::getFromRequest("new_details");
    if (!$params["clientdetails"]["gatewayid"] || $changeDetails) {
        try {
            $body = array("started" => true, "creditor" => array("reference" => $params["creditor_reference"]), "subscriber" => array("reference" => "Client" . $params["clientdetails"]["id"]), "paymentScheme" => $schemeDetails["scheme"], "items" => array(array("type" => "signMandate", "action" => "sign", "autoGenReference" => true, "mandate" => array("signatory" => array("givenName" => $params["clientdetails"]["firstname"], "familyName" => $params["clientdetails"]["lastname"], "email" => $params["clientdetails"]["email"], "telephone" => str_replace(".", "", $params["clientdetails"]["phonenumberformatted"]), "companyName" => $params["clientdetails"]["company"] ?: NULL, "organizationName" => $params["clientdetails"]["company"] ?: NULL, "billingAddress" => array("street1" => $params["clientdetails"]["address1"], "street2" => $params["clientdetails"]["address2"] ?: NULL, "city" => $params["clientdetails"]["city"], "postalCode" => $params["clientdetails"]["postcode"], "country" => $params["clientdetails"]["country"])))), array("type" => $schemeDetails["payment_type"], "action" => "create", $schemeDetails["payin_or_directDebit"] => array("amount" => $params["amount"], $schemeDetails["payment_reference_label"] => substr("whmcs-" . $params["invoicenum"], 0, 35), "label" => $params["description"], "currency" => $params["currency"], "scheme" => $schemeDetails["scheme"]))));
            $responseBody = slimpay_send_request($initialResponseBody["_links"]["https://api.slimpay.net/alps#create-orders"]["href"], $apiToken, $body, "POST");
            if (array_key_exists("code", $responseBody) && $responseBody["code"] != 200) {
                logTransaction("slimpay", array_merge($responseBody, $params), "Error on Payment", $params);
                return "An Error Occurred - Please contact Support";
            }
            $orderReference = $responseBody["reference"];
            WHMCS\TransientData::getInstance()->store("slimPayOrder" . $params["invoiceid"], $orderReference, 86400 * 14);
            WHMCS\Session::set("SlimPay", $orderReference);
            $redirectUrl = "";
            if (array_key_exists("https://api.slimpay.net/alps#extended-user-approval", $responseBody["_links"])) {
                $extendedUserApprovalUrl = str_replace("{?mode}", "?mode=iframepopin", $responseBody["_links"]["https://api.slimpay.net/alps#extended-user-approval"]["href"]);
                $responseBody = slimpay_send_request($extendedUserApprovalUrl, $apiToken);
            } else {
                if (array_key_exists("https://api.slimpay.net/alps#user-approval", $responseBody["_links"])) {
                    $redirectUrl = $responseBody["_links"]["https://api.slimpay.net/alps#user-approval"]["href"];
                } else {
                    throw new WHMCS\Exception\Module\NotServicable("Unable to Obtain User Approval Data");
                }
            }
            $mandateReference = $params["gatewayid"];
            if ($changeDetails && $mandateReference) {
                $clientModel = $params["clientdetails"]["model"];
                WHMCS\Database\Capsule::table("tblclientsfiles")->where("userid", "=", $clientModel->id)->where("title", "=", "SP - Direct Debit Mandate (" . $mandateReference . ")")->delete();
                invoiceSetPayMethodRemoteToken($params["invoiceid"], "");
            }
            if ($redirectUrl) {
                header("Location: " . $redirectUrl);
                WHMCS\Terminus::getInstance()->doExit();
            }
            return base64_decode($responseBody["content"]);
        } catch (Exception $e) {
            logTransaction("slimpay", $e->getMessage(), "Error on User Approval Obtain", $params);
            return "An Error Occurred - Please contact Support";
        }
    } else {
        try {
            slimpay_create_pay_in($params, $apiToken, $initialResponseBody["_links"]["https://api.slimpay.net/alps#create-payins"]["href"], $schemeDetails);
        } catch (Exception $e) {
            logTransaction("slimpay", $e->getMessage(), "Error on Payment", $params);
            return "An Unknown Error Occurred - Please contact Support";
        }
    }
    $paymentRequest = Lang::trans("invoicePaymentAutoWhenDue");
    return "<div class=\"alert alert-success\">\n    " . $paymentRequest . "\n</div>";
}
function slimpay_capture(array $params)
{
    $apiUrl = "https://api.slimpay.net/";
    if ($params["sandbox"]) {
        $apiUrl = "https://api.preprod.slimpay.com/";
    }
    try {
        $schemeDetails = slimpay_get_payment_scheme_details($params);
        $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
        if (!$apiToken) {
            throw new WHMCS\Exception\Module\NotServicable("No API Token Available");
        }
        $initialResponseBody = slimpay_send_request($apiUrl, $apiToken);
    } catch (Exception $e) {
        return array("status" => "error", "rawdata" => $e->getMessage());
    }
    if (!$params["gatewayid"]) {
        return array("status" => "failed", "rawdata" => "No Signed Mandate for this Client");
    }
    $orderReference = WHMCS\TransientData::getInstance()->retrieve("slimPayOrder" . $params["invoiceid"]);
    $paymentReference = WHMCS\TransientData::getInstance()->retrieve("slimPayPayment" . $params["invoiceid"]);
    if (!$orderReference && !$paymentReference) {
        try {
            $responseBody = slimpay_create_pay_in($params, $apiToken, $initialResponseBody["_links"]["https://api.slimpay.net/alps#create-payins"]["href"], $schemeDetails["scheme"]);
            return array("status" => "pending", "rawdata" => $responseBody);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    return array("status" => "error", "rawdata" => "There is already a payment pending for this invoice");
}
function slimpay_refund(array $params)
{
    try {
        $apiUrl = "https://api.slimpay.net/";
        if ($params["sandbox"]) {
            $apiUrl = "https://api.preprod.slimpay.com/";
        }
        $schemeDetails = slimpay_get_payment_scheme_details($params);
        $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
        if (!$apiToken) {
            throw new WHMCS\Exception\Module\NotServicable("No API Token Available");
        }
        $initialResponseBody = slimpay_send_request($apiUrl, $apiToken);
        $responseBody = slimpay_send_request($initialResponseBody["_links"]["https://api.slimpay.net/alps#create-payouts"]["href"], $apiToken, array("creditor" => array("reference" => $params["creditor_reference"]), "subscriber" => array("reference" => "Client" . $params["clientdetails"]["id"]), "amount" => $params["amount"], "currency" => $params["currency"], "reference" => substr("Refund" . $params["invoicenum"], 0, 35), "label" => substr("Refund " . $params["transid"], 0, 35), "scheme" => "SEPA.CREDIT_TRANSFER"));
        if (!in_array($responseBody["executionStatus"], array("rejected", "notprocessed"))) {
            return array("status" => "success", "rawdata" => $responseBody, "transid" => $responseBody["id"]);
        }
        return array("status" => "error", "rawdata" => $responseBody);
    } catch (Exception $e) {
        return array("status" => "error", "rawdata" => $e->getMessage());
    }
}
function slimpay_status(array $params)
{
    $apiUrl = "https://api.slimpay.net/";
    if ($params["sandbox"]) {
        $apiUrl = "https://api.preprod.slimpay.com/";
    }
    try {
        $schemeDetails = slimpay_get_payment_scheme_details($params);
        $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
        if (!$apiToken) {
            throw new WHMCS\Exception\Module\NotServicable("No API Token Available");
        }
        $initialResponseBody = slimpay_send_request($apiUrl, $apiToken);
    } catch (Exception $e) {
        return array("status" => "error", "rawdata" => $e->getMessage());
    }
    $orderReference = WHMCS\TransientData::getInstance()->retrieve("slimPayOrder" . $params["invoiceid"]);
    $paymentReference = WHMCS\TransientData::getInstance()->retrieve("slimPayPayment" . $params["invoiceid"]);
    if ($orderReference) {
        try {
            $uri = str_replace("{?creditorReference,reference}", "?creditorReference=" . $params["creditor_reference"] . "&reference=" . $orderReference, $initialResponseBody["_links"]["https://api.slimpay.net/alps#get-orders"]["href"]);
            $responseBody = slimpay_send_request($uri, $apiToken);
            $paymentStatus = $responseBody["state"];
            if (strpos($paymentStatus, "closed.aborted") === 0) {
                WHMCS\TransientData::getInstance()->delete("slimPayOrder" . $params["invoiceid"]);
                return array("status" => $paymentStatus, "rawdata" => $responseBody);
            }
            if (strpos($paymentStatus, "closed.completed") === 0) {
                $responseBody = slimpay_send_request($responseBody["_links"]["https://api.slimpay.net/alps#get-payment"]["href"], $apiToken);
                switch ($responseBody["executionStatus"]) {
                    case "rejected":
                    case "notprocessed":
                        WHMCS\TransientData::getInstance()->delete("slimPayOrder" . $params["invoiceid"]);
                        return array("status" => "declined", "rawData" => $responseBody);
                    case "processed":
                        $transactionId = $responseBody["reference"];
                        $rawData = $responseBody;
                        WHMCS\TransientData::getInstance()->delete("slimPayOrder" . $params["invoiceid"]);
                        $clientModel = $params["clientdetails"]["model"];
                        if (empty($params["gatewayid"])) {
                            slimpay_get_and_save_mandate($responseBody["_links"]["https://api.slimpay.net/alps#get-mandate"]["href"], $apiToken, $clientModel);
                        }
                        return array("status" => "success", "transid" => $transactionId, "rawdata" => $rawData);
                    default:
                        return array("status" => "pending", "rawData" => $responseBody);
                }
            }
        } catch (Exception $e) {
            return array("status" => "Error", "rawdata" => $e->getMessage());
        }
    }
    if ($paymentReference) {
        try {
            $uri = str_replace("{id}", $paymentReference, $initialResponseBody["_links"]["https://api.slimpay.net/alps#search-payment-by-id"]["href"]);
            $responseBody = slimpay_send_request($uri, $apiToken);
            switch ($responseBody["executionStatus"]) {
                case "rejected":
                case "notprocessed":
                    WHMCS\TransientData::getInstance()->delete("slimPayPayment" . $params["invoiceid"]);
                    return array("status" => "declined", "rawData" => $responseBody);
                case "processed":
                    $dueDate = WHMCS\Carbon::parse($responseBody["executionDate"])->tz(date_default_timezone_get());
                    if ($dueDate <= WHMCS\Carbon::now()) {
                        $transactionId = $responseBody["reference"];
                        WHMCS\TransientData::getInstance()->delete("slimPayPayment" . $params["invoiceid"]);
                        slimpay_get_and_save_mandate($responseBody["_links"]["https://api.slimpay.net/alps#get-mandate"]["href"], $apiToken, $params["clientdetails"]["model"]);
                        return array("status" => "success", "transid" => $transactionId, "rawdata" => $responseBody);
                    }
                    return array("status" => "pending", "rawdata" => $responseBody);
                default:
                    return array("status" => "pending", "rawdata" => $responseBody);
            }
        } catch (Exception $e) {
            return array("status" => "Error", "rawdata" => $e->getMessage());
        }
    }
    return array("status" => "Error", "rawdata" => "No current payment pending");
}
function slimpay_api_authorisation($apiUrl, $appId, $appSecret)
{
    try {
        $body = GuzzleHttp\Stream\Stream::factory(http_build_query(array("grant_type" => "client_credentials", "scope" => "api")));
        $request = new GuzzleHttp\Message\Request("POST", $apiUrl . "oauth/token", array("Accept" => "application/json", "Authorization" => "Basic " . base64_encode($appId . ":" . $appSecret), "Content-Type" => "application/x-www-form-urlencoded"), $body);
        $client = new GuzzleHttp\Client();
        $response = $client->send($request);
        return $response->json()["access_token"];
    } catch (Exception $e) {
        throw $e;
    }
}
function slimpay_standard_headers($token)
{
    return array("Accept" => "application/hal+json; profile=\"https://api.slimpay.net/alps/v1\"", "Authorization" => "Bearer " . $token, "Content-Type" => "application/json", "allow_redirects" => true);
}
function slimpay_get_and_save_mandate($uri, $apiToken, $clientModel)
{
    try {
        $responseBody = slimpay_send_request($uri, $apiToken);
        $mandateReference = $responseBody["reference"];
        if ($clientModel instanceof WHMCS\User\Client\Contact) {
            $clientModel = $clientModel->client;
        }
        $payMethod = $clientModel->payMethods()->where("gateway_name", "slimpay")->first();
        if (!$payMethod) {
            $payMethod = WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($clientModel, $clientModel, "Default Bank Account");
        }
        $payMethod->payment->setRemoteToken($mandateReference);
        $payMethod->payment->save();
        $signedMandate = WHMCS\Database\Capsule::table("tblclientsfiles")->where("userid", "=", $clientModel->id)->where("title", "=", "SP - Direct Debit Mandate (" . $mandateReference . ")")->first();
        if (!$signedMandate) {
            $responseBody = slimpay_send_request($responseBody["_links"]["https://api.slimpay.net/alps#get-document"]["href"], $apiToken);
            $storage = Storage::clientFiles();
            do {
                $fileName = "file" . mt_rand(100000, 999999) . "_" . $mandateReference . ".pdf";
            } while ($storage->has($fileName));
            $storage->write($fileName, base64_decode($responseBody["content"]));
            WHMCS\Database\Capsule::table("tblclientsfiles")->insert(array("userid" => $clientModel->id, "title" => "SP - Direct Debit Mandate (" . $mandateReference . ")", "filename" => $fileName, "adminonly" => 0, "dateadded" => "now()"));
        }
    } catch (Exception $e) {
        logTransaction("slimpay", error_get_last(), "Error on Mandate File Save");
    }
}
function slimpay_create_pay_in(array $params, $apiToken, $uri, $scheme)
{
    $body = array("creditor" => array("reference" => $params["creditor_reference"]), "subscriber" => array("reference" => "Client" . $params["clientdetails"]["id"]), "amount" => $params["amount"], "currency" => $params["currency"], "reference" => substr("whmcs-" . $params["invoicenum"], 0, 35), "label" => $params["description"], "scheme" => $scheme, "executionDate" => WHMCS\Carbon::parse($params["dueDate"])->format("Y-m-d\\TH:i:s.\\0\\0\\0O"));
    $responseBody = slimpay_send_request($uri, $apiToken, $body, "POST");
    $paymentId = $responseBody["id"];
    WHMCS\TransientData::getInstance()->store("slimPayPayment" . $params["invoiceid"], $paymentId, 86400 * 14);
    return $responseBody;
}
function slimpay_poll(array $params)
{
    $apiUrl = "https://api.slimpay.net/";
    if ($params["sandbox"]) {
        $apiUrl = "https://api.preprod.slimpay.com/";
    }
    try {
        $schemeDetails = slimpay_get_payment_scheme_details($params);
        $apiToken = slimpay_api_authorisation($apiUrl, $schemeDetails["app_id"], $schemeDetails["app_secret"]);
        if (!$apiToken) {
            throw new WHMCS\Exception\Module\NotServicable("No API Token Available");
        }
        $initialResponseBody = slimpay_send_request($apiUrl, $apiToken);
    } catch (Exception $e) {
        throw new WHMCS\Exception($e->getMessage());
    }
    try {
        $uri = str_replace("{?creditorReference,entityReference,subscriberReference,scheme,currency,executionStatus,dateCreatedBefore,dateCreatedAfter,page,size}", "?creditorReference=" . $params["creditor_reference"] . "&executionStatus=toprocess&size=1000&scheme=SEPA.DIRECT_DEBIT.CORE", $initialResponseBody["_links"]["https://api.slimpay.net/alps#search-payment-issues"]["href"]);
        $paymentIssues = slimpay_send_request($uri, $apiToken);
        if (0 < $paymentIssues["page"]["totalelements"]) {
            foreach ($paymentIssues["_embedded"]["paymentIssues"] as $paymentIssue) {
                logTransaction("slimpay", $paymentIssue, "Payment Reversed", $params);
                slimpay_handle_payment_issue($paymentIssue, $apiToken);
            }
        }
        $uri = str_replace("{?creditorReference,entityReference,subscriberReference,scheme,currency,executionStatus,dateCreatedBefore,dateCreatedAfter,page,size}", "?creditorReference=" . $params["creditor_reference"] . "&executionStatus=toprocess&size=1000&scheme=BACS.DIRECT_DEBIT", $initialResponseBody["_links"]["https://api.slimpay.net/alps#search-payment-issues"]["href"]);
        $paymentIssues = slimpay_send_request($uri, $apiToken);
        if (0 < $paymentIssues["page"]["totalelements"]) {
            foreach ($paymentIssues["_embedded"]["paymentIssues"] as $paymentIssue) {
                logTransaction("slimpay", $paymentIssue, "Payment Reversed", $params);
                slimpay_handle_payment_issue($paymentIssue, $apiToken);
            }
        }
    } catch (Exception $e) {
        throw new WHMCS\Exception($e->getMessage());
    }
}
function slimpay_handle_payment_issue(array $paymentIssue, $apiToken)
{
    $links = $paymentIssue["_links"];
    $paymentUri = $links["https://api.slimpay.net/alps#get-payment"]["href"];
    $acknowledgePaymentIssueUri = $links["https://api.slimpay.net/alps#ack-payment-issue"]["href"];
    $issueId = $paymentIssue["id"];
    $payment = slimpay_send_request($paymentUri, $apiToken);
    $transactionId = $payment["id"];
    $invoiceId = $payment["reference"];
    try {
        paymentReversed($issueId, $transactionId, $invoiceId, "slimpay");
        logTransaction("slimpay", $transactionId, "Payment Reversed");
    } catch (Exception $e) {
        logTransaction("slimpay", $transactionId, "Payment Reversal Could Not Be Completed: " . $e->getMessage());
    }
    slimpay_send_request($acknowledgePaymentIssueUri, $apiToken);
}
function slimpay_get_payment_scheme_details(array $params)
{
    switch ($params["currency"]) {
        case "GBP":
            return array("scheme" => "BACS.DIRECT_DEBIT", "app_id" => $params["bacs_app_id"], "app_secret" => $params["bacs_app_secret"], "payment_type" => "directDebit", "payment_reference_label" => "paymentReference", "payin_or_directDebit" => "directDebit");
        case "EUR":
            return array("scheme" => "SEPA.DIRECT_DEBIT.CORE", "app_id" => $params["app_id"], "app_secret" => $params["app_secret"], "payment_type" => "payment", "payment_reference_label" => "reference", "payin_or_directDebit" => "payin");
        default:
            throw new WHMCS\Exception("Invalid Currency for Payment");
    }
}
function slimpay_send_request($uri, $apiToken, array $body = NULL, $getOrPost = "GET")
{
    if (!in_array($getOrPost, array("GET", "POST"))) {
        $getOrPost = "GET";
    }
    $client = new GuzzleHttp\Client();
    $request = new GuzzleHttp\Message\Request($getOrPost, $uri, slimpay_standard_headers($apiToken), GuzzleHttp\Stream\Stream::factory(json_encode($body, JSON_UNESCAPED_UNICODE)));
    $response = $client->send($request);
    $response = $response->json();
    if (!is_array($response) || !array_key_exists("_links", $response)) {
        if (is_array($response) && array_key_exists("message", $response)) {
            $response = $response["message"];
        } else {
            if (is_array($response)) {
                $response = implode($response);
            }
        }
        throw new WHMCS\Exception\Module\NotServicable($response);
    }
    return $response;
}

?>