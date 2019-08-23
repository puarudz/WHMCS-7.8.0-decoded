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
function gocardless_MetaData()
{
    return array("apiOnboarding" => true, "apiOnboardingRedirectUrl" => "https://api1.whmcs.com/gocardless/auth/initiate", "apiOnboardingCallbackPath" => "modules/gateways/callback/gocardless.php", "failedEmail" => "Direct Debit Payment Failed", "successEmail" => "Direct Debit Payment Confirmation", "pendingEmail" => "Direct Debit Payment Pending", "noCurrencyConversion" => true);
}
function gocardless_config(array $params)
{
    $return = array("FriendlyName" => array("Type" => "System", "Value" => "GoCardless"), "verificationStatus" => NULL, "accessToken" => array("FriendlyName" => "OAuth Access Token", "Type" => "password", "Description" => "To modify these values, click the Configure button below.", "ReadOnly" => true), "callbackToken" => array("FriendlyName" => "Callback Token", "Type" => "password", "ReadOnly" => true), "reconnectAccount" => array("FriendlyName" => "", "Type" => "button", "Description" => sprintf("<a href=\"%s\" class=\"btn btn-sm btn-default\">%s</a>", "configgateways.php?action=onboarding&gateway=gocardless", "Configure GoCardless Account Connection")));
    if (array_key_exists("accessToken", $params) && $params["accessToken"]) {
        try {
            $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
            $response = json_decode($client->get("creditors"));
            $creditor = isset($response->creditors[0]) ? $response->creditors[0] : "";
            if ($creditor && $creditor->verification_status == "action_required") {
                $description = "<strong>Verification Status: Action Required</strong><br>" . "Please login to your GoCardless account to complete account verification. " . "<a href=\"https://manage.gocardless.com/sign-in\" target=\"_blank\" class=\"alert-link\">Login</a>";
                $return["verificationStatus"] = array("FriendlyName" => "", "Type" => "none", "Description" => "<div class=\"alert alert-warning\" style=\"margin:0;\">" . $description . "</div>");
            } else {
                if ($creditor && $creditor->verification_status == "in_review") {
                    $description = "<strong>Verification Status: In Review</strong><br>" . "Your account is awaiting review by GoCardless. This message will update once account verification has been performed.";
                    $return["verificationStatus"] = array("FriendlyName" => "", "Type" => "none", "Description" => "<div class=\"alert alert-info\" style=\"margin:0;\">" . $description . "</div>");
                } else {
                    if ($creditor && $creditor->verification_status == "successful") {
                        $description = "<strong>Verification Status: Successful</strong><br>" . "Verification has been completed by GoCardless and your account is active and ready for use.";
                        $return["verificationStatus"] = array("FriendlyName" => "", "Type" => "none", "Description" => "<div class=\"alert alert-success\" style=\"margin:0;\">" . $description . "</div>");
                    }
                }
            }
        } catch (Exception $e) {
        }
    }
    if (is_null($return["verificationStatus"])) {
        unset($return["verificationStatus"]);
    }
    $currencies = WHMCS\Billing\Currency::all()->pluck("code");
    $usageNotes = array();
    foreach ($currencies as $currencyCode) {
        if (!in_array($currencyCode, WHMCS\Module\Gateway\GoCardless\GoCardless::SUPPORTED_CURRENCIES)) {
            $usageNotes[] = "<strong>Unsupported Currencies.</strong> You have one or more " . "currencies configured that are not supported by GoCardless. Invoices using " . "currencies GoCardless does not support will be unable to be paid using GoCardless. " . "<a href=\"https://docs.whmcs.com/GoCardless#Supported_Currencies\" target=\"_blank\">" . "Learn more</a>";
            break;
        }
    }
    $systemUrl = App::getSystemURL();
    if (substr($systemUrl, 0, 5) != "https") {
        $usageNotes[] = "<strong>GoCardless requires an HTTPS secured connection for the WHMCS installation.</strong> " . "Your current WHMCS System URL setting does not begin with https and will be rejected. " . "Please add an SSL Certificate to your WHMCS domain to use GoCardless. " . "<a href=\"https://docs.whmcs.com/GoCardless#SSL_Requirement\" target=\"_blank\">" . "Learn more</a>";
    }
    if ($usageNotes) {
        $return["UsageNotes"] = array("Type" => "System", "Value" => implode("<br>", $usageNotes));
    }
    return $return;
}
function gocardless_link(array $params)
{
    if (!in_array($params["currency"], WHMCS\Module\Gateway\GoCardless\GoCardless::SUPPORTED_CURRENCIES)) {
        return "<div class=\"alert alert-danger\">" . "Payment Method Unavailable - Please select an alternate payment method" . "</div>";
    }
    $gatewayId = $params["clientdetails"]["gatewayid"];
    if ($gatewayId && substr($gatewayId, 0, 2) == "MD") {
        $history = WHMCS\Billing\Payment\Transaction\History::where("gateway", $params["paymentmethod"])->where("invoice_id", $params["invoiceid"])->where("transaction_id", "!=", "N/A")->orderBy("id", "desc")->first();
        if ($history && !$history->completed) {
            $paymentId = $history->transactionId;
            $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
            try {
                $response = json_decode($client->get("payments/" . $paymentId), true);
                $chargeDate = WHMCS\Carbon::parse($response["payments"]["charge_date"]);
                return "<div class=\"alert alert-info\">\n    A payment has been submitted that will clear your bank on " . $chargeDate->toClientDateFormat() . ".\n    <br>A payment confirmation email will be sent when the payment clears.\n</div>";
            } catch (Exception $e) {
            }
        }
        return "<div class=\"alert alert-info\">\n    There is an automatic payment setup for this account. No manual payment is required.\n</div>";
    }
    if (App::isInRequest("payment") && App::getFromRequest("payment") == 1 || App::isInRequest("a") && App::getFromRequest("a") == "checkout") {
        $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
        $postParams = array();
        $customer = array("address_line1" => $params["clientdetails"]["address1"], "city" => $params["clientdetails"]["city"], "country_code" => $params["clientdetails"]["country"], "email" => $params["clientdetails"]["email"], "family_name" => $params["clientdetails"]["lastname"], "given_name" => $params["clientdetails"]["firstname"], "postal_code" => $params["clientdetails"]["postcode"], "region" => $params["clientdetails"]["state"]);
        if ($params["clientdetails"]["country"] == "NZ") {
            $customer["phone_number"] = $params["clientdetails"]["phonenumber"];
        }
        $successUrl = $params["systemurl"] . "modules/gateways/callback/gocardless.php";
        $postParams["redirect_flows"] = array("session_token" => "SESSION_" . $params["clientdetails"]["id"] . "_" . $params["invoiceid"], "success_redirect_url" => $successUrl, "prefilled_customer" => $customer);
        try {
            $response = json_decode($client->post("redirect_flows", array("json" => $postParams)));
        } catch (Exception $e) {
            return "<div class=\"alert alert-danger\">" . $e->getMessage() . "</div>";
        }
        $redirectId = $response->redirect_flows->id;
        WHMCS\TransientData::getInstance()->store($params["clientdetails"]["id"] . "_" . $params["invoiceid"], $redirectId, 60 * 60);
        $redirectUrl = $response->redirect_flows->redirect_url;
        App::redirect($redirectUrl);
    }
    $systemUrl = App::getSystemURL();
    $buttonTitle = Lang::trans("setupMandate");
    return "<form method=\"post\" action=\"" . $systemUrl . "viewinvoice.php\">\n    <input type=\"hidden\" name=\"id\" value=\"" . $params["invoiceid"] . "\">\n    <input type=\"hidden\" name=\"payment\" value=\"1\">\n    <button type=\"submit\" class=\"btn btn-primary\">" . $buttonTitle . "</button>\n</form>";
}
function gocardless_nolocalcc()
{
}
function gocardless_no_cc()
{
}
function gocardless_capture(array $params)
{
    if (!in_array($params["currency"], WHMCS\Module\Gateway\GoCardless\GoCardless::SUPPORTED_CURRENCIES)) {
        return array("status" => "error", "rawdata" => "Invalid Currency for Payment");
    }
    $mandate = $params["gatewayid"];
    if (!$mandate || substr($mandate, 0, 2) != "MD") {
        return array("status" => "error", "rawdata" => "No mandate setup");
    }
    $paymentReference = WHMCS\Billing\Payment\Transaction\History::where("gateway", "gocardless")->where("invoice_id", $params["invoiceid"])->where("transaction_id", "!=", "N/A")->orderBy("id", "desc")->first();
    if ($paymentReference && $paymentReference->completed) {
        $paymentReference = NULL;
    } else {
        if ($paymentReference && in_array($paymentReference->remoteStatus, WHMCS\Module\Gateway\GoCardless\Resources\Payments::CANCELLED_STATES)) {
            $paymentReference = NULL;
        }
    }
    if (!$paymentReference) {
        try {
            $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
            $response = json_decode($client->get("mandates/" . $mandate));
            $nextChargeDate = $response->mandates->next_possible_charge_date;
            $nextChargeDateCarbon = WHMCS\Carbon::createFromFormat("Y-m-d", $nextChargeDate);
            $nextDueDate = explode(" ", $params["dueDate"]);
            $nextDueDate = WHMCS\Carbon::createFromFormat("Y-m-d", $nextDueDate[0]);
            if ($nextDueDate < $nextChargeDateCarbon) {
                $nextDueDate = $nextChargeDateCarbon;
            }
            $details = (string) $params["amount"] . "|" . $params["currencyId"];
            if (array_key_exists("basecurrencyamount", $params)) {
                $details = (string) $params["basecurrencyamount"] . "|" . $params["baseCurrencyId"];
            }
            $postParams = array("amount" => str_replace(".", "", $params["amount"]), "currency" => $params["currency"], "charge_date" => $nextDueDate->format("Y-m-d"), "description" => $params["description"], "metadata" => array("client_id" => (string) (string) $params["clientdetails"]["userid"], "invoice_id" => (string) (string) $params["invoiceid"], "invoice_details" => $details), "links" => array("mandate" => $mandate));
            $response = json_decode($client->post("payments", array("json" => array("payments" => $postParams))));
            $attempts = WHMCS\Billing\Payment\Transaction\History::firstOrNew(array("invoice_id" => $params["invoiceid"], "gateway" => $params["paymentmethod"], "transaction_id" => $response->payments->id));
            $attempts->remoteStatus = $response->payments->status;
            $attempts->description = $params["description"];
            $attempts->completed = false;
            $attempts->additionalInformation = json_decode(json_encode($response->payments), true);
            $attempts->save();
            return array("status" => "pending", "rawdata" => $response, "transid" => $response->payments->id, "history_id" => $attempts->id);
        } catch (Exception $e) {
            return array("status" => "error", "rawdata" => $e->getMessage(), "declineReason" => $e->getMessage());
        }
    }
    return array("status" => "error", "rawdata" => "There is already a payment pending for this invoice", "declineReason" => "There is already a payment pending for this invoice");
}
function gocardless_adminstatusmsg(array $params)
{
    if (!in_array($params["currency"], WHMCS\Module\Gateway\GoCardless\GoCardless::SUPPORTED_CURRENCIES)) {
        return array("alert" => true, "type" => "danger", "alertText" => "<strong>Invalid Currency for Payment</strong><br>" . "GoCardless require using one of the following currencies for payment:" . implode(WHMCS\Module\Gateway\GoCardless\GoCardless::SUPPORTED_CURRENCIES, ", "));
    }
    $mandate = $params["clientdetails"]["gatewayid"];
    if ($mandate && substr($mandate, 0, 2) == "MD") {
        $paymentReference = WHMCS\Billing\Payment\Transaction\History::where("gateway", "gocardless")->where("invoice_id", $params["invoiceid"])->orderBy("id", "desc")->first();
        if ($paymentReference && ($paymentReference->completed || substr($paymentReference->transactionId, 0, 2) != "PM")) {
            $paymentReference = NULL;
        }
        $checked = WHMCS\TransientData::getInstance()->retrieve("goCardlessInvoice" . $params["invoiceid"]);
        if ($paymentReference && !$checked) {
            $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
            try {
                $response = json_decode($client->get("payments/" . $paymentReference->transactionId));
                if (in_array($response->payments->status, WHMCS\Module\Gateway\GoCardless\Resources\Payments::CANCELLED_STATES)) {
                    $paymentReference->remoteStatus = $response->payments->status;
                    $paymentReference->description = $response->payments->description;
                    $paymentReference->completed = true;
                    $paymentReference->save();
                    return array();
                }
                $checked = "A payment is being processed that will clear on " . fromMySQLDate($response->payments->charge_date);
                WHMCS\TransientData::getInstance()->store("goCardlessInvoice" . $params["invoiceid"], $checked, 43200);
            } catch (Exception $e) {
                return array("alert" => true, "type" => "danger", "alertText" => "<strong>Error Retrieving Payment</strong><br>" . $e->getMessage());
            }
        }
        $alertText = "";
        if ($checked) {
            if ($paymentReference && $paymentReference->remoteStatus == "pending_submission") {
                $cancelLink = App::getPhpSelf() . "?action=edit&id=" . $params["invoiceid"] . "&cancelpayment=" . $paymentReference->id . generate_token("link");
                $buttonTitle = AdminLang::trans("invoices.cancelPayment");
                $checked = "<div class=\"row\">\n    <div class=\"col-md-8\">\n        " . $checked . "\n    </div>\n    <div class=\"col-md-4 text-right\">\n        <button title=\"button\" onclick=\"window.location='" . $cancelLink . "'\" class=\"btn btn-default\">\n            " . $buttonTitle . "\n        </button>\n    </div>\n</div>";
            }
            $alertText = "<strong>Payment Pending</strong><br>" . $checked . "<br><br>";
        }
        $mandateInformation = gocardless_remote_status($params);
        if (is_array($mandateInformation) && array_key_exists("Mandate Information", $mandateInformation)) {
            $mandate = "<br>" . $mandateInformation["Mandate Information"];
        }
        return array("alert" => true, "type" => "info", "alertText" => $alertText . "<strong>Mandate Setup</strong><br>" . "There is a mandate setup for automatic payments with GoCardless: " . $mandate);
    }
    return array();
}
function gocardless_remote_status(array $params)
{
    $mandate = $params["clientdetails"]["gatewayid"];
    if ($mandate && substr($mandate, 0, 2) == "MD") {
        $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
        try {
            $response = json_decode($client->get("mandates/" . $mandate));
            $mandateStatuses = WHMCS\Module\Gateway\GoCardless\Resources\Mandates::STATUSES;
            $setupDate = WHMCS\Carbon::parse($response->mandates->created_at);
            $nextDate = WHMCS\Carbon::parse($response->mandates->next_possible_charge_date);
            return array("Mandate Information" => "Status: " . $mandateStatuses[$response->mandates->status] . "<br />\nSetup Date: " . $setupDate->toAdminDateTimeFormat() . "<br />\nReference: " . $response->mandates->reference . "<br />\nNext Possible Charge Date: " . $nextDate->toAdminDateFormat() . "<br />\nScheme: " . $response->mandates->scheme);
        } catch (Exception $e) {
            return array("Error" => $e->getMessage());
        }
    }
    return array();
}
function gocardless_storeremote(array $params)
{
    if ($params["action"] == "delete") {
        $mandate = $params["gatewayid"];
        if (!$mandate || substr($mandate, 0, 2) != "MD") {
            return array("status" => "error", "rawdata" => "No mandate setup");
        }
        try {
            $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
            $response = json_decode($client->get("mandates/" . $mandate), true);
            $metadata = $response["mandates"]["metadata"];
            $client->post("mandates/" . $mandate . "/actions/cancel", array("json" => array("data" => array("metadata" => $metadata))));
        } catch (Exception $e) {
            return array("status" => "error", "rawdata" => $e->getMessage());
        }
    }
    return array("status" => "success");
}
function gocardless_cancel_payment(array $params)
{
    $paymentId = $params["cancelTransactionId"];
    if ($paymentId) {
        $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
        try {
            $response = json_decode($client->get("payments/" . $paymentId), true);
            $payment = $response["payments"];
            if ($payment["status"] != "pending_submission") {
                return array("msg" => "<strong>Invalid Request</strong><br>" . "The payment " . $paymentId . " cannot be cancelled", "type" => "warning", "status" => "Cancel Payment Failed", "rawdata" => $response);
            }
            $response = json_decode($client->post("payments/" . $paymentId . "/actions/cancel", array("json" => array("data" => array("metadata" => array("reason" => "Admin Requested", "admin_id" => (string) WHMCS\Session::get("adminid"), "invoice_id" => (string) $params["invoiceid"]))))), true);
            $history = $params["history"];
            $history->remoteStatus = $response["payments"]["status"];
            $history->description = $response["payments"]["description"];
            $history->additionalInformation = $response;
            $history->save();
            $history->invoice->status = "Unpaid";
            $history->invoice->save();
            WHMCS\TransientData::getInstance()->delete("goCardlessInvoice" . $params["invoiceid"]);
            return array("msg" => "Payment " . $paymentId . " has been cancelled", "type" => "success", "status" => "Cancel Payment Success", "rawdata" => $response);
        } catch (Exception $e) {
            return array("msg" => $e->getMessage(), "type" => "danger", "status" => "Cancel Payment Failed", "rawdata" => $e->getMessage());
        }
    }
    return array();
}
function gocardless_admin_area_actions(array $params)
{
    return array(array("label" => "Manage Cancelled Mandates", "actionName" => "list_cancelled_mandates", "modalSize" => "modal-lg", "disabled" => empty($params["accessToken"])), array("label" => "Import Existing Mandates", "actionName" => "list_mandates_for_import", "modalSize" => "modal-lg", "disabled" => empty($params["accessToken"])));
}
function gocardless_list_cancelled_mandates(array $params)
{
    try {
        $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
        $action = App::getFromRequest("action");
        $message = "";
        if ($action && $action == "reinstate") {
            check_token("WHMCS.admin.default");
            $mandateId = App::getFromRequest("mandate_id");
            $client->post("mandates/" . $mandateId . "/actions/reinstate", array());
            $message = "The mandate " . $mandateId . " has been submitted to be reinstated.";
        }
        $response = json_decode($client->get("mandates", array("query" => array("limit" => 500, "status" => "cancelled"))), true);
        $view = moduleView("gocardless", "mandates.list", array("mandates" => $response["mandates"], "message" => $message, "routePath" => routePath("admin-setup-payments-gateways-action", "gocardless", "list_cancelled_mandates"), "activeStatuses" => array()));
        return array("status" => "success", "body" => $view);
    } catch (Exception $e) {
        return array("status" => "error", "body" => WHMCS\View\Helper::alert($e->getMessage(), "danger"));
    }
}
function gocardless_list_mandates_for_import(array $params)
{
    $message = "";
    try {
        $client = WHMCS\Module\Gateway\GoCardless\Client::factory($params["accessToken"]);
        $action = App::getFromRequest("action");
        if ($action && $action == "import") {
            return gocardless_mandate_import_start($client, App::getFromRequest("customer"));
        }
        if ($action && $action == "do_import") {
            $mandateId = App::getFromRequest("mandate_id");
            $clientId = App::getFromRequest("client_id");
            try {
                $whmcsClient = WHMCS\User\Client::findOrFail($clientId);
                $client->put("mandates/" . $mandateId, array("json" => array("mandates" => array("metadata" => array("client_id" => (string) (string) $clientId)))));
                $mandateData = json_decode($client->get("mandates/" . $mandateId));
                $bankAccountId = $mandateData->mandates->links->customer_bank_account;
                $bankAccountData = json_decode($client->get("/customer_bank_accounts/" . $bankAccountId));
                $accountNumberLastTwo = str_pad($bankAccountData->customer_bank_accounts->account_number_ending, 8, "x", STR_PAD_LEFT);
                $accountBankName = $bankAccountData->customer_bank_accounts->bank_name;
                $accountHolderName = $bankAccountData->customer_bank_accounts->account_holder_name;
                $payMethod = WHMCS\Payment\PayMethod\Model::where("userid", $whmcsClient->id)->where("gateway_name", "gocardless")->where("payment_type", WHMCS\Payment\PayMethod\Model::TYPE_REMOTE_BANK_ACCOUNT)->first();
                if (!$payMethod) {
                    $payMethod = WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($whmcsClient, $whmcsClient->defaultBillingContact);
                    $payMethod->setGateway($params["gatewayInterface"]);
                    $payMethod->save();
                }
                $newPayment = $payMethod->payment;
                $newPayment->setAccountNumber($accountNumberLastTwo)->setAccountHolderName($accountHolderName)->setName($accountBankName)->setRemoteToken($mandateId)->save();
                $message = "Mandate " . $mandateId . " Successfully Associated with Client ID: " . $clientId;
            } catch (Exception $e) {
                $message = "Error Associating Mandate " . $mandateId . " with Client ID: " . $clientId . ": " . $e->getMessage();
            }
        }
        $response = json_decode($client->get("mandates", array("query" => array("limit" => 500, "status" => "active,submitted,pending_submission"))), true);
        $mandates = collect($response["mandates"]);
        $payMethods = WHMCS\Payment\PayMethod\Model::where("gateway_name", "gocardless")->where("payment_type", WHMCS\Payment\PayMethod\Model::TYPE_REMOTE_BANK_ACCOUNT)->get();
        $mandateList = array();
        foreach ($payMethods as $payMethod) {
            if ($payMethod->payment->bank_data == "") {
                $payMethod->delete();
                continue;
            }
            $mandateList[] = $payMethod->payment->getRemoteToken();
        }
        $mandates = $mandates->filter(function ($value, $key) use($mandateList) {
            $notInArray = !in_array($value["id"], $mandateList);
            $startsWithMd = substr($value["id"], 0, 2) == "MD";
            return $notInArray && $startsWithMd;
        });
        $view = moduleView("gocardless", "mandates.list", array("mandates" => $mandates, "message" => $message, "routePath" => routePath("admin-setup-payments-gateways-action", "gocardless", "list_mandates_for_import"), "activeStatuses" => array("active", "submitted", "pending_submission")));
        return array("body" => $view);
    } catch (Exception $e) {
        return array("errorMsg" => $e->getMessage(), "errorMsgTitle" => Lang::trans("error"));
    }
}
function gocardless_mandate_import_start(WHMCS\Module\Gateway\GoCardless\Api\Client $client, $customerId)
{
    $recommendedClientId = (int) App::getFromRequest("client_id");
    $customer = json_decode($client->get("customers/" . $customerId), true);
    $customer = $customer["customers"];
    $whmcsClient = NULL;
    if (!$recommendedClientId) {
        $whmcsClient = WHMCS\User\Client::where("email", $customer["email"])->first();
        if ($client) {
            $recommendedClientId = $client->id;
        }
    }
    $options = array();
    if ($recommendedClientId) {
        if (!$whmcsClient) {
            $whmcsClient = WHMCS\User\Client::find($recommendedClientId);
        }
        $options[$whmcsClient->id] = $whmcsClient->fullName;
    }
    $dropdown = new WHMCS\Admin\ApplicationSupport\View\Html\Helper\ClientSearchDropdown("client_id", $recommendedClientId, $options, AdminLang::trans("global.typeToSearchClients"), "id");
    $view = moduleView("gocardless", "mandates.import", array("clientId" => $recommendedClientId, "routePath" => routePath("admin-setup-payments-gateways-action", "gocardless", "list_mandates_for_import"), "dropdown" => $dropdown, "customer" => $customer, "mandateId" => App::getFromRequest("mandate_id")));
    return array("body" => $view, "submitlabel" => AdminLang::trans("global.import"), "submitId" => "btnImport");
}

?>