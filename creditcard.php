<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require ROOTDIR . "/includes/ccfunctions.php";
require ROOTDIR . "/includes/clientfunctions.php";
require ROOTDIR . "/includes/gatewayfunctions.php";
require ROOTDIR . "/includes/invoicefunctions.php";
$clientArea = new WHMCS\ClientArea();
$clientArea->initPage();
$clientArea->requireLogin();
$clientArea->setPageTitle(Lang::trans("ordercheckout"));
$clientArea->setDisplayTitle(Lang::trans("creditcard"));
$clientArea->setTagLine("");
$clientArea->addToBreadCrumb("#", Lang::trans("ordercheckout"));
$clientArea->setTemplate("creditcard");
$invoiceid = (int) $whmcs->get_req_var("invoiceid");
$userId = (int) WHMCS\Session::get("uid");
if (!$userId || !$invoiceid) {
    redir("", "clientarea.php");
}
$client = WHMCS\User\Client::find($userId);
if (!$client) {
    redir("", "clientarea.php");
}
$invoice = new WHMCS\Invoice($invoiceid);
if (!$invoice->isAllowed()) {
    redir("", "clientarea.php");
}
$invoiceid = $invoice->getData("invoiceid");
$invoicenum = $invoice->getData("invoicenum");
$status = $invoice->getData("status");
$total = $invoice->getData("total");
$invoiceModel = WHMCS\Billing\Invoice::find($invoiceid);
if ($status != "Unpaid") {
    redir("", "clientarea.php");
}
$gateways = new WHMCS\Gateways();
$action = $whmcs->get_req_var("action");
$ccinfo = $whmcs->get_req_var("ccinfo");
$cctype = $whmcs->get_req_var("cctype");
$ccDescription = App::getFromRequest("ccdescription");
$ccnumber = $whmcs->get_req_var("ccnumber");
$ccExpiryDate = App::getFromRequest("ccexpirydate");
$ccexpirymonth = $ccexpiryyear = $ccstartmonth = $ccstartyear = "";
if ($ccExpiryDate) {
    $ccExpiryDate = WHMCS\Carbon::createFromCcInput($ccExpiryDate);
    $ccexpirymonth = $ccExpiryDate->month;
    $ccexpiryyear = $ccExpiryDate->year;
}
$ccStartDate = App::getFromRequest("ccstartdate");
if ($ccStartDate) {
    $ccStartDate = WHMCS\Carbon::createFromCcInput($ccStartDate);
    $ccstartmonth = $ccStartDate->month;
    $ccstartyear = $ccStartDate->year;
}
$ccissuenum = $whmcs->get_req_var("ccissuenum");
$nostore = $whmcs->get_req_var("nostore");
$cccvv = $whmcs->get_req_var("cccvv");
$cccvv2 = $whmcs->get_req_var("cccvv2");
$firstname = $whmcs->get_req_var("firstname");
$lastname = $whmcs->get_req_var("lastname");
$address1 = $whmcs->get_req_var("address1");
$address2 = $whmcs->get_req_var("address2");
$city = $whmcs->get_req_var("city");
$state = $whmcs->get_req_var("state");
$postcode = $whmcs->get_req_var("postcode");
$country = $whmcs->get_req_var("country");
$phonenumber = App::formatPostedPhoneNumber();
$userDetailsValidationError = false;
$params = NULL;
$errormessage = false;
$fromorderform = false;
if (WHMCS\Session::get("cartccdetail")) {
    $cartccdetail = unserialize(base64_decode(decrypt(WHMCS\Session::getAndDelete("cartccdetail"))));
    list($cctype, $ccnumber, $ccexpirymonth, $ccexpiryyear, $ccstartmonth, $ccstartyear, $ccissuenum, $cccvv, $nostore, $ccinfo) = $cartccdetail;
    $action = "submit";
    if (ccFormatNumbers($ccnumber)) {
        $ccinfo = "new";
    }
    $fromorderform = true;
}
$gateway = new WHMCS\Module\Gateway();
$gateway->load($invoice->getData("paymentmodule"));
if ($gateway->functionExists("credit_card_input")) {
    if (is_null($params)) {
        $params = getCCVariables($invoiceid);
    }
    $clientArea->assign("credit_card_input", $gateway->call("credit_card_input", $params));
}
if ($action == "submit") {
    if (!$fromorderform) {
        check_token();
    }
    if ($nostore && (!WHMCS\Config\Setting::getValue("CCAllowCustomerDelete") || $gateway->functionExists("storeremote"))) {
        $nostore = "";
    }
    $payMethod = NULL;
    $billingcid = App::getFromRequest("billingcontact");
    if (!$fromorderform) {
        if ($billingcid == "new") {
            $errormessage = checkDetailsareValid($userId, false, false, false, false);
        }
        if ($errormessage) {
            $userDetailsValidationError = true;
        }
        if ($gateway->functionExists("cc_validation")) {
            $params = array();
            $params["cardtype"] = $cctype;
            $params["cardnum"] = ccFormatNumbers($ccnumber);
            $params["cardexp"] = ccFormatDate(ccFormatNumbers($ccexpirymonth . $ccexpiryyear));
            $params["cardstart"] = ccFormatDate(ccFormatNumbers($ccstartmonth . $ccstartyear));
            $params["cardissuenum"] = ccFormatNumbers($ccissuenum);
            $errormessage = $gateway->call("cc_validation", $params);
            $params = NULL;
        } else {
            if ($ccinfo == "new") {
                $errormessage .= updateCCDetails("", $cctype, $ccnumber, $cccvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum, "", "", $gateway->getLoadedModule());
            }
            if ($cccvv2) {
                $cccvv = $cccvv2;
            }
            if (!$cccvv) {
                $errormessage .= "<li>" . $_LANG["creditcardccvinvalid"];
            }
        }
        if (!$errormessage) {
            if ($billingcid === "new") {
                $array = array("userid" => $userId, "firstname" => $firstname, "lastname" => $lastname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber);
                $billingcid = insert_query("tblcontacts", $array);
            }
            if ($ccinfo == "new") {
                $errormessage .= updateCCDetails($userId, $cctype, $ccnumber, $cccvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum, $nostore, "", $gateway->getLoadedModule(), $payMethod);
                if ($payMethod) {
                    $billingContact = $client->contacts->find($billingcid);
                    if ($billingContact) {
                        $payMethod->contact()->associate($billingContact);
                        $payMethod->save();
                    }
                }
            }
        }
    }
    if (!$errormessage) {
        $gatewayName = "";
        if (!$payMethod && $ccinfo && is_numeric($ccinfo)) {
            $payMethod = WHMCS\Payment\PayMethod\Model::findForClient($ccinfo, $client->id);
        }
        if ($payMethod) {
            $invoiceModel->payMethod()->associate($payMethod);
            $invoiceModel->save();
        } else {
            $payMethod = $invoiceModel->payMethod;
        }
        if ($payMethod) {
            $gatewayName = $payMethod->gateway_name;
        }
        $params = getCCVariables($invoiceid, $gatewayName, $payMethod);
        if (!$payMethod) {
            $payMethod = $params["payMethod"];
        }
        if ($ccinfo == "new") {
            $params["cardtype"] = getCardTypeByCardNumber($ccnumber);
            $params["cardnum"] = ccFormatNumbers($ccnumber);
            $params["cardexp"] = ccFormatDate(ccFormatNumbers($ccexpirymonth . $ccexpiryyear));
            $params["cardstart"] = ccFormatDate(ccFormatNumbers($ccstartmonth . $ccstartyear));
            $params["cardissuenum"] = ccFormatNumbers($ccissuenum);
            $params["gatewayid"] = get_query_val("tblclients", "gatewayid", array("id" => $userId));
            if ($payMethod && $payMethod->payment instanceof WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
                $params["gatewayid"] = $payMethod->payment->getRemoteToken();
            }
            $params["billingcontactid"] = $billingcid;
        }
        if (function_exists($params["paymentmethod"] . "_3dsecure")) {
            $params["cccvv"] = $cccvv;
            $buttoncode = call_user_func($params["paymentmethod"] . "_3dsecure", $params);
            $buttoncode = str_replace("<form", "<form target=\"3dauth\"", $buttoncode);
            $smartyvalues["code"] = $buttoncode;
            $smartyvalues["width"] = "400";
            $smartyvalues["height"] = "500";
            if ($buttoncode == "success" || $buttoncode == "declined") {
                $result = $buttoncode;
            } else {
                $clientArea->setTemplate("3dsecure");
                $clientArea->output();
                exit;
            }
        } else {
            if ($gateway->isTokenised() && $payMethod->isLocalCreditCard()) {
                $payment = $payMethod->payment;
                $newRemotePayMethod = WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($invoiceModel->client, $invoiceModel->client->billingContact, $payMethod->getDescription());
                $newRemotePayMethod->setGateway($gateway);
                updateCCDetails($userId, $payment->getCardType(), $payment->getCardNumber(), $cccvv, $payment->getExpiryDate()->toCreditCard(), $payment->getStartDate(), $payment->getIssueNumber(), "", "", $invoiceModel->paymentGateway, $newRemotePayMethod);
                $payMethod->delete();
                $payMethod = $newRemotePayMethod;
                $invoiceModel->payMethod()->associate($payMethod);
                $invoiceModel->save();
                $params = getCCVariables($invoiceid, $invoiceModel->paymentGateway, $payMethod);
            }
            $result = captureCCPayment($invoiceid, $cccvv, true, $payMethod);
        }
        if ($params["paymentmethod"] == "offlinecc") {
            sendAdminNotification("account", "Offline Credit Card Payment Submitted", "<p>An offline credit card payment has just been submitted.  Details are below:</p><p>Client ID: " . $userId . "<br />Invoice ID: " . $invoiceid . "</p>");
            redir("id=" . $invoiceid . "&offlinepaid=true", "viewinvoice.php");
        }
        if ($result == "success") {
            redir("id=" . $invoiceid . "&paymentsuccess=true", "viewinvoice.php");
        } else {
            $errormessage = "<li>" . $_LANG["creditcarddeclined"];
            $action = "";
            if ($ccinfo == "new" && $payMethod instanceof WHMCS\Payment\PayMethod\Model) {
                $payMethod->delete();
            }
        }
    }
}
$billingContactId = NULL;
if ($invoiceModel && $invoiceModel->payMethod && $invoiceModel->payMethod->getContactId()) {
    $billingContactId = $invoiceModel->payMethod->getContactId();
}
if (!$billingContactId) {
    $billingContactId = "billing";
}
$clientsdetails = getClientsDetails($userId, $billingContactId);
$cardtype = $clientsdetails["cctype"];
$cardlastfour = $clientsdetails["cclastfour"];
if (!$errormessage && $fromorderform) {
    $firstname = $clientsdetails["firstname"];
    $lastname = $clientsdetails["lastname"];
    $email = $clientsdetails["email"];
    $address1 = $clientsdetails["address1"];
    $address2 = $clientsdetails["address2"];
    $city = $clientsdetails["city"];
    $state = $clientsdetails["state"];
    $postcode = $clientsdetails["postcode"];
    $country = $clientsdetails["country"];
    $phonenumber = $clientsdetails["telephoneNumber"];
}
$invoiceData = $invoice->getOutput();
$existingClientCards = array();
$gatewayCards = $client->payMethods->creditCards()->validateGateways()->sortByExpiryDate()->filter(function (WHMCS\Payment\Contracts\PayMethodInterface $payMethod) use($gateway) {
    if ($payMethod->getType() === WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL && !in_array($gateway->getWorkflowType(), array(WHMCS\Module\Gateway::WORKFLOW_ASSISTED, WHMCS\Module\Gateway::WORKFLOW_REMOTE))) {
        return true;
    }
    $payMethodGateway = $payMethod->getGateway();
    return $payMethodGateway && $payMethodGateway->getLoadedModule() === $gateway->getLoadedModule();
});
$billingContacts = array(array("id" => 0, "firstname" => $client->firstName, "lastname" => $client->lastName, "companyname" => $client->companyName, "email" => $client->email, "address1" => $client->address1, "address2" => $client->address2, "city" => $client->city, "state" => $client->state, "postcode" => $client->postcode, "country" => $client->country, "countryname" => $client->countryName, "phonenumber" => $client->phoneNumber));
foreach ($client->contacts as $contact) {
    $billingContacts[$contact->id] = array("id" => $contact->id, "firstname" => $contact->firstName, "lastname" => $contact->lastName, "companyname" => $contact->companyName, "email" => $contact->email, "address1" => $contact->address1, "address2" => $contact->address2, "city" => $contact->city, "state" => $contact->state, "postcode" => $contact->postcode, "country" => $contact->country, "countryname" => $contact->countryName, "phonenumber" => $contact->phoneNumber);
}
$defaultCardKey = NULL;
$lowestOrder = NULL;
foreach ($gatewayCards as $key => $creditCardMethod) {
    if (is_null($lowestOrder) || $lowestOrder < $creditCardMethod->order_preference) {
        $lowestOrder = $creditCardMethod->order_preference;
        $defaultCardKey = $key;
    }
    $existingClientCards[$key] = getPayMethodCardDetails($creditCardMethod);
}
$existingCard = array("cardtype" => NULL, "cardlastfour" => NULL, "cardnum" => Lang::trans("nocarddetails"), "fullcardnum" => NULL, "expdate" => "", "startdate" => "", "issuenumber" => NULL, "gatewayid" => NULL, "billingcontactid" => NULL);
if (!empty($existingClientCards)) {
    $existingCard = $existingClientCards[$defaultCardKey];
}
$countryObject = new WHMCS\Utility\Country();
if (!$ccinfo) {
    if ($invoiceModel->payMethod && $invoiceModel->payMethod->gateway_name == $gateway->getLoadedModule()) {
        $ccinfo = $invoiceModel->payMethod->id;
    } else {
        if ($existingCard) {
            $ccinfo = $existingCard["payMethod"]->id;
        } else {
            if ($gatewayCards) {
                $ccinfo = $gatewayCards->first()->id;
            } else {
                $ccinfo = "new";
            }
        }
    }
}
$smartyvalues = array("firstname" => $firstname, "lastname" => $lastname, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "countryname" => $countryObject->getName($country), "countriesdropdown" => getCountriesDropDown($country), "phonenumber" => $phonenumber, "cardOnFile" => 0 < strlen($existingCard["cardlastfour"]), "addingNewCard" => $ccinfo == "new" || 0 >= strlen($existingCard["cardlastfour"]), "ccinfo" => $ccinfo, "cardtype" => $existingCard["cardtype"], "cardnum" => $existingCard["cardlastfour"], "existingCardType" => $existingCard["cardtype"], "existingCardLastFour" => $existingCard["cardlastfour"], "existingCardExpiryDate" => $existingCard["expdate"], "existingCardStartDate" => $existingCard["startdate"], "existingCardIssueNum" => $existingCard["issuenumber"], "defaultBillingContact" => $billingContacts[$client->billingContactId], "billingContacts" => $billingContacts, "existingCards" => $existingClientCards, "cctype" => $cctype, "ccdescription" => $ccDescription, "ccnumber" => $ccnumber, "ccexpirymonth" => $ccexpirymonth, "ccexpiryyear" => $ccexpiryyear < 2000 ? $ccexpiryyear + 2000 : $ccexpiryyear, "ccstartmonth" => $ccstartmonth, "ccstartyear" => $ccstartyear < 2000 ? $ccstartyear + 2000 : $ccstartyear, "ccissuenum" => $ccissuenum, "cccvv" => $cccvv, "errormessage" => $errormessage, "invoiceid" => $invoiceid, "invoicenum" => $invoicenum, "total" => $invoiceData["total"], "balance" => $invoiceData["balance"], "showccissuestart" => WHMCS\Config\Setting::getValue("ShowCCIssueStart"), "shownostore" => WHMCS\Config\Setting::getValue("CCAllowCustomerDelete") && !$gateway->functionExists("storeremote"), "allowClientsToRemoveCards" => WHMCS\Config\Setting::getValue("CCAllowCustomerDelete") && !$gateway->functionExists("storeremote"), "invoice" => $invoiceData, "invoiceitems" => $invoice->getLineItems(), "userDetailsValidationError" => $userDetailsValidationError, "billingcontact" => $billingcid);
$smartyvalues["months"] = $gateways->getCCDateMonths();
$smartyvalues["startyears"] = $gateways->getCCStartDateYears();
$smartyvalues["years"] = $gateways->getCCExpiryDateYears();
$smartyvalues["expiryyears"] = $smartyvalues["years"];
if (is_null($params)) {
    $params = getCCVariables($invoiceid);
}
$smartyvalues["remotecode"] = "";
if (function_exists($params["paymentmethod"] . "_remoteinput")) {
    $smartyvalues["remotecode"] = true;
}
$clientArea->addOutputHookFunction("ClientAreaPageCreditCardCheckout");
$clientArea->output();

?>