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
define("EWAY_TOKENS_PARTNER_ID", "311f3706123f4a93bc92841cd3b9e970");
function ewaytokens_MetaData()
{
    return array("DisplayName" => "eWAY Rapid 3.1 Payments", "APIVersion" => "1.1");
}
function ewaytokens_config()
{
    $configArray = array();
    $configArray["FriendlyName"] = array("Type" => "System", "Value" => "eWAY Rapid 3.1 Payments");
    $configArray["apiKey"] = array("FriendlyName" => "API Key", "Type" => "text", "Size" => 20);
    $configArray["apiPass"] = array("FriendlyName" => "API Password", "Type" => "password", "Size" => 20);
    $configArray["testmode"] = array("FriendlyName" => "Test Mode", "Type" => "yesno");
    return $configArray;
}
function ewaytokens_nolocalcc()
{
}
function ewaytokens_remoteinput(array $params)
{
    WHMCS\Session::delete("ewaytokensConfirm");
    $sandbox = "";
    if ($params["testmode"]) {
        $sandbox = ".sandbox";
    }
    $url = "https://api" . $sandbox . ".ewaypayments.com/AccessCodesShared";
    $customer = array();
    $customer["Reference"] = $params["clientdetails"]["id"];
    $customer["Title"] = "";
    $customer["FirstName"] = $params["clientdetails"]["firstname"];
    $customer["LastName"] = $params["clientdetails"]["lastname"];
    if ($params["clientdetails"]["company"]) {
        $customer["CompanyName"] = $params["clientdetails"]["company"];
    }
    $customer["Street1"] = $params["clientdetails"]["address1"];
    if ($params["clientdetails"]["address2"]) {
        $customer["Street2"] = $params["clientdetails"]["address2"];
    }
    $customer["City"] = $params["clientdetails"]["city"];
    $customer["State"] = $params["clientdetails"]["state"];
    $customer["PostalCode"] = $params["clientdetails"]["postcode"];
    $customer["Email"] = $params["clientdetails"]["email"];
    $customer["Phone"] = $params["clientdetails"]["phonenumber"];
    $customer["Country"] = $params["clientdetails"]["country"];
    $data = array("Method" => "CreateTokenCustomer", "RedirectUrl" => App::getSystemURL(), "CancelUrl" => App::getSystemURL(), "TransactionType" => "Purchase", "PartnerID" => EWAY_TOKENS_PARTNER_ID, "Customer" => $customer);
    if (array_key_exists("amount", $params) && $params["amount"]) {
        $amount = round($params["amount"] * 100);
        $data["Method"] = "TokenPayment";
        $data["Payment"] = array("TotalAmount" => $amount, "InvoiceNumber" => $params["invoiceid"], "InvoiceDescription" => "Invoice #" . $params["invoiceid"], "InvoiceReference" => $params["invoiceid"], "CurrencyCode" => $params["currency"]);
    }
    $response = curlCall($url, json_encode($data), array("CURLOPT_USERPWD" => (string) $params["apiKey"] . ":" . $params["apiPass"], "CURLOPT_HTTPHEADER" => array("Content-Type:  application/json")));
    $response = json_decode($response, true);
    if (!$response["SharedPaymentUrl"]) {
        $error = Lang::trans("invoiceserror") . " (" . $response["Errors"] . ")";
        return "<div class=\"alert alert-danger\">" . $error . "</div>";
    }
    WHMCS\Session::set("ewaytokensConfirm", $response["AccessCode"]);
    $loading = Lang::trans("loading");
    $output = ewaytokens_javascript_output($response);
    return (string) $output . "\n<div id=\"eWayTokensInfo\" class=\"alert alert-info\">\n    " . $loading . "\n</div>";
}
function ewaytokens_remoteupdate(array $params)
{
    WHMCS\Session::delete("ewaytokensConfirm");
    $sandbox = "";
    if ($params["testmode"]) {
        $sandbox = ".sandbox";
    }
    $url = "https://api" . $sandbox . ".ewaypayments.com/AccessCodesShared";
    $customerId = $params["gatewayid"];
    $data = array("Method" => "UpdateTokenCustomer", "RedirectUrl" => App::getSystemURL(), "CancelUrl" => App::getSystemURL(), "PartnerID" => EWAY_TOKENS_PARTNER_ID, "Customer" => array("TokenCustomerID" => $customerId), "Payment" => array("TotalAmount" => 0));
    $response = curlCall($url, json_encode($data), array("CURLOPT_USERPWD" => (string) $params["apiKey"] . ":" . $params["apiPass"], "CURLOPT_HTTPHEADER" => array("Content-Type:  application/json")));
    $response = json_decode($response, true);
    if (!$response["SharedPaymentUrl"]) {
        $error = Lang::trans("invoiceserror") . " (" . $response["Errors"] . ")";
        return "<div class=\"alert alert-danger\">" . $error . "</div>";
    }
    WHMCS\Session::set("ewaytokensConfirm", $response["AccessCode"]);
    $loading = Lang::trans("loading");
    $output = ewaytokens_javascript_output($response, true, $params["payMethod"]->id);
    return (string) $output . "\n<div id=\"eWayTokensInfo\" class=\"alert alert-info\">\n    " . $loading . "\n</div>";
}
function ewaytokens_capture(array $params)
{
    if (!$params["gatewayid"]) {
        return array("status" => "failed", "rawdata" => "No Remote Card Stored for this Client");
    }
    $whmcs = App::self();
    $sandbox = "";
    if ($params["testmode"]) {
        $sandbox = ".sandbox";
    }
    $url = "https://api" . $sandbox . ".ewaypayments.com/Transaction";
    try {
        $payment = array();
        $payment["InvoiceNumber"] = $params["invoiceid"];
        $payment["InvoiceDescription"] = "Invoice #" . $params["invoiceid"];
        $payment["InvoiceReference"] = $params["invoiceid"];
        $payment["TotalAmount"] = round($params["amount"] * 100);
        $payment["CurrencyCode"] = $params["currency"];
        $parameters = array();
        $parameters["Method"] = "TokenPayment";
        $parameters["RedirectUrl"] = $params["systemurl"];
        $parameters["CancelUrl"] = $params["returnurl"] . "&paymentfailed=true";
        $parameters["CustomerIP"] = $whmcs->getRemoteIp();
        $parameters["TransactionType"] = "Recurring";
        $parameters["Payment"] = $payment;
        $parameters["Customer"] = array("TokenCustomerID" => $params["gatewayid"]);
        $parameters["PartnerID"] = EWAY_TOKENS_PARTNER_ID;
        $payment = curlCall($url, json_encode($parameters), array("CURLOPT_USERPWD" => (string) $params["apiKey"] . ":" . $params["apiPass"], "CURLOPT_HTTPHEADER" => array("Content-Type:  application/json")));
        $payment = json_decode($payment, true);
        if ($payment["TransactionStatus"] == true) {
            return array("status" => "success", "transid" => $payment["TransactionID"], "rawdata" => $payment);
        }
        return array("status" => "declined", "rawdata" => $payment);
    } catch (Exception $e) {
        return array("status" => "error", "rawdata" => $e->getMessage());
    }
}
function ewaytokens_refund(array $params)
{
    $sandbox = "";
    if ($params["testmode"]) {
        $sandbox = ".sandbox";
    }
    $url = "https://api" . $sandbox . ".ewaypayments.com/Transaction/" . $params["transid"] . "/Refund";
    $parameters = array();
    $parameters["PartnerID"] = EWAY_TOKENS_PARTNER_ID;
    $refund = array();
    $refund["TotalAmount"] = round($params["amount"] * 100);
    $refund["CurrencyCode"] = $params["currency"];
    $parameters["Refund"] = $refund;
    $refund = curlCall($url, json_encode($parameters), array("CURLOPT_USERPWD" => (string) $params["apiKey"] . ":" . $params["apiPass"], "CURLOPT_HTTPHEADER" => array("Content-Type:  application/json")));
    if ($refund["TransactionStatus"] === true) {
        return array("status" => "success", "transid" => $refund["TransactionID"], "rawdata" => $refund);
    }
    return array("status" => "declined", "rawdata" => $refund);
}
function ewaytokens_adminstatusmsg(array $params)
{
    $gatewayId = $params["gatewayid"];
    if ($gatewayId) {
        return array("type" => "info", "title" => "eWay Remote Token", "msg" => "This customer has an eWay Token storing their card details " . "for automated recurring billing with ID " . $gatewayId);
    }
    return array();
}
function ewaytokens_javascript_output(array $response, $update = false, $payMethodId = 0)
{
    $error = Lang::trans("erroroccured");
    $success = Lang::trans("paymentMethods.addedSuccess");
    $pleaseWait = Lang::trans("pleasewait");
    $retry = Lang::trans("paymentMethods.retry");
    $failed = Lang::trans("paymentMethods.requestCancelled");
    $failed .= "<div class=\"pull-right\"><a class=\"btn btn-default btn-xs\"" . " onClick=\"eCrypt.showModalPayment(eWAYConfig, eWayCallback);\">" . $retry . "</a></div>";
    $confirmRouteUrl = "/payment/remote/confirm";
    if ($update) {
        $confirmRouteUrl .= "/update";
    }
    $confirmRouteData = "gateway=ewaytokens";
    if ($payMethodId) {
        $confirmRouteData .= "&pay_method_id=" . $payMethodId;
    }
    return "<script>\n    /**\n     * eWAY Rapid IFrame config object. Contains the SharedPaymentUrl\n     */\n    var eWAYConfig = {\n        sharedPaymentUrl: \"" . $response["SharedPaymentUrl"] . "\"\n    };\n\n    /**\n     * Example eWAY Rapid IFrame callback\n     */\n    function eWayCallback(result, transactionID, errors) {\n        var confirmUrl = '';\n        if (typeof WHMCS.utils !== 'undefined') {\n                confirmUrl = WHMCS.utils.getRouteUrl('" . $confirmRouteUrl . "');\n            } else {\n                confirmUrl = WHMCS.adminUtils.getAdminRouteUrl('/client" . $confirmRouteUrl . "');\n            }\n        if (result === 'Complete') {\n            jQuery('#eWayTokensInfo').slideUp('fast', function() {\n                jQuery(this).removeClass('alert-info alert-danger')\n                        .addClass('alert-success')\n                    .text('" . $success . ". " . $pleaseWait . "')\n                    .slideDown('fast');\n            });\n            WHMCS.http.jqClient.jsonPost({\n                url: confirmUrl,\n                data: '" . $confirmRouteData . "&token=' + csrfToken,\n                success: function(response) {\n                    if (typeof WHMCS.utils !== 'undefined') {\n                        var url = WHMCS.utils.getRouteUrl('/account/paymentmethods');\n                        if (response.redirect !== '') {\n                            url = response.redirect;\n                        }\n                        window.location.replace(url);\n                    }\n                    if (response.successWindow && response.successWindow !== '') {\n                        window[data.successWindow]();\n                        dialogClose();\n                    }\n                },\n                warning: function(error) {\n                    jQuery('#eWayTokensInfo').slideUp('fast', function() {\n                    jQuery(this).removeClass('alert-info alert-success')\n                        .addClass('alert-danger')\n                        .text(error)\n                        .slideDown('fast');\n                    });\n                },\n                fail: function(error) {\n                    jQuery('#eWayTokensInfo').slideUp('fast', function() {\n                    jQuery(this).removeClass('alert-info alert-success')\n                        .addClass('alert-danger')\n                        .text(error)\n                        .slideDown('fast');\n                    });\n                }\n            });\n        } else if (result === 'Error') {\n            jQuery('#eWayTokensInfo').slideUp('fast', function() {\n                jQuery(this).removeClass('alert-info alert-success')\n                        .addClass('alert-danger')\n                    .text('" . $error . ": ' + errors)\n                    .slideDown('fast');\n            });\n        } else if (result === 'Cancel') {\n            jQuery('#eWayTokensInfo').html('" . $failed . "');\n        }\n    }\n    var showModalTimer = null;\n    jQuery(document).ready(function() {\n        if (typeof eCrypt === 'undefined') {\n            jQuery.getScript(\n                'https://secure.ewaypayments.com/scripts/eCrypt.min.js'\n            );\n        }\n        showModalTimer = setTimeout(eWayTokensShowModal, 3000, eWAYConfig, eWayCallback);\n    });\n    function eWayTokensShowModal(config, callback) {\n        if (typeof eCrypt === 'undefined') {\n            showModalTimer = setTimeout(eWayTokensShowModal, 3000, config, callback);\n            return;\n        }\n        eCrypt.showModalPayment(config, callback);\n    }\n</script>";
}
function ewaytokens_remote_input_confirm(array $params)
{
    $sandbox = "";
    if ($params["testmode"]) {
        $sandbox = ".sandbox";
    }
    $remoteStorageToken = $params["remoteStorageToken"];
    $url = "https://api" . $sandbox . ".ewaypayments.com/AccessCode/" . $remoteStorageToken;
    $response = curlCall($url, "", array("CURLOPT_USERPWD" => (string) $params["apiKey"] . ":" . $params["apiPass"], "CURLOPT_HTTPHEADER" => array("Content-Type:  application/json")));
    $response = json_decode($response, true);
    if (array_key_exists("errors", $response) && $response["errors"]) {
        return array("warning" => $response["errors"]);
    }
    $customerToken = $response["TokenCustomerID"];
    $url = "https://api" . $sandbox . ".ewaypayments.com/Customer/" . $customerToken;
    $response2 = curlCall($url, "", array("CURLOPT_USERPWD" => (string) $params["apiKey"] . ":" . $params["apiPass"], "CURLOPT_HTTPHEADER" => array("Content-Type:  application/json")));
    $response2 = json_decode($response2, true);
    $customer = $response2["Customers"][0];
    $redirectPage = "";
    if ($response["TransactionID"]) {
        logTransaction($params["paymentmethod"], $response, "Success");
        $invoiceId = $response["InvoiceNumber"];
        $invoice = WHMCS\Billing\Invoice::find($invoiceId);
        $success = false;
        if ($invoice) {
            $invoice->addPayment($invoice->balance, $response["TransactionID"], 0, "ewaytokens");
            $invoice->saveRemoteCard($customer["CardDetails"]["Number"], getCardTypeByCardNumber($customer["CardDetails"]["Number"]), $customer["CardDetails"]["ExpiryMonth"] . $customer["CardDetails"]["ExpiryYear"], $customerToken);
            $success = true;
        } else {
            logTransaction($params["paymentmethod"], $response, "Invoice ID Not Found");
        }
        $redirectPage = App::getSystemUrl() . "viewinvoice.php?id=" . $invoiceId . "&";
        if ($success) {
            $redirectPage .= "paymentsuccess=true";
        } else {
            $redirectPage .= "paymentfailed=true";
        }
    }
    return array("success" => true, "remoteStorageToken" => $customerToken, "cardNumber" => $customer["CardDetails"]["Number"], "cardExpiry" => $customer["CardDetails"]["ExpiryMonth"] . $customer["CardDetails"]["ExpiryYear"], "redirect" => $redirectPage);
}

?>