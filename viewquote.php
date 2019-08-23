<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require "includes/gatewayfunctions.php";
require "includes/quotefunctions.php";
require "includes/invoicefunctions.php";
require "includes/clientfunctions.php";
$whmcs = App::self();
$id = (int) $whmcs->get_req_var("id");
$pagetitle = $_LANG["quotestitle"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > " . "<a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > " . "<a href=\"clientarea.php?action=quotes\">" . $_LANG["quotes"] . "</a> > " . "<a href=\"viewquote.php?id=" . $id . "\">" . $pagetitle . "</a>";
initialiseClientArea($whmcs->get_lang("quotestitle") . $id, "", $breadcrumbnav);
if (!isset($_SESSION["uid"]) && !isset($_SESSION["adminid"])) {
    $goto = "viewquote";
    require "login.php";
    exit;
}
if (!checkContactPermission("quotes", true)) {
    redir("action=quotes", "clientarea.php");
    exit;
}
if ($action == "accept") {
    if (!$agreetos && $CONFIG["EnableTOSAccept"]) {
        $smarty->assign("agreetosrequired", true);
    } else {
        $validQuote = WHMCS\Database\Capsule::table("tblquotes")->where("id", $id)->where("userid", $_SESSION["uid"])->whereNotIn("stage", array("Draft", "Accepted"))->first();
        if ($validQuote) {
            update_query("tblquotes", array("stage" => "Accepted", "dateaccepted" => "now()"), array("id" => $id));
            logActivity("Quote Accepted - Quote ID: " . $id);
            $quote_data = (array) $validQuote;
            if ($quote_data["userid"]) {
                $clientsdetails = getClientsDetails($quote_data["userid"], "billing");
            } else {
                $clientsdetails = $quote_data;
            }
            $pdfdata = genQuotePDF($id);
            $messageArr = array("emailquote" => true, "quote_number" => $id, "quote_subject" => $quote_data["subject"], "quote_date_created" => $quote_data["datecreated"], "invoice_num" => "", "client_first_name" => $clientsdetails["firstname"], "client_last_name" => $clientsdetails["lastname"], "client_company_name" => $clientsdetails["companyname"], "client_email" => $clientsdetails["email"], "client_address1" => $clientsdetails["address1"], "client_address2" => $clientsdetails["address2"], "client_city" => $clientsdetails["city"], "client_state" => $clientsdetails["state"], "client_postcode" => $clientsdetails["postcode"], "client_country" => $clientsdetails["country"], "client_phonenumber" => $clientsdetails["phonenumber"], "client_id" => $clientsdetails["userid"], "client_language" => $clientsdetails["language"], "quoteattachmentdata" => $pdfdata);
            sendMessage("Quote Accepted", $_SESSION["uid"], $messageArr);
            sendAdminMessage("Quote Accepted Notification", array("quote_number" => $id, "quote_subject" => $quote_data["subject"], "quote_date_created" => $quote_data["datecreated"], "client_id" => $vars["userid"], "clientname" => $clientsdetails["firstname"] . " " . $clientsdetails["lastname"], "client_email" => $clientsdetails["email"], "client_company_name" => $clientsdetails["companyname"], "client_address1" => $clientsdetails["address1"], "client_address2" => $clientsdetails["address2"], "client_city" => $clientsdetails["city"], "client_state" => $clientsdetails["state"], "client_postcode" => $clientsdetails["postcode"], "client_country" => $clientsdetails["country"], "client_phonenumber" => $clientsdetails["phonenumber"], "client_ip" => $clientsdetails["ip"], "client_hostname" => $clientsdetails["host"]), "account");
            run_hook("AcceptQuote", array("quoteid" => $id, "invoiceid" => $invoiceid));
        } else {
            $smarty->assign("error", "on");
            $smarty->assign("invalidQuoteIdRequested", true);
            outputClientArea("viewquote", true);
            exit;
        }
    }
}
if (isset($_SESSION["adminid"])) {
    $result = select_query("tblquotes", "", array("id" => $id));
} else {
    $result = select_query("tblquotes", "", array("id" => $id, "userid" => $_SESSION["uid"], "stage" => array("sqltype" => "NEQ", "value" => "Draft")));
}
$data = mysql_fetch_array($result);
$id = $data["id"];
$stage = $data["stage"];
$userid = $data["userid"];
$date = $data["datecreated"];
$validuntil = $data["validuntil"];
$subtotal = $data["subtotal"];
$total = $data["total"];
$status = $data["status"];
$proposal = $data["proposal"];
$notes = $data["customernotes"];
$currency = $data["currency"];
if (!$id) {
    $smarty->assign("error", "on");
    $smarty->assign("invalidQuoteIdRequested", true);
    outputClientArea("viewquote", true);
    exit;
}
$smarty->assign("invalidQuoteIdRequested", false);
$currency = getCurrency($userid, $currency);
$date = fromMySQLDate($date, 0, 1);
$validuntil = fromMySQLDate($validuntil, 0, 1);
if ($userid) {
    $clientsdetails = getClientsDetails($userid, "billing");
} else {
    $clientsdetails = array();
    $clientsdetails["firstname"] = $data["firstname"];
    $clientsdetails["lastname"] = $data["lastname"];
    $clientsdetails["companyname"] = $data["companyname"];
    $clientsdetails["email"] = $data["email"];
    $clientsdetails["address1"] = $data["address1"];
    $clientsdetails["address2"] = $data["address2"];
    $clientsdetails["city"] = $data["city"];
    $clientsdetails["state"] = $data["state"];
    $clientsdetails["postcode"] = $data["postcode"];
    $clientsdetails["country"] = $data["country"];
    $clientsdetails["phonenumber"] = $data["phonenumber"];
}
if ($CONFIG["TaxEnabled"]) {
    $tax = $data["tax1"];
    $tax2 = $data["tax2"];
    $taxdata = getTaxRate(1, $clientsdetails["state"], $clientsdetails["country"]);
    $smarty->assign("taxname", $taxdata["name"]);
    $smarty->assign("taxrate", $taxdata["rate"]);
    $taxdata2 = getTaxRate(2, $clientsdetails["state"], $clientsdetails["country"]);
    $smarty->assign("taxname2", $taxdata2["name"]);
    $smarty->assign("taxrate2", $taxdata2["rate"]);
}
$countries = new WHMCS\Utility\Country();
$clientsdetails["country"] = $countries->getName($clientsdetails["country"]);
$smarty->assign("clientsdetails", $clientsdetails);
$smarty->assign("companyname", $CONFIG["CompanyName"]);
$smarty->assign("pagetitle", $_LANG["quotenumber"] . $id);
$smarty->assign("quoteid", $id);
$smarty->assign("quotenum", $id);
$smarty->assign("payto", nl2br($CONFIG["InvoicePayTo"]));
$smarty->assign("datecreated", $date);
$smarty->assign("datedue", $duedate);
$smarty->assign("subtotal", formatCurrency($subtotal));
$smarty->assign("discount", $discount . "%");
$smarty->assign("tax", formatCurrency($tax));
$smarty->assign("tax2", formatCurrency($tax2));
$smarty->assign("total", formatCurrency($total));
$smarty->assign("stage", $stage);
$smarty->assign("validuntil", $validuntil);
$quoteitems = array();
$result = select_query("tblquoteitems", "quantity,description,unitprice,discount,taxable", array("quoteid" => $id), "id", "ASC");
while ($data = mysql_fetch_array($result)) {
    list($qty, $description, $unitprice) = $data;
    $discountpc = $discount = $data[3];
    $taxed = $data[4] ? true : false;
    if ($qty && $qty != 1) {
        $description = $qty . " x " . $description . " @ " . $unitprice . $_LANG["invoiceqtyeach"];
        $amount = $qty * $unitprice;
    } else {
        $amount = $unitprice;
    }
    $discount = $amount * $discount / 100;
    if ($discount) {
        $amount -= $discount;
    }
    $quoteitems[] = array("description" => nl2br($description), "unitprice" => formatCurrency($unitprice), "discount" => 0 < $discount ? formatCurrency($discount) : "", "discountpc" => $discountpc, "amount" => formatCurrency($amount), "taxed" => $taxed);
}
$smarty->assign("id", $id);
$smarty->assign("quoteitems", $quoteitems);
$smarty->assign("proposal", nl2br($proposal));
$smarty->assign("notes", nl2br($notes));
$smarty->assign("accepttos", $CONFIG["EnableTOSAccept"]);
$smarty->assign("tosurl", $CONFIG["TermsOfService"]);
outputClientArea("viewquote", true, array("ClientAreaPageViewQuote"));

?>