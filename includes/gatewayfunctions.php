<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function loadGatewayModule($paymentMethod)
{
    $paymentMethod = WHMCS\Gateways::makeSafeName($paymentMethod);
    if (!$paymentMethod) {
        return false;
    }
    $basePath = fetchGatewayModuleDirectory();
    $expectedFile = $basePath . "/" . $paymentMethod . ".php";
    $state = false;
    if (file_exists($expectedFile)) {
        ob_start();
        $state = (include_once $expectedFile !== false);
        ob_end_clean();
    }
    return $state;
}
function fetchGatewayModuleDirectory()
{
    return ROOTDIR . "/modules/gateways";
}
function paymentMethodsSelection($blankSelection = "", $tabIndex = false)
{
    global $paymentmethod;
    if ($tabIndex) {
        $tabIndex = " tabindex=\"" . $tabIndex . "\"";
    }
    $code = "<select name=\"paymentmethod\" class=\"form-control select-inline\"" . $tabIndex . ">";
    if ($blankSelection) {
        $code .= "<option value=\"\">" . $blankSelection . "</option>";
    }
    $result = select_query("tblpaymentgateways", "gateway,value", array("setting" => "name"), "order", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $gateway = $data["gateway"];
        $value = $data["value"];
        $code .= "<option value=\"" . $gateway . "\"";
        if ($paymentmethod == $gateway) {
            $code .= " selected";
        }
        $code .= ">" . $value . "</option>";
    }
    $code .= "</select>";
    return $code;
}
function checkActiveGateway()
{
    if (count(getGatewaysArray())) {
        return true;
    }
    return false;
}
function getGatewaysArray()
{
    $gateways = array();
    $result = select_query("tblpaymentgateways", "gateway,value", array("setting" => "name"), "order", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $gateways[$data["gateway"]] = $data["value"];
    }
    return $gateways;
}
function getGatewayName($moduleName)
{
    return get_query_val("tblpaymentgateways", "value", array("gateway" => $moduleName, "setting" => "name"));
}
function showPaymentGatewaysList($disabledGateways = array(), $userId = NULL, $forceAll = false)
{
    $result = select_query("tblpaymentgateways", "gateway, value", array("setting" => "name"), "order", "ASC");
    $gatewayList = array();
    $allowChoice = (bool) WHMCS\Config\Setting::getValue("AllowCustomerChangeInvoiceGateway") || $forceAll;
    $clientGateway = getClientsPaymentMethod($userId);
    while ($data = mysql_fetch_array($result)) {
        $showPaymentGateway = $data["gateway"];
        if (!$allowChoice && strcasecmp($showPaymentGateway, $clientGateway) !== 0) {
            continue;
        }
        $showPaymentGWValue = $data["value"];
        $gatewayType = get_query_val("tblpaymentgateways", "value", array("setting" => "type", "gateway" => $showPaymentGateway));
        $isVisible = (bool) get_query_val("tblpaymentgateways", "value", array("setting" => "visible", "gateway" => $showPaymentGateway));
        if ($isVisible && !in_array($showPaymentGateway, $disabledGateways)) {
            $gatewayList[$showPaymentGateway] = array("sysname" => $showPaymentGateway, "name" => $showPaymentGWValue, "type" => $gatewayType);
        }
    }
    return $gatewayList;
}
function getVariables($gateway)
{
    return getGatewayVariables($gateway);
}
function getGatewayVariables($gateway, $invoiceId = "")
{
    $invoice = new WHMCS\Invoice($invoiceId);
    try {
        $params = $invoice->initialiseGatewayAndParams($gateway);
    } catch (WHMCS\Exception\Module\NotActivated $e) {
        logActivity("Failed to initialise payment gateway module: " . $e->getMessage());
        throw new WHMCS\Exception\Fatal("Gateway Module \"" . WHMCS\Input\Sanitize::makeSafeForOutput($gateway) . "\" Not Activated");
    } catch (Exception $e) {
        logActivity("Failed to initialise payment gateway module: " . $e->getMessage());
        throw new WHMCS\Exception\Fatal("Could not initialise payment gateway.");
    }
    if ($invoiceId) {
        $params = array_merge($params, $invoice->getGatewayInvoiceParams());
    }
    $params = WHMCS\Input\Sanitize::convertToCompatHtml($params);
    return $params;
}
function logTransaction($gateway, $data, $result, array $passedParams = array(), WHMCS\Module\Gateway $gatewayModule = NULL)
{
    global $params;
    if (!$params) {
        $params = array();
    }
    $historyId = 0;
    if (array_key_exists("history_id", $passedParams)) {
        $historyId = $passedParams["history_id"];
        unset($passedParams["history_id"]);
    }
    $params = array_merge($params, $passedParams);
    $invoiceData = "";
    if ($params["invoiceid"]) {
        $invoiceData .= "Invoice ID => " . $params["invoiceid"] . "\n";
    }
    if ($params["clientdetails"]["userid"]) {
        $invoiceData .= "User ID => " . $params["clientdetails"]["userid"] . "\n";
    }
    if ($params["amount"]) {
        $invoiceData .= "Amount => " . $params["amount"] . "\n";
    }
    if (is_array($data)) {
        $logData = outputDataArrayToString($data);
    } else {
        $logData = $data;
    }
    static $gatewayNames = array();
    if (!array_key_exists($gateway, $gatewayNames)) {
        $gatewayNames[$gateway] = $gateway;
        if (!$gatewayModule) {
            $gatewayModule = new WHMCS\Module\Gateway();
            $loaded = $gatewayModule->load($gateway);
        } else {
            $loaded = $gatewayModule->getLoadedModule() != "";
        }
        if ($loaded) {
            $gatewayConfig = $gatewayModule->getConfiguration();
            if (array_key_exists("FriendlyName", $gatewayConfig)) {
                $gatewayNames[$gateway] = $gatewayConfig["FriendlyName"]["Value"];
            }
        }
    }
    $gateway = $gatewayNames[$gateway];
    $array = array("date" => "now()", "gateway" => $gateway, "data" => $invoiceData . $logData, "result" => $result, "transaction_history_id" => $historyId);
    insert_query("tblgatewaylog", $array);
    run_hook("LogTransaction", $array);
}
function checkCbInvoiceID($invoiceId, $gateway = "Unknown")
{
    $result = select_query("tblinvoices", "id", array("id" => $invoiceId));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    if (!$id) {
        logtransaction($gateway, $_REQUEST, "Invoice ID Not Found");
        exit;
    }
    return $id;
}
function checkCbTransID($transactionId)
{
    $result = select_query("tblaccounts", "id", array("transid" => $transactionId));
    $numRows = mysql_num_rows($result);
    if ($numRows) {
        exit;
    }
}
function callback3DSecureRedirect($invoiceId, $success = false)
{
    global $CONFIG;
    $redirectPage = App::getSystemUrl() . "viewinvoice.php?id=" . $invoiceId . "&";
    if ($success) {
        $redirectPage .= "paymentsuccess=true";
    } else {
        $redirectPage .= "paymentfailed=true";
    }
    echo "<html>\n    <head>\n        <title>" . $CONFIG["CompanyName"] . "</title>\n    </head>\n    <body onload=\"document.frmResultPage.submit();\">\n        <form name=\"frmResultPage\" method=\"post\" action=\"" . $redirectPage . "\" target=\"_parent\">\n            <noscript>\n                <br>\n                <br>\n                <center>\n                    <p style=\"color:#cc0000;\"><b>Processing Your Transaction</b></p>\n                    <p>JavaScript is currently disabled or is not supported by your browser.</p>\n                    <p>Please click Submit to continue the processing of your transaction.</p>\n                    <input type=\"submit\" value=\"Submit\">\n                </center>\n            </noscript>\n        </form>\n    </body>\n</html>";
    exit;
}
function getRecurringBillingValues($invoiceId)
{
    global $CONFIG;
    if (!function_exists("getBillingCycleMonths")) {
        include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "invoicefunctions.php";
    }
    $firstCyclePeriod = "";
    $firstCycleUnits = "";
    $invoiceId = (int) $invoiceId;
    $result = select_query("tblinvoiceitems", "tblinvoiceitems.relid," . "tblhosting.userid," . "tblhosting.billingcycle," . "tblhosting.packageid," . "tblhosting.regdate," . "tblhosting.nextduedate", array("invoiceid" => $invoiceId, "type" => "Hosting"), "tblinvoiceitems`.`id", "ASC", "", "tblhosting ON tblhosting.id=tblinvoiceitems.relid");
    $data = mysql_fetch_array($result);
    $relatedId = $data["relid"];
    $userId = $data["userid"];
    $billingCycle = $data["billingcycle"];
    $packageId = $data["packageid"];
    $registrationDate = $data["regdate"];
    $nextDueDate = $data["nextduedate"];
    if (!$relatedId || $billingCycle == "One Time" || $billingCycle == "Free Account") {
        return false;
    }
    $result = select_query("tblinvoices", "total," . "taxrate," . "taxrate2," . "paymentmethod," . "(SELECT SUM(amountin)-SUM(amountout) FROM tblaccounts WHERE invoiceid=tblinvoices.id) AS amountpaid", array("id" => $invoiceId));
    $data = mysql_fetch_array($result);
    $total = $data["total"];
    $taxRate = $data["taxrate"];
    $taxRate2 = $data["taxrate2"];
    $paymentMethod = $data["paymentmethod"];
    $amountPaid = $data["amountpaid"];
    $firstPaymentAmount = $total - $amountPaid;
    $recurringCyclePeriod = getBillingCycleMonths($billingCycle);
    $recurringCycleUnits = "Months";
    if (12 <= $recurringCyclePeriod) {
        $recurringCyclePeriod = $recurringCyclePeriod / 12;
        $recurringCycleUnits = "Years";
    }
    $taxCalculator = new WHMCS\Billing\Tax();
    $taxCalculator->setIsInclusive($CONFIG["TaxType"] == "Inclusive")->setIsCompound($CONFIG["TaxL2Compound"])->setLevel1Percentage($taxRate)->setLevel2Percentage($taxRate2);
    $recurringAmount = 0;
    $query = "SELECT tblhosting.amount,tblinvoiceitems.amount as invoiced_amount,tblinvoiceitems.taxed" . " FROM tblinvoiceitems" . " INNER JOIN tblhosting ON tblhosting.id=tblinvoiceitems.relid" . " WHERE tblinvoiceitems.invoiceid=" . $invoiceId . " AND tblinvoiceitems.type='Hosting'" . " AND tblhosting.billingcycle='" . db_escape_string($billingCycle) . "'";
    $result = full_query($query);
    $recurringTax = array();
    while ($data = mysql_fetch_array($result)) {
        $productAmount = $data["amount"];
        $invoicedAmount = $data["invoiced_amount"];
        $taxed = $data["taxed"];
        if ($taxed) {
            if ($invoicedAmount <= $productAmount) {
                $recurringTax[] = $productAmount;
            } else {
                $recurringTax[] = $invoicedAmount;
                $recurringTax[] = $productAmount - $invoicedAmount;
            }
        }
        $recurringAmount += $productAmount;
    }
    $productTax1 = $productTax2 = 0;
    if (WHMCS\Config\Setting::getValue("TaxPerLineItem")) {
        foreach ($recurringTax as $taxBase) {
            $taxCalculator->setTaxBase($taxBase);
            $productTax1 += $taxCalculator->getLevel1TaxTotal();
            $productTax2 += $taxCalculator->getLevel2TaxTotal();
        }
    } else {
        $taxCalculator->setTaxBase(array_sum($recurringTax));
        $productTax1 = $taxCalculator->getLevel1TaxTotal();
        $productTax2 = $taxCalculator->getLevel2TaxTotal();
    }
    if ($CONFIG["TaxType"] == "Exclusive") {
        $recurringAmount += $productTax1 + $productTax2;
    }
    $query = "SELECT tblhostingaddons.recurring,tblhostingaddons.tax" . " FROM tblinvoiceitems" . " INNER JOIN tblhostingaddons ON tblhostingaddons.id=tblinvoiceitems.relid" . " WHERE tblinvoiceitems.invoiceid=" . $invoiceId . " AND tblinvoiceitems.type='Addon'" . " AND tblhostingaddons.billingcycle='" . db_escape_string($billingCycle) . "'";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        list($addonAmount, $addonTax) = $data;
        if ($CONFIG["TaxType"] == "Exclusive" && $addonTax) {
            if ($CONFIG["TaxL2Compound"]) {
                $addonAmount = $addonAmount + $addonAmount * $taxRate / 100;
                $addonAmount = $addonAmount + $addonAmount * $taxRate2 / 100;
            } else {
                $addonAmount = $addonAmount + format_as_currency($addonAmount * $taxRate / 100) + format_as_currency($addonAmount * $taxRate2 / 100);
            }
        }
        $recurringAmount += $addonAmount;
    }
    if (in_array($billingCycle, array("Annually", "Biennially", "Triennially"))) {
        $cycleregperiods = array("Annually" => "1", "Biennially" => "2", "Triennially" => "3");
        $query = "SELECT SUM(tbldomains.recurringamount)" . " FROM tblinvoiceitems" . " INNER JOIN tbldomains ON tbldomains.id=tblinvoiceitems.relid" . " WHERE tblinvoiceitems.invoiceid=" . $invoiceId . " AND tblinvoiceitems.type IN ('DomainRegister','DomainTransfer','Domain')" . " AND tbldomains.registrationperiod='" . db_escape_string($cycleregperiods[$billingCycle]) . "'";
        $result = full_query($query);
        $data = mysql_fetch_array($result);
        $domainAmount = $data[0];
        if ($CONFIG["TaxType"] == "Exclusive" && $CONFIG["TaxDomains"]) {
            if ($CONFIG["TaxL2Compound"]) {
                $domainAmount = $domainAmount + $domainAmount * $taxRate / 100;
                $domainAmount = $domainAmount + $domainAmount * $taxRate2 / 100;
            } else {
                $domainAmount = $domainAmount + format_as_currency($domainAmount * $taxRate / 100) + format_as_currency($domainAmount * $taxRate2 / 100);
            }
        }
        $recurringAmount += $domainAmount;
    }
    $result = select_query("tblinvoices", "duedate", array("id" => $invoiceId));
    $data = mysql_fetch_array($result);
    $invoiceDueDate = $data["duedate"];
    $invoiceDueDate = str_replace("-", "", $invoiceDueDate);
    $overdue = $invoiceDueDate < date("Ymd");
    $result = select_query("tblproducts", "proratabilling,proratadate,proratachargenextmonth", array("id" => $packageId));
    $data = mysql_fetch_array($result);
    $proRataBilling = $data["proratabilling"];
    $proRataDate = $data["proratadate"];
    $proRataChargeNextMonth = $data["proratachargenextmonth"];
    if ($registrationDate == $nextDueDate && $proRataBilling) {
        $orderYear = substr($registrationDate, 0, 4);
        $orderMonth = substr($registrationDate, 5, 2);
        $orderDay = substr($registrationDate, 8, 2);
        $proRataValues = getProrataValues($billingCycle, 0, $proRataDate, $proRataChargeNextMonth, $orderDay, $orderMonth, $orderYear, $userId);
        $firstCyclePeriod = $proRataValues["days"];
        $firstCycleUnits = "Days";
    }
    if (!$firstCyclePeriod) {
        $firstCyclePeriod = $recurringCyclePeriod;
    }
    if (!$firstCycleUnits) {
        $firstCycleUnits = $recurringCycleUnits;
    }
    $result = select_query("tblpaymentgateways", "value", array("gateway" => $paymentMethod, "setting" => "convertto"));
    $data = mysql_fetch_array($result);
    $convertTo = $data[0];
    if ($convertTo) {
        $currency = getCurrency($userId);
        $firstPaymentAmount = convertCurrency($firstPaymentAmount, $currency["id"], $convertTo);
        $recurringAmount = convertCurrency($recurringAmount, $currency["id"], $convertTo);
    }
    $firstPaymentAmount = format_as_currency($firstPaymentAmount);
    $recurringAmount = format_as_currency($recurringAmount);
    $recurringBillingValues = array();
    $recurringBillingValues["primaryserviceid"] = $relatedId;
    if ($firstPaymentAmount != $recurringAmount) {
        $recurringBillingValues["firstpaymentamount"] = $firstPaymentAmount;
        $recurringBillingValues["firstcycleperiod"] = $firstCyclePeriod;
        $recurringBillingValues["firstcycleunits"] = $firstCycleUnits;
    }
    $recurringBillingValues["recurringamount"] = $recurringAmount;
    $recurringBillingValues["recurringcycleperiod"] = $recurringCyclePeriod;
    $recurringBillingValues["recurringcycleunits"] = $recurringCycleUnits;
    $recurringBillingValues["overdue"] = $overdue;
    return $recurringBillingValues;
}
function cancelSubscriptionForService($serviceID, $userID = 0)
{
    $userID = (int) $userID;
    $serviceID = (int) $serviceID;
    if ($serviceID == 0) {
        throw new InvalidArgumentException("Required value serviceID Missing");
    }
    $serviceData = new WHMCS\Service($serviceID, $userID == 0 ? "" : $userID);
    if ($userID == 0) {
        $userID = $serviceData->getData("userid");
    }
    $paymentMethod = $serviceData->getData("paymentmethod");
    $subscriptionID = $serviceData->getData("subscriptionid");
    if (!$subscriptionID) {
        throw new InvalidArgumentException("Required value SubscriptionID Missing");
    }
    $gateway = new WHMCS\Module\Gateway();
    $gateway->load($paymentMethod);
    if ($gateway->functionExists("cancelSubscription")) {
        $params = array("subscriptionID" => $subscriptionID);
        $cancelResult = $gateway->call("cancelSubscription", $params);
        if (is_array($cancelResult) && $cancelResult["status"] == "success") {
            WHMCS\Database\Capsule::table("tblhosting")->where("id", "=", $serviceID)->where("userid", "=", $userID)->update(array("subscriptionid" => ""));
            logActivity("Subscription Cancellation for ID " . $subscriptionID . " Successful - Service ID: " . $serviceID, $userID);
            logtransaction($paymentMethod, $cancelResult["rawdata"], "Subscription Cancellation Success");
            return true;
        }
        logActivity("Subscription Cancellation for ID " . $subscriptionID . " Failed - Service ID: " . $serviceID, $userID);
        logtransaction($paymentMethod, $cancelResult["rawdata"], "Subscription Cancellation Failed");
        throw new WHMCS\Exception\Gateways\SubscriptionCancellationFailed("Subscription Cancellation Failed");
    }
    throw new WHMCS\Exception\Gateways\SubscriptionCancellationNotSupported("Subscription Cancellation not Support by Gateway");
}
function getUpgradeRecurringValues($invoiceID)
{
    global $CONFIG;
    $invoiceID = (int) $invoiceID;
    if ($invoiceID == 0) {
        throw new InvalidArgumentException("Required value InvoiceID Missing");
    }
    $data = WHMCS\Database\Capsule::table("tblinvoiceitems")->join("tblupgrades", "tblupgrades.id", "=", "tblinvoiceitems.relid")->where("invoiceid", $invoiceID)->where("tblinvoiceitems.type", "Upgrade")->orderBy("tblinvoiceitems.id", "ASC")->first(array("tblinvoiceitems.relid", "tblinvoiceitems.taxed", "tblinvoiceitems.userid", "tblupgrades.relid as service", "tblupgrades.originalvalue", "tblupgrades.newvalue", "tblupgrades.orderid", "tblupgrades.type"));
    if (is_null($data)) {
        return false;
    }
    $relID = $data->service;
    $taxed = $data->taxed;
    $userID = $data->userid;
    if ($data->type == "package") {
        $packageData = explode(",", $data->newvalue);
        list($packageID, $billingCycle) = $packageData;
    } else {
        $packageData = new WHMCS\Service($relID);
        $packageID = $packageData->getData("packageid");
        $billingCycle = $packageData->getData("billingcycle");
    }
    $promoID = 0;
    $order = new WHMCS\Order();
    $order->setID($data->orderid);
    $promoCode = $order->getData("promocode");
    if ($promoCode) {
        $promoID = WHMCS\Database\Capsule::table("tblpromotions")->where("code", "=", $promoCode)->value("id");
    }
    if (!$relID || $billingCycle == "onetime" || $billingCycle == "free") {
        throw new InvalidArgumentException("Not Recurring or Missing ServiceID");
    }
    if ($billingCycle == "semiannually") {
        $cycle = "Semi-Annually";
    } else {
        $cycle = ucfirst($billingCycle);
    }
    $recurringAmount = recalcRecurringProductPrice($relID, $userID, $packageID, $cycle, "empty", $promoID);
    $invoice = new WHMCS\Invoice($invoiceID);
    $total = $invoice->getData("total");
    $taxRate = $invoice->getData("taxrate");
    $taxRate2 = $invoice->getData("taxrate2");
    $amountPaid = $invoice->getData("amountpaid");
    $firstPaymentAmount = $total - $amountPaid;
    $recurringCyclePeriod = getBillingCycleMonths($billingCycle);
    $recurringCycleUnits = "Months";
    if (12 <= $recurringCyclePeriod) {
        $recurringCyclePeriod = $recurringCyclePeriod / 12;
        $recurringCycleUnits = "Years";
    }
    if ($CONFIG["TaxType"] == "Exclusive" && $taxed) {
        if ($CONFIG["TaxL2Compound"]) {
            $recurringAmount = $recurringAmount + $recurringAmount * $taxRate / 100;
            $recurringAmount = $recurringAmount + $recurringAmount * $taxRate2 / 100;
        } else {
            $recurringAmount = $recurringAmount + $recurringAmount * $taxRate / 100 + $recurringAmount * $taxRate2 / 100;
        }
    }
    $recurringAmount = format_as_currency($recurringAmount);
    $invoiceDueDate = $invoice->getData("duedate");
    $invoiceDueDate = str_replace("-", "", $invoiceDueDate);
    $overdue = $invoiceDueDate < date("Ymd") ? true : false;
    $service = new WHMCS\Service($relID);
    $dateUntil = $service->getData("nextduedate");
    if ($dateUntil == "0000-00-00") {
        $dateUntil = getInvoicePayUntilDate($invoice->getData("duedate"), $billingCycle);
    }
    $currentServicePaidUntil = WHMCS\Carbon::createFromFormat("Y-m-d", $dateUntil);
    $newServiceStartDate = WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $invoice->getData("duedate"));
    if ($newServiceStartDate < $currentServicePaidUntil) {
        $days = $currentServicePaidUntil->diffInDays($newServiceStartDate);
        $returnData = array();
        $returnData["primaryserviceid"] = $relID;
        if ($firstPaymentAmount != $recurringAmount) {
            $returnData["firstpaymentamount"] = $firstPaymentAmount;
            $returnData["firstcycleperiod"] = $days;
            $returnData["firstcycleunits"] = "Days";
        }
        $returnData["recurringamount"] = $recurringAmount;
        $returnData["recurringcycleperiod"] = $recurringCyclePeriod;
        $returnData["recurringcycleunits"] = $recurringCycleUnits;
        $returnData["overdue"] = $overdue;
        return $returnData;
    }
    $message = "Delinquent service cannot be upgraded. Service ID: " . $service->getID() . ", upgrade invoice ID: " . $invoice->getID();
    throw new InvalidArgumentException($message);
}
function findInvoiceID($serviceID, $transID = "", $type = "Hosting")
{
    $allowedTypes = array("Addon", "Domain", "DomainTransfer", "DomainRegister", "Hosting");
    if (!in_array($type, $allowedTypes)) {
        return NULL;
    }
    $serviceID = (int) $serviceID;
    $invoiceID = WHMCS\Database\Capsule::table("tblinvoiceitems")->join("tblinvoices", "tblinvoices.id", "=", "tblinvoiceitems.invoiceid")->where("tblinvoiceitems.relid", "=", $serviceID)->where("tblinvoiceitems.type", "=", $type)->where("tblinvoices.status", "=", "Unpaid")->orderBy("tblinvoices.id")->value("tblinvoices.id");
    if (!$invoiceID) {
        $invoiceID = WHMCS\Database\Capsule::table("tblinvoiceitems")->join("tblinvoices", "tblinvoices.id", "=", "tblinvoiceitems.invoiceid")->where("tblinvoiceitems.relid", "=", $serviceID)->where("tblinvoiceitems.type", "=", $type)->where("tblinvoices.status", "=", "Paid")->orderBy("tblinvoices.id", "desc")->value("tblinvoices.id");
    }
    if (!$invoiceID && !empty($transID) && in_array($type, array("Domain", "DomainTransfer", "DomainRegistration", "Hosting"))) {
        $joinTable = "tblhosting";
        if ($type != "Hosting") {
            $joinTable = "tbldomains";
        }
        $invoiceID = WHMCS\Database\Capsule::table("tblinvoiceitems")->join("tblinvoices", "tblinvoices.id", "=", "tblinvoiceitems.invoiceid")->join($joinTable, $joinTable . ".id", "=", "tblinvoiceitems.relid")->where($joinTable . ".subscriptionid", "=", $transID)->where("tblinvoiceitems.type", "=", $type)->where("tblinvoices.status", "=", "Unpaid")->orderBy("tblinvoices.id")->value("tblinvoices.id");
        if (!$invoiceID) {
            $invoiceID = WHMCS\Database\Capsule::table("tblinvoiceitems")->join("tblinvoices", "tblinvoices.id", "=", "tblinvoiceitems.invoiceid")->join($joinTable, $joinTable . ".id", "=", "tblinvoiceitems.relid")->where($joinTable . ".subscriptionid", "=", $transID)->where("tblinvoiceitems.type", "=", $type)->where("tblinvoices.status", "=", "Paid")->orderBy("tblinvoices.id", "desc")->value("tblinvoices.id");
        }
    }
    return $invoiceID;
}
function outputDataArrayToString(array $data, $depth = 0)
{
    $logData = "";
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $logData .= str_repeat("    ", $depth) . (string) $key . " => \n";
            $logData .= outputDataArrayToString($value, $depth + 1);
        } else {
            $logData .= str_repeat("    ", $depth) . (string) $key . " => " . $value . "\n";
        }
    }
    return $logData;
}
function invoiceSetPayMethodRemoteToken($invoiceId, $remoteToken)
{
    try {
        WHMCS\Billing\Invoice::findOrFail($invoiceId)->setPayMethodRemoteToken($remoteToken);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
function invoiceDeletePayMethod($invoiceId)
{
    try {
        WHMCS\Billing\Invoice::findOrFail($invoiceId)->deletePayMethod();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
function invoiceConvertLocalCardToRemote($invoiceId, $remoteToken)
{
    try {
        WHMCS\Billing\Invoice::findOrFail($invoiceId)->convertLocalCardToRemote($remoteToken);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
function invoiceConvertLocalBankAccountToRemote($invoiceId, $remoteToken)
{
    try {
        WHMCS\Billing\Invoice::findOrFail($invoiceId)->convertLocalBankAccountToRemote($remoteToken);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
function invoiceSaveRemoteCard($invoiceId, $cardNumberOrLastFour, $cardType, $expiryDate, $remoteToken)
{
    try {
        WHMCS\Billing\Invoice::findOrFail($invoiceId)->saveRemoteCard($cardNumberOrLastFour, $cardType, $expiryDate, $remoteToken);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

?>