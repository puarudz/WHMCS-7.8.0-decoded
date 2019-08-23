<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function saveQuote($id = 0, $subject = "", $stage = "", $datecreated = "", $validuntil = "", $clienttype = "", $userid = 0, $firstname = "", $lastname = "", $companyname = "", $email = "", $address1 = "", $address2 = "", $city = "", $state = "", $postcode = "", $country = "", $phonenumber = "", $currency = 0, array $lineitems = array(), $proposal = "", $customernotes = "", $adminnotes = "", $updatepriceonly = false, $taxId = "")
{
    global $CONFIG;
    if (!$id) {
        $id = insert_query("tblquotes", array("subject" => $subject, "stage" => $stage, "datecreated" => toMySQLDate($datecreated), "validuntil" => toMySQLDate($validuntil), "lastmodified" => "now()"));
        $newQuote = true;
    } else {
        $newQuote = false;
    }
    if ($clienttype == "new") {
        $userid = 0;
        $fortax_state = $state;
        $fortax_country = $country;
        $isClientTaxExempt = false;
        if ($taxId) {
            $isClientTaxExempt = WHMCS\Billing\Tax\Vat::validateNumber($taxId) && WHMCS\Config\Setting::getValue("TaxEUTaxExempt");
        }
    } else {
        $clientsdetails = getClientsDetails($userid);
        $fortax_state = $clientsdetails["state"];
        $fortax_country = $clientsdetails["country"];
        $isClientTaxExempt = $clientsdetails["taxexempt"];
    }
    $taxlevel1 = getTaxRate(1, $fortax_state, $fortax_country);
    $taxlevel2 = getTaxRate(2, $fortax_state, $fortax_country);
    $subtotal = 0;
    $taxableamount = 0;
    $tax1 = 0;
    $tax2 = 0;
    if ($lineitems) {
        foreach ($lineitems as $linedata) {
            $line_id = $linedata["id"];
            $line_desc = $linedata["desc"];
            $line_qty = $linedata["qty"];
            $line_up = $linedata["up"];
            $line_discount = $linedata["discount"];
            $line_taxable = $linedata["taxable"];
            if ($line_id) {
                update_query("tblquoteitems", array("description" => $line_desc, "quantity" => $line_qty, "unitprice" => $line_up, "discount" => $line_discount, "taxable" => $line_taxable), array("id" => $line_id));
            } else {
                insert_query("tblquoteitems", array("quoteid" => $id, "description" => $line_desc, "quantity" => $line_qty, "unitprice" => $line_up, "discount" => $line_discount, "taxable" => $line_taxable));
            }
            $lineitemamount = $line_qty * $line_up * (1 - $line_discount / 100);
            $subtotal += $lineitemamount;
            if ($line_taxable) {
                $taxableamount += $lineitemamount;
            }
        }
    } else {
        $result = select_query("tblquoteitems", "", array("quoteid" => $id), "id", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $line_qty = $data["quantity"];
            $line_unitprice = $data["unitprice"];
            $line_discount = $data["discount"];
            $line_taxable = $data["taxable"];
            $lineitemamount = round($line_qty * $line_unitprice * (1 - $line_discount / 100), 2);
            $subtotal += $lineitemamount;
            if ($line_taxable) {
                $taxableamount += $lineitemamount;
            }
        }
    }
    if (WHMCS\Config\Setting::getValue("TaxEnabled")) {
        if (0 < $taxlevel1["rate"] && !$isClientTaxExempt) {
            if ($CONFIG["TaxType"] == "Inclusive") {
                $tax1 = format_as_currency($taxableamount / (100 + $taxlevel1["rate"]) * $taxlevel1["rate"]);
            } else {
                $tax1 = format_as_currency($taxableamount * $taxlevel1["rate"] / 100);
            }
        }
        if (0 < $taxlevel2["rate"] && !$isClientTaxExempt) {
            if ($CONFIG["TaxType"] == "Inclusive") {
                $tax2 = format_as_currency($taxableamount / (100 + $taxlevel2["rate"]) * $taxlevel2["rate"]);
            } else {
                if ($CONFIG["TaxL2Compound"]) {
                    $tax2 = format_as_currency(($taxableamount + $tax1) * $taxlevel2["rate"] / 100);
                } else {
                    $tax2 = format_as_currency($taxableamount * $taxlevel2["rate"] / 100);
                }
            }
        }
    }
    if ($CONFIG["TaxType"] == "Inclusive") {
        $total = $subtotal;
        $subtotal = $subtotal - $tax1 - $tax2;
    } else {
        $total = $subtotal + $tax1 + $tax2;
    }
    if ($updatepriceonly) {
        update_query("tblquotes", array("subtotal" => $subtotal, "tax1" => $tax1, "tax2" => $tax2, "total" => $total), array("id" => $id));
    } else {
        update_query("tblquotes", array("subject" => $subject, "stage" => $stage, "datecreated" => toMySQLDate($datecreated), "validuntil" => toMySQLDate($validuntil), "lastmodified" => "now()", "userid" => $userid, "firstname" => $firstname, "lastname" => $lastname, "companyname" => $companyname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber, "tax_id" => $taxId, "currency" => $currency, "subtotal" => $subtotal, "tax1" => $tax1, "tax2" => $tax2, "total" => $total, "proposal" => $proposal, "customernotes" => $customernotes, "adminnotes" => $adminnotes), array("id" => $id));
    }
    if ($newQuote) {
        run_hook("QuoteCreated", array("quoteid" => $id, "status" => $stage));
    } else {
        run_hook("QuoteStatusChange", array("quoteid" => $id, "status" => $stage));
    }
    return $id;
}
function genQuotePDF($id)
{
    global $whmcs;
    global $CONFIG;
    global $_LANG;
    global $currency;
    $companyname = $CONFIG["CompanyName"];
    $companyurl = $CONFIG["Domain"];
    $companyaddress = $CONFIG["InvoicePayTo"];
    $companyaddress = explode("\n", $companyaddress);
    $quotenumber = $id;
    $result = select_query("tblquotes", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $subject = $data["subject"];
    $stage = $data["stage"];
    $datecreated = fromMySQLDate($data["datecreated"]);
    $validuntil = fromMySQLDate($data["validuntil"]);
    $userid = $data["userid"];
    $proposal = $data["proposal"] ? $data["proposal"] . "\n" : "";
    $notes = $data["customernotes"] ? $data["customernotes"] . "\n" : "";
    $currency = getCurrency($userid, $data["currency"]);
    if ($userid) {
        getUsersLang($userid);
        $stage = getQuoteStageLang($stage);
        $clientsdetails = getClientsDetails($userid);
    } else {
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
    $taxlevel1 = getTaxRate(1, $clientsdetails["state"], $clientsdetails["country"]);
    $taxlevel2 = getTaxRate(2, $clientsdetails["state"], $clientsdetails["country"]);
    $countries = new WHMCS\Utility\Country();
    $clientsdetails["country"] = $countries->getName($clientsdetails["country"]);
    $subtotal = formatCurrency($data["subtotal"]);
    $tax1 = formatCurrency($data["tax1"]);
    $tax2 = formatCurrency($data["tax2"]);
    $total = formatCurrency($data["total"]);
    $lineitems = array();
    $result = select_query("tblquoteitems", "", array("quoteid" => $id), "id", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $line_id = $data["id"];
        $line_desc = $data["description"];
        $line_qty = $data["quantity"];
        $line_unitprice = $data["unitprice"];
        $line_discount = $data["discount"];
        $line_taxable = $data["taxable"];
        $line_total = format_as_currency($line_qty * $line_unitprice * (1 - $line_discount / 100));
        $lineitems[] = array("id" => $line_id, "description" => htmlspecialchars(WHMCS\Input\Sanitize::decode($line_desc)), "qty" => $line_qty, "unitprice" => $line_unitprice, "discount" => $line_discount, "taxable" => $line_taxable, "total" => formatCurrency($line_total));
    }
    $tplvars = array();
    $tplvars["companyname"] = $companyname;
    $tplvars["companyurl"] = $companyurl;
    $tplvars["companyaddress"] = $companyaddress;
    $tplvars["paymentmethod"] = $paymentmethod;
    $tplvars["quotenumber"] = $quotenumber;
    $tplvars["subject"] = $subject;
    $tplvars["stage"] = $stage;
    $tplvars["datecreated"] = $datecreated;
    $tplvars["validuntil"] = $validuntil;
    $tplvars["userid"] = $userid;
    $tplvars["clientsdetails"] = $clientsdetails;
    $tplvars["proposal"] = $proposal;
    $tplvars["notes"] = $notes;
    $tplvars["taxlevel1"] = $taxlevel1;
    $tplvars["taxlevel2"] = $taxlevel2;
    $tplvars["subtotal"] = $subtotal;
    $tplvars["tax1"] = $tax1;
    $tplvars["tax2"] = $tax2;
    $tplvars["total"] = $total;
    $tplvars = WHMCS\Input\Sanitize::decode($tplvars);
    $tplvars["lineitems"] = $lineitems;
    $tplvars["pdfFont"] = WHMCS\Config\Setting::getValue("TCPDFFont");
    $invoice = new WHMCS\Invoice();
    $invoice->pdfCreate($_LANG["quotenumber"] . $id);
    $invoice->pdfAddPage("quotepdf.tpl", $tplvars);
    $pdfdata = $invoice->pdfOutput();
    return $pdfdata;
}
function sendQuotePDF($id)
{
    global $CONFIG;
    global $_LANG;
    global $currency;
    $result = select_query("tblquotes", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $subject = $data["subject"];
    $stage = $data["stage"];
    $datecreated = fromMySQLDate($data["datecreated"]);
    $validuntil = fromMySQLDate($data["validuntil"]);
    $userid = $data["userid"];
    $notes = $data["customernotes"] . "\n";
    if ($userid) {
        $clientsdetails = getClientsDetails($userid);
    } else {
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
    $pdfdata = genquotepdf($id);
    $sysurl = App::getSystemUrl();
    $quote_link = "<a href=\"" . $sysurl . "viewquote.php?id=" . $id . "\">" . $sysurl . "viewquote.php?id=" . $id . "</a>";
    $result = sendMessage("Quote Delivery with PDF", $userid, array("emailquote" => true, "quote_number" => $id, "quote_subject" => $subject, "quote_date_created" => $datecreated, "quote_valid_until" => $validuntil, "client_id" => $userid, "client_first_name" => $clientsdetails["firstname"], "client_last_name" => $clientsdetails["lastname"], "client_company_name" => $clientsdetails["companyname"], "client_email" => $clientsdetails["email"], "client_address1" => $clientsdetails["address1"], "client_address2" => $clientsdetails["address2"], "client_city" => $clientsdetails["city"], "client_state" => $clientsdetails["state"], "client_postcode" => $clientsdetails["postcode"], "client_country" => $clientsdetails["country"], "client_phonenumber" => $clientsdetails["phonenumber"], "client_language" => $clientsdetails["language"], "quoteattachmentdata" => $pdfdata, "quote_link" => $quote_link));
    if ($result === true) {
        update_query("tblquotes", array("stage" => "Delivered"), array("id" => $id));
        return true;
    }
    return $result;
}
function convertQuotetoInvoice($id, $invoicetype = NULL, $invoiceduedate = NULL, $depositpercent = 0, $depositduedate = NULL, $finalduedate = NULL, $sendemail = false)
{
    global $CONFIG;
    global $_LANG;
    $result = select_query("tblquotes", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $companyname = $data["companyname"];
    $email = $data["email"];
    $address1 = $data["address1"];
    $address2 = $data["address2"];
    $city = $data["city"];
    $state = $data["state"];
    $postcode = $data["postcode"];
    $country = $data["country"];
    $phonenumber = $data["phonenumber"];
    $taxId = $data["tax_id"];
    $currency = $data["currency"];
    if ($userid) {
        getUsersLang($userid);
        $clientsdetails = getClientsDetails($userid);
    } else {
        if (!function_exists("addClient")) {
            require ROOTDIR . "/clientfunctions.php";
        }
        $_SESSION["currency"] = $currency;
        $userid = addClient($firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, substr(md5($id), 0, 10), 0, "", "on", array("tax_id" => $taxId));
        getUsersLang($userid);
        $clientsdetails = getClientsDetails($userid);
    }
    $taxExempt = $clientsdetails["taxexempt"];
    $taxRate = $taxRate2 = NULL;
    if ($taxExempt) {
        $taxRate = $taxRate2 = 0;
    }
    $subtotal = $data["subtotal"];
    $tax1 = $data["tax1"];
    $tax2 = $data["tax2"];
    $total = $data["total"];
    $duedate = $finaldate = "";
    if ($invoicetype == "deposit") {
        if ($depositduedate) {
            $duedate = toMySQLDate($depositduedate);
        }
        $finaldate = $finalduedate ? toMySQLDate($finalduedate) : date("Y-m-d");
    } else {
        if ($invoiceduedate) {
            $duedate = toMySQLDate($invoiceduedate);
        }
    }
    $finalinvoiceid = 0;
    $invoice = WHMCS\Billing\Invoice::newInvoice($userid, NULL, $taxRate, $taxRate2);
    if ($duedate) {
        $invoice->dateDue = $duedate;
    }
    $invoice->status = "Unpaid";
    $invoice->tax1 = $tax1;
    $invoice->tax2 = $tax2;
    $invoice->subtotal = $subtotal;
    $invoice->total = $total;
    $invoice->adminNotes = Lang::trans("quoteref") . $id;
    $invoice->save();
    $invoiceid = $invoice->id;
    if ($finaldate) {
        $finalInvoice = WHMCS\Billing\Invoice::newInvoice($userid, NULL, $taxRate, $taxRate2);
        if ($finaldate) {
            $finalInvoice->dateDue = $finaldate;
        }
        $finalInvoice->status = "Unpaid";
        $finalInvoice->tax1 = $tax1;
        $finalInvoice->tax2 = $tax2;
        $finalInvoice->subtotal = $subtotal;
        $finalInvoice->total = $total;
        $finalInvoice->adminNotes = Lang::trans("quoteref") . $id;
        $finalInvoice->save();
        $finalinvoiceid = $finalInvoice->id;
    }
    $result = select_query("tblquoteitems", "", array("quoteid" => $id), "id", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $line_id = $data["id"];
        $line_desc = $data["description"];
        $line_qty = $data["quantity"];
        $line_unitprice = $data["unitprice"];
        $line_discount = $data["discount"];
        $line_taxable = $data["taxable"];
        $line_total = format_as_currency($line_qty * $line_unitprice * (1 - $line_discount / 100));
        $lineitemdesc = (string) $line_qty . " x " . $line_desc . " @ " . $line_unitprice;
        if (0 < $line_discount) {
            $lineitemdesc .= " - " . $line_discount . "% " . $_LANG["orderdiscount"];
        }
        if ($finalinvoiceid) {
            $originalamount = $line_total;
            $line_total = $originalamount * $depositpercent / 100;
            $final_amount = $originalamount - $line_total;
            insert_query("tblinvoiceitems", array("invoiceid" => $finalinvoiceid, "userid" => $userid, "description" => $lineitemdesc . " (" . (100 - $depositpercent) . "% " . $_LANG["quotefinalpayment"] . ")", "amount" => $final_amount, "taxed" => $line_taxable));
            $lineitemdesc .= " (" . $depositpercent . "% " . $_LANG["quotedeposit"] . ")";
        }
        insert_query("tblinvoiceitems", array("invoiceid" => $invoiceid, "userid" => $userid, "description" => $lineitemdesc, "amount" => $line_total, "taxed" => $line_taxable));
    }
    if (!function_exists("updateInvoiceTotal")) {
        require ROOTDIR . "/includes/invoicefunctions.php";
    }
    updateInvoiceTotal($invoiceid);
    if ($finalinvoiceid) {
        updateInvoiceTotal($finalinvoiceid);
    }
    if (defined("APICALL")) {
        $source = "api";
        $user = WHMCS\Session::get("adminid");
    } else {
        if (defined("ADMINAREA")) {
            $source = "adminarea";
            $user = WHMCS\Session::get("adminid");
        } else {
            $source = "clientarea";
            $user = WHMCS\Session::get("uid");
        }
    }
    $invoiceArr = array("source" => $source, "user" => $user, "invoiceid" => $invoiceid, "status" => "Unpaid");
    run_hook("InvoiceCreation", $invoiceArr);
    if ($sendemail) {
        run_hook("InvoiceCreationPreEmail", $invoiceArr);
        sendMessage("Invoice Created", $invoiceid);
    }
    run_hook("InvoiceCreated", $invoiceArr);
    if ($finalinvoiceid) {
        $invoiceArr = array("source" => $source, "user" => $user, "invoiceid" => $finalinvoiceid, "status" => "Unpaid");
        run_hook("InvoiceCreation", $invoiceArr);
        if ($sendemail) {
            run_hook("InvoiceCreationPreEmail", $invoiceArr);
            sendMessage("Invoice Created", $finalinvoiceid);
        }
        run_hook("InvoiceCreated", $invoiceArr);
    }
    update_query("tblquotes", array("userid" => $userid, "stage" => "Accepted", "dateaccepted" => WHMCS\Carbon::now()->toDateString()), array("id" => $id));
    return $invoiceid;
}
function getQuoteStageLang($stage)
{
    global $_LANG;
    $translation = $_LANG["quotestage" . strtolower(str_replace(" ", "", $stage))];
    if (!$translation) {
        $translation = $stage;
    }
    return $translation;
}

?>