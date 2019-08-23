<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\TCO;

class Inline
{
    public function link(array $params = array())
    {
        $formParameters = array();
        $recurringFormParameters = array();
        if ($params["demomode"]) {
            $formParameters["demo"] = "Y";
        }
        $invoice = \WHMCS\Billing\Invoice::find($params["invoiceid"]);
        $invoiceData = $invoice->getBillingValues();
        $overdue = $invoiceData["overdue"];
        if ($overdue) {
            $params["recurringBilling"] = "disablerecur";
        }
        unset($invoiceData["overdue"]);
        $i = 0;
        foreach ($invoiceData as $invoiceDatum) {
            $firstPaymentAmount = $invoiceDatum["amount"];
            if (array_key_exists("firstPaymentAmount", $invoiceDatum)) {
                $firstPaymentAmount = $invoiceDatum["firstPaymentAmount"];
            }
            if (0 <= $invoiceDatum["amount"]) {
                $startupFee = $firstPaymentAmount - $invoiceDatum["amount"];
                $startupFee += $invoiceDatum["setupFee"];
                $formParameters["li_" . $i . "_product_id"] = $invoiceDatum["itemId"];
                $formParameters["li_" . $i . "_startup_fee"] = $startupFee;
                $formParameters["li_" . $i . "_type"] = "product";
                $formParameters["li_" . $i . "_price"] = $invoiceDatum["lineItemAmount"];
                $formParameters["li_" . $i . "_name"] = $invoiceDatum["description"];
                if ($params["recurringBilling"] != "disablerecur" && !$overdue && $invoiceDatum["recurringCyclePeriod"]) {
                    $billingCycle = $invoiceDatum["recurringCyclePeriod"] . " Month";
                    if ($invoiceDatum["recurringCycleUnits"] == "Years") {
                        $billingCycle = $invoiceDatum["recurringCyclePeriod"] . " Year";
                    }
                    $recurringFormParameters["li_" . $i . "_recurrence"] = $billingCycle;
                    $recurringFormParameters["li_" . $i . "_duration"] = "Forever";
                    $recurringFormParameters["li_" . $i . "_price"] = $invoiceDatum["amount"];
                }
            } else {
                $formParameters["li_" . $i . "_type"] = "coupon";
                $formParameters["li_" . $i . "_price"] = abs($invoiceDatum["amount"]);
                $formParameters["li_" . $i . "_name"] = $invoiceDatum["description"];
            }
            $i++;
        }
        if (0 < $invoice->credit) {
            $formParameters["li_" . $i . "_type"] = "coupon";
            $formParameters["li_" . $i . "_price"] = $invoice->credit;
            $formParameters["li_" . $i . "_name"] = "Credit";
            $i++;
        }
        if (0 < $invoice->amountPaid) {
            $formParameters["li_" . $i . "_type"] = "coupon";
            $formParameters["li_" . $i . "_price"] = $invoice->amountPaid;
            $formParameters["li_" . $i . "_name"] = "Partial Payments";
            $i++;
        }
        $cardName = (string) $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
        $formParameters["sid"] = $params["vendornumber"];
        $formParameters["mode"] = "2CO";
        $formParameters["currency_code"] = $params["currency"];
        $formParameters["merchant_order_id"] = $params["invoiceid"];
        $formParameters["card_holder_name"] = $cardName;
        $formParameters["email"] = $params["clientdetails"]["email"];
        $formParameters["street_address"] = $params["clientdetails"]["address1"];
        $formParameters["street_address2"] = $params["clientdetails"]["address2"];
        $formParameters["city"] = $params["clientdetails"]["city"];
        $formParameters["state"] = $params["clientdetails"]["state"];
        $formParameters["zip"] = $params["clientdetails"]["postcode"];
        $formParameters["country"] = $params["clientdetails"]["country"];
        $formParameters["phone"] = $params["clientdetails"]["telephoneNumber"];
        $formParameters["return_url"] = (string) $params["systemurl"] . "viewinvoice.php?id=" . $params["invoiceid"];
        $formParameters["x_receipt_link_url"] = (string) $params["systemurl"] . "modules/gateways/callback/tco.php";
        return $this->buildHtmlForm($params, $formParameters, $recurringFormParameters);
    }
    protected function buildHtmlForm(array $params, array $formParameters, array $recurringFormParameters = array())
    {
        $url = "https://www.2checkout.com/checkout/purchase";
        $jsUrl = "https://www.2checkout.com/static/checkout/javascript/direct.min.js";
        if ($params["sandbox"] == "on") {
            $url = "https://sandbox.2checkout.com/checkout/purchase";
        }
        $recurringForm = "";
        if ($recurringFormParameters) {
            $recurringFormParameters = array_merge($formParameters, $recurringFormParameters);
            foreach ($recurringFormParameters as $parameterName => $parameterValue) {
                $recurringForm .= "<input type=\"hidden\" name=\"" . $parameterName . "\" value=\"" . $parameterValue . "\">";
            }
        }
        $items = "";
        foreach ($formParameters as $parameterName => $parameterValue) {
            $items .= "<input type=\"hidden\" name=\"" . $parameterName . "\" value=\"" . $parameterValue . "\">";
        }
        $code = "";
        $payButtonText = \Lang::trans("invoicesubscriptionpayment");
        $redirectWait = \Lang::trans("pleaseWaitForPayment");
        $clickToReload = \Lang::trans("clickToReload");
        if ($recurringForm) {
            $code .= "<form id=\"tcoInlineRecurringFrm\" action=\"" . $url . "\" method=\"post\">\n" . $recurringForm . "\n    <button id=\"tcoRecurringSubmit\" name='submit' type=\"submit\" class=\"btn btn-primary\">\n        " . $payButtonText . "\n        <i id=\"tcoRecurringSubmitSpinner\" class=\"fas fa-spin fa-spinner fa-fw\"></i>\n    </button>\n</form><br>";
        }
        if ($params["recurringBilling"] != "forcerecur") {
            $payButtonText = \Lang::trans("invoiceoneoffpayment");
            $code .= "<form id=\"tcoInlineFrm\" action=\"" . $url . "\" method=\"post\">\n" . $items . "\n    <button id=\"tcoSubmit\" name='submit' type=\"submit\" class=\"btn btn-primary\">\n        " . $payButtonText . "\n        <i id=\"tcoSubmitSpinner\" class=\"fas fa-spin fa-spinner fa-fw\"></i>\n    </button>\n</form>";
        }
        $code .= "<script src=\"" . $jsUrl . "\"></script>\n<script type=\"text/javascript\">\n    var buttonClicked = false,\n        noAutoSubmit = true,\n        button = document.getElementById('tcoSubmit'),\n        recurringButton = document.getElementById('tcoRecurringSubmit'),\n        spinner = document.getElementById('tcoSubmitSpinner'),\n        recurringSpinner = document.getElementById('tcoRecurringSubmitSpinner');\n      \n    if (button) {\n        if (spinner.className.split(' ').indexOf('hidden') === -1) {\n            button.style.width = button.offsetWidth.toString() + 'px';\n            spinner.className += ' hidden';\n        }\n        button.onclick = function() {\n            if (buttonClicked !== true) {\n                var arr;\n                buttonClicked = true;\n                arr = button.className.split(' ');\n                if (arr.indexOf('disabled') === -1) {\n                    button.className += ' disabled';\n                }\n                if (recurringButton) {\n                    arr = recurringButton.className.split(' ');\n                    if (arr.indexOf('disabled') === -1) {\n                        recurringButton.className += ' disabled';\n                    }\n                }\n                if (spinner.className.split(' ').indexOf('hidden') !== -1) {\n                    spinner.className = spinner.className.replace(/\\bhidden\\b/g, '');\n                }\n            }\n        };\n    }\n    \n    if (recurringButton) {\n        if (recurringSpinner.className.split(' ').indexOf('hidden') === -1) {\n            recurringButton.style.width = recurringButton.offsetWidth.toString() + 'px';\n            recurringSpinner.className += ' hidden';\n        }\n        recurringButton.onclick = function() {\n            if (buttonClicked !== true) {\n                var arr;\n                buttonClicked = true;\n                arr = recurringButton.className.split(' ');\n                if (arr.indexOf('disabled') === -1) {\n                    recurringButton.className += ' disabled';\n                }\n                if (button) {\n                    arr = button.className.split(' ');\n                    if (arr.indexOf('disabled') === -1) {\n                        button.className += ' disabled';\n                    }\n                }\n                if (recurringSpinner.className.split(' ').indexOf('hidden') !== -1) {\n                    recurringSpinner.className = recurringSpinner.className.replace(/\\bhidden\\b/g, '');\n                }\n            }\n        };\n    }\n    \n    if (document.getElementById('frmPayment') !== null) {\n        setTimeout(function() {\n            if (recurringButton) {\n                recurringButton.click();\n            } else {\n                button.click();\n            }\n        }, 2000);\n    }\n    \n    function close_callback(d)\n    {\n        var arr;\n        if (recurringButton) {\n            arr = recurringSpinner.className.split(' ');\n            if (arr.indexOf('hidden') === -1) {\n                recurringSpinner.className += ' hidden';\n            }\n            if (spinner.className.split(' ').indexOf('hidden') !== -1) {\n                spinner.className = spinner.className.replace(/\\bhidden\\b/g, '');\n            }\n        }\n        if (button) {\n            arr = spinner.className.split(' ');\n            if (arr.indexOf('hidden') === -1) {\n                spinner.className += ' hidden';\n            }\n            arr = button.className.split(' ');\n            if (arr.indexOf('hidden') === -1) {\n                button.className += ' hidden';\n            }\n        }\n        window.location.href = 'viewinvoice.php?id=" . $params["invoiceid"] . "&paymentfailed=true';\n    }\n    \n    (function() {\n         inline_2Checkout.subscribe('checkout_closed', close_callback);\n     }());\n</script>";
        return $code;
    }
    public function callback(array $params = array())
    {
        $gatewayModuleName = "tco";
        $tcoOrderNumber = \App::getFromRequest("sale_id");
        $tcoInvoiceId = \App::getFromRequest("invoice_id");
        $hashSid = $params["vendornumber"];
        $postedSid = \App::getFromRequest("vendor_id");
        $hashSecretWord = $params["secretword"];
        $hashToValidate = strtoupper(md5($tcoOrderNumber . $hashSid . $tcoInvoiceId . $hashSecretWord));
        if ($hashSid != $postedSid || hash_equals($hashToValidate, \App::getFromRequest("md5_hash")) === false) {
            logTransaction($params["paymentmethod"], $_POST, "MD5 Hash Failure");
        } else {
            $notificationType = \App::getFromRequest("message_type");
            $itemCount = \App::getFromRequest("item_count");
            $serviceId = \App::getFromRequest("item_id_1");
            $transactionId = \App::getFromRequest("sale_id");
            $recurringTransactionId = $transactionId . "-" . \App::getFromRequest("invoice_id");
            $amount = \App::getFromRequest("invoice_list_amount");
            if (!$amount) {
                $amount = \App::getFromRequest("item_list_amount_1");
            }
            $invoiceId = \App::getFromRequest("vendor_order_id");
            $currency = \App::getFromRequest("list_currency");
            try {
                $currency = \WHMCS\Billing\Currency::where("code", $currency)->firstOrFail();
            } catch (\Exception $e) {
                logTransaction($params["paymentmethod"], $_POST, "Unrecognised Currency");
                return NULL;
            }
            $hostingAndAddonIds = array("hosting" => array(), "addon" => array());
            if (in_array($notificationType, array("INVOICE_STATUS_CHANGED", "ORDER_CREATED", "RECURRING_INSTALLMENT_SUCCESS"))) {
                $invoiceToBeFound = true;
                for ($i = 1; $i <= $itemCount; $i++) {
                    $serviceId = \App::getFromRequest("item_id_" . $i);
                    $recurringPayment = trim(\App::getFromRequest("item_rec_status_" . $i));
                    if (substr($serviceId, 0, 1) == "H") {
                        $hostingAndAddonIds["hosting"][] = substr($serviceId, 1);
                    } else {
                        if (substr($serviceId, 0, 1) == "A") {
                            $hostingAndAddonIds["addon"][] = substr($serviceId, 1);
                        }
                    }
                    if ($invoiceToBeFound && ($recurringPayment && $serviceId || $notificationType == "RECURRING_INSTALLMENT_SUCCESS")) {
                        $invoiceId = self::findInvoiceID($serviceId, $transactionId);
                        if ($invoiceId) {
                            $invoiceToBeFound = false;
                        }
                    }
                }
                $invoiceId = checkCbInvoiceID($invoiceId, $params["paymentmethod"]);
                $invoice = \WHMCS\Billing\Invoice::with("client", "client.currencyrel")->find($invoiceId);
            }
            $notificationOnly = false;
            switch ($notificationType) {
                case "INVOICE_STATUS_CHANGED":
                    if (!$params["skipfraudcheck"]) {
                        $fraudStatus = \App::getFromRequest("fraud_status");
                        $invoiceStatus = \App::getFromRequest("invoice_status");
                        if (in_array($invoiceStatus, array("approved", "deposited"))) {
                            if ($fraudStatus == "pass") {
                                logTransaction($params["paymentmethod"], $_POST, "Fraud Status Pass");
                                checkCbTransID($transactionId);
                                $amount = Helper::convertCurrency($amount, $currency, $invoice);
                                $invoice->addPayment($amount, $transactionId, 0, $gatewayModuleName);
                                self::saveRecurringSaleId($hostingAndAddonIds, $transactionId);
                            } else {
                                logTransaction($params["paymentmethod"], $_POST, "Fraud Status Fail");
                            }
                        } else {
                            $notificationOnly = true;
                        }
                    }
                    break;
                case "ORDER_CREATED":
                    if ($params["skipfraudcheck"]) {
                        logTransaction($params["paymentmethod"], $_POST, "Payment Success");
                        checkCbTransID($transactionId);
                        $amount = Helper::convertCurrency($amount, $currency, $invoice);
                        $invoice->addPayment($amount, $transactionId, 0, $gatewayModuleName);
                    }
                    break;
                case "RECURRING_INSTALLMENT_FAILED":
                    logTransaction($params["paymentmethod"], $_POST, "Recurring Payment Failed", $params);
                    break;
                case "RECURRING_INSTALLMENT_SUCCESS":
                    checkCbTransID($recurringTransactionId);
                    if (!$invoiceId && !$serviceId) {
                        logTransaction($params["paymentmethod"], array_merge(array("InvoiceLookup" => "No Service ID Found in Callback"), $_POST), "Recurring Error");
                    } else {
                        if (!$invoiceId) {
                            $message = "No invoice match found for Service ID " . $serviceId . " or Subscription ID";
                            logTransaction($params["paymentmethod"], array_merge(array("InvoiceLookup" => $message), $_POST), "Recurring Error");
                        } else {
                            logTransaction($params["paymentmethod"], $_POST, "Recurring Success");
                            $amount = Helper::convertCurrency($amount, $currency, $invoice);
                            $invoice->addPayment($amount, $recurringTransactionId, 0, $gatewayModuleName);
                            self::saveRecurringSaleId($hostingAndAddonIds, $recurringTransactionId);
                        }
                    }
                    break;
                default:
                    $notificationOnly = true;
            }
            if ($notificationOnly) {
                logTransaction($params["paymentmethod"], $_POST, "Notification Only", $params);
            }
        }
    }
    public function clientCallback(array $params = array())
    {
        $invoiceId = \App::getFromRequest("merchant_order_id");
        $tcoOrderNumber = \App::getFromRequest("order_number");
        $total = \App::getFromRequest("total");
        if (\App::isInRequest("product_description")) {
            $invoiceId = \App::getFromRequest("product_description");
        }
        if (!$params["demomode"]) {
            $hashSid = $params["vendornumber"];
            $postedSid = \App::getFromRequest("sid");
            $hashSecretWord = $params["secretword"];
            $hashToValidate = strtoupper(md5($hashSecretWord . $hashSid . $tcoOrderNumber . $total));
            if ($hashSid != $postedSid || hash_equals($hashToValidate, \App::getFromRequest("key")) === false) {
                logTransaction($params["paymentmethod"], $_POST, "MD5 Hash Failure");
                return NULL;
            }
        }
        logTransaction($params["paymentmethod"], $_REQUEST, "Client Redirect", $params);
        $systemUrl = \App::getSystemURL();
        $companyName = \WHMCS\Config\Setting::getValue("CompanyName");
        $redirectUri = $systemUrl . "clientarea.php?action=invoices";
        if (\App::getFromRequest("credit_card_processed") == "Y" && $params["skipfraudcheck"]) {
            $redirectUri = $systemUrl . "viewinvoice.php?id=" . $invoiceId . "&paymentsuccess=true";
        } else {
            if (\App::getFromRequest("credit_card_processed") == "Y") {
                $redirectUri = $systemUrl . "viewinvoice.php?id=" . $invoiceId . "&pendingreview=true";
            } else {
                logTransaction($params["paymentmethod"], $_REQUEST, "Unsuccessful", $params);
            }
        }
        header("Location: " . $redirectUri);
    }
    protected static function findInvoiceId($serviceId, $transactionId)
    {
        $itemType = substr($serviceId, 0, 1);
        switch ($itemType) {
            case "H":
                $types = array("Hosting");
                break;
            case "A":
                $types = array("Addon");
                break;
            case "D":
                $types = array("Domain", "DomainTransfer", "DomainRegister");
                break;
            case "i":
                $parts = explode("_", substr($serviceId, 1));
                return $parts[0];
            default:
                return null;
        }
        foreach ($types as $type) {
            $invoiceId = findInvoiceID(substr($serviceId, 1), $transactionId, $type);
            if ($invoiceId) {
                return $invoiceId;
            }
        }
        return null;
    }
    protected static function saveRecurringSaleId(array $ids, $subscriptionId)
    {
        if (is_array($ids["hosting"]) && count($ids["hosting"])) {
            \WHMCS\Service\Service::whereIn("id", $ids["hosting"])->update(array("subscriptionid" => $subscriptionId));
        }
        if (is_array($ids["addon"]) && count($ids["addon"])) {
            foreach ($ids["addon"] as $id) {
                try {
                    $addon = \WHMCS\Service\Addon::findOrFail($id);
                    $addon->serviceProperties->save(array("subscriptionid" => $subscriptionId));
                } catch (\Exception $e) {
                }
            }
        }
    }
}

?>