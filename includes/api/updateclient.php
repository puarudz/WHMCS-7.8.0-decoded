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
if (!function_exists("getClientsDetails")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("saveCustomFields")) {
    require ROOTDIR . "/includes/customfieldfunctions.php";
}
$apiresults = array();
$whmcs = App::self();
$skipValidation = $whmcs->get_req_var("skipvalidation");
$customFields = $whmcs->get_req_var("customfields");
if (!empty($_POST["clientid"])) {
    $clientid = $whmcs->get_req_var("clientid");
} else {
    $clientid = "";
}
$clientip = $whmcs->getFromRequest("clientip");
if ($clientemail) {
    $result = select_query("tblclients", "id", array("email" => $clientemail));
} else {
    $result = select_query("tblclients", "id", array("id" => $clientid));
}
$data = mysql_fetch_array($result);
$clientid = $data["id"];
if (!$clientid) {
    $apiresults = array("result" => "error", "message" => "Client ID Not Found");
} else {
    $client = WHMCS\User\Client::find($clientid);
    $cardDataPresent = (bool) App::getFromRequest("cardtype");
    $bankDataPresent = (bool) App::getFromRequest("bankcode");
    $clearCardData = (bool) App::getFromRequest("clearcreditcard");
    if ($clearCardData) {
        $cardDataPresent = false;
    }
    $ccPayMethods = NULL;
    $bankPayMethods = NULL;
    if ($cardDataPresent || $bankDataPresent) {
        $clientPayMethods = $client->payMethods;
        if ($cardDataPresent) {
            $ccPayMethods = $clientPayMethods->filter(function (WHMCS\Payment\PayMethod\Model $payMethod) {
                return $payMethod->payment->isLocalCreditCard();
            });
            if (1 < $ccPayMethods->count()) {
                $apiresults = array("result" => "error", "message" => "Multiple Credit Card Pay Methods Found");
                return NULL;
            }
        }
        if ($bankDataPresent) {
            $bankPayMethods = $clientPayMethods->filter(function (WHMCS\Payment\PayMethod\Model $payMethod) {
                return $payMethod->isBankAccount();
            });
            if (1 < $bankPayMethods->count()) {
                $apiresults = array("result" => "error", "message" => "Multiple Bank Account Pay Methods Found");
                return NULL;
            }
        }
        $apiresults["warning"] = "Credit card related parameters are now deprecated " . "and may be removed in a future version. Use AddPayMethod or UpdatePayMethod instead.";
    }
    if (($whmcs->get_req_var("clearcreditcard") || $whmcs->get_req_var("cardtype")) && !function_exists("updateCCDetails")) {
        require ROOTDIR . "/includes/ccfunctions.php";
    }
    if ($cardDataPresent && $ccPayMethods) {
        if (0 < $ccPayMethods->count()) {
            $payMethod = $ccPayMethods->offsetGet(0);
        } else {
            $payMethod = WHMCS\Payment\PayMethod\Adapter\CreditCard::factoryPayMethod($client, $client, "New Card");
        }
        updateCCDetails($clientid, App::getFromRequest("cardtype"), App::getFromRequest("cardnum"), App::getFromRequest("cvv"), App::getFromRequest("expdate"), App::getFromRequest("startdate"), App::getFromRequest("issuenumber"), "", "", "", $payMethod);
    }
    if ($bankDataPresent && $bankPayMethods) {
        if (0 < $bankPayMethods->count()) {
            $payMethod = $bankPayMethods->offsetGet(0);
        } else {
            $payMethod = WHMCS\Payment\PayMethod\Adapter\BankAccount::factoryPayMethod($client, $client, "New Account");
        }
        $payment = $payMethod->payment;
        $payment->setRoutingNumber(App::getFromRequest("bankcode"));
        $payment->setAccountNumber(App::getFromRequest("bankacct"));
        $payment->save();
    }
    if ($whmcs->get_req_var("clearcreditcard")) {
        $apiresults["warning"] = "Credit card related parameters are now deprecated " . "and may be removed in a future version. Use DeletePayMethod instead.";
        updateCCDetails($clientid, "", "", "", "", "", "", "", true);
    }
    if ($_POST["email"]) {
        $result = select_query("tblclients", "id", array("email" => $_POST["email"], "id" => array("sqltype" => "NEQ", "value" => $clientid)));
        $data = mysql_fetch_array($result);
        $result = select_query("tblcontacts", "id", array("email" => $_POST["email"], "subaccount" => "1"));
        $data2 = mysql_fetch_array($result);
        if ($data["id"] || $data2["id"]) {
            $apiresults = array("result" => "error", "message" => "Duplicate Email Address");
            return NULL;
        }
    }
    $passwordChanged = false;
    $oldClientsDetails = getClientsDetails($clientid);
    unset($oldClientsDetails["cctype"]);
    unset($oldClientsDetails["cclastfour"]);
    unset($oldClientsDetails["gatewayid"]);
    if (isset($_POST["taxexempt"])) {
        $_POST["taxexempt"] = $_POST["taxexempt"] ? 1 : 0;
    }
    if (isset($_POST["latefeeoveride"])) {
        $_POST["latefeeoveride"] = $_POST["latefeeoveride"] ? 1 : 0;
    }
    if (isset($_POST["overideduenotices"])) {
        $_POST["overideduenotices"] = $_POST["overideduenotices"] ? 1 : 0;
    }
    if (isset($_POST["separateinvoices"])) {
        $_POST["separateinvoices"] = $_POST["separateinvoices"] ? 1 : 0;
    }
    if (isset($_POST["disableautocc"])) {
        $_POST["disableautocc"] = $_POST["disableautocc"] ? 1 : 0;
    }
    $updatequery = "";
    $fieldsarray = array("firstname", "lastname", "companyname", "email", "address1", "address2", "city", "state", "postcode", "country", "phonenumber", "credit", "taxexempt", "notes", "status", "language", "currency", "groupid", "taxexempt", "latefeeoveride", "overideduenotices", "billingcid", "separateinvoices", "disableautocc", "datecreated", "securityqid", "lastlogin", "ip", "host");
    foreach ($fieldsarray as $fieldname) {
        if (isset($_POST[$fieldname])) {
            $updatequery .= (string) $fieldname . "='" . db_escape_string($_POST[$fieldname]) . "',";
        }
    }
    if ($_POST["password2"]) {
        $hasher = new WHMCS\Security\Hash\Password();
        $updatequery .= sprintf("password='%s',", $hasher->hash(WHMCS\Input\Sanitize::decode($_POST["password2"])));
        $passwordChanged = true;
    }
    if ($_POST["securityqans"]) {
        $updatequery .= "securityqans='" . encrypt($_POST["securityqans"]) . "',";
    }
    $query = "UPDATE tblclients SET " . substr($updatequery, 0, -1) . " WHERE id=" . (int) $clientid;
    $result = full_query($query);
    if ($customFields) {
        $customFields = safe_unserialize(base64_decode($customFields));
        if (!$skipValidation) {
            $validate = new WHMCS\Validate();
            $validate->validateCustomFields("client", "", false, $customFields);
            $customFieldsErrors = $validate->getErrors();
            if (count($customFieldsErrors)) {
                $error = implode(", ", $customFieldsErrors);
                $apiresults = array("result" => "error", "message" => $error);
                return NULL;
            }
        }
        saveCustomFields($clientid, $customFields, "client", true);
    }
    if ($paymentmethod) {
        clientChangeDefaultGateway($clientid, $paymentmethod);
    }
    if (App::isInRequest("marketingoptin")) {
        $optInStatus = (bool) App::getFromRequest("marketingoptin");
        try {
            if (!$client->marketingEmailsOptIn && $optInStatus) {
                $client->marketingEmailOptIn($clientip);
            } else {
                if ($client->marketingEmailsOptIn && !$optInStatus) {
                    $client->marketingEmailOptOut($clientip);
                }
            }
        } catch (Exception $e) {
        }
    }
    if (WHMCS\Config\Setting::getValue("TaxEUTaxValidation")) {
        $taxExempt = WHMCS\Billing\Tax\Vat::setTaxExempt($client);
        $client->save();
    }
    $newClientsDetails = getClientsDetails($clientid);
    unset($newClientsDetails["cctype"]);
    unset($newClientsDetails["cclastfour"]);
    unset($newClientsDetails["gatewayid"]);
    $hookValues = array_merge(array("userid" => $clientid, "isOptedInToMarketingEmails" => $client->isOptedInToMarketingEmails(), "olddata" => $oldClientsDetails), $newClientsDetails);
    run_hook("ClientEdit", $hookValues);
    $updateFieldsArray = array("firstname" => "First Name", "lastname" => "Last Name", "companyname" => "Company Name", "email" => "Email Address", "address1" => "Address 1", "address2" => "Address 2", "city" => "City", "state" => "State", "postcode" => "Postcode", "country" => "Country", "phonenumber" => "Phone Number", "securityqid" => "Security Question", "securityqans" => "Security Question Answer", "billingcid" => "Billing Contact", "groupid" => "Client Group", "language" => "Language", "currency" => "Currency", "status" => "Status", "defaultgateway" => "Default Payment Method");
    $updatedTickBoxArray = array("latefeeoveride" => "Late Fees Override", "overideduenotices" => "Overdue Notices", "taxexempt" => "Tax Exempt", "separateinvoices" => "Separate Invoices", "disableautocc" => "Disable CC Processing", "marketing_emails_opt_in" => "Marketing Emails Opt-in", "overrideautoclose" => "Auto Close");
    $changeList = array();
    foreach ($newClientsDetails as $key => $value) {
        if (!in_array($key, array_merge(array_keys($updateFieldsArray), array_keys($updatedTickBoxArray)))) {
            continue;
        }
        if (in_array($key, array("securityqans")) && $oldClientsDetails[$key] != $value) {
            $changeList[] = $updateFieldsArray[$key] . " Changed";
            continue;
        }
        if ($key == "securityqid" && $oldClientsDetails[$key] != $value) {
            if (!$value) {
                $changeList[] = "Security Question Removed";
            } else {
                $changeList[] = "Security Question Changed";
            }
            continue;
        }
        if (in_array($key, array_keys($updateFieldsArray)) && $value != $oldClientsDetails[$key]) {
            $oldValue = $oldClientsDetails[$key];
            $newValue = $value;
            $log = true;
            if ($key == "groupid") {
                $oldValue = $oldValue ? get_query_val("tblclientgroups", "groupname", array("id" => $oldValue)) : AdminLang::trans("global.none");
                $newValue = $newValue ? get_query_val("tblclientgroups", "groupname", array("id" => $newValue)) : AdminLang::trans("global.none");
            } else {
                if ($key == "currency") {
                    $oldValue = get_query_val("tblcurrencies", "code", array("id" => $oldValue));
                    $newValue = get_query_val("tblcurrencies", "code", array("id" => $newValue));
                } else {
                    if ($key == "securityqid") {
                        $oldValue = decrypt(get_query_val("tbladminsecurityquestions", "question", array("id" => $oldValue)));
                        $newValue = decrypt(get_query_val("tbladminsecurityquestions", "question", array("id" => $newValue)));
                        if ($oldValue == $newValue) {
                            $log = false;
                        }
                    }
                }
            }
            if ($log) {
                $changeList[] = $updateFieldsArray[$key] . ": '" . $oldValue . "' to '" . $newValue . "'";
            }
            continue;
        }
        if (in_array($key, array_keys($updatedTickBoxArray))) {
            if ($key == "overideduenotices") {
                $oldField = $oldClientsDetails[$key] ? "Disabled" : "Enabled";
                $newField = $value ? "Disabled" : "Enabled";
            } else {
                $oldField = $oldClientsDetails[$key] ? "Enabled" : "Disabled";
                $newField = $value ? "Enabled" : "Disabled";
            }
            if ($oldField != $newField) {
                $changeList[] = $updatedTickBoxArray[$key] . ": '" . $oldField . "' to '" . $newField . "'";
            }
            continue;
        }
    }
    if ($passwordChanged) {
        $changeList[] = "Password Changed";
    }
    if (!count($changeList)) {
        $changeList[] = "No Changes";
    }
    $changes = implode(", ", $changeList);
    logActivity("Client Profile Modified - " . $changes . " - User ID: " . $clientid, $clientid);
    $apiresults = array_merge($apiresults, array("result" => "success", "clientid" => $clientid));
}

?>