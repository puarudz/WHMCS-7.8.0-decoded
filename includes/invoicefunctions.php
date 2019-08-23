<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getInvoiceStatusColour($status, $clientarea = true)
{
    if (!$clientarea) {
        global $aInt;
        if ($status == "Draft") {
            $status = "<span class=\"textgrey\">" . $aInt->lang("status", "draft") . "</span>";
        } else {
            if ($status == "Unpaid") {
                $status = "<span class=\"textred\">" . $aInt->lang("status", "unpaid") . "</span>";
            } else {
                if ($status == "Paid") {
                    $status = "<span class=\"textgreen\">" . $aInt->lang("status", "paid") . "</span>";
                } else {
                    if ($status == "Cancelled") {
                        $status = "<span class=\"textgrey\">" . $aInt->lang("status", "cancelled") . "</span>";
                    } else {
                        if ($status == "Refunded") {
                            $status = "<span class=\"textblack\">" . $aInt->lang("status", "refunded") . "</span>";
                        } else {
                            if ($status == "Collections") {
                                $status = "<span class=\"textgold\">" . $aInt->lang("status", "collections") . "</span>";
                            } else {
                                if ($status == "Payment Pending") {
                                    $status = "<span class=\"textgreen\">" . AdminLang::trans("status.paymentpending") . "</span>";
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        global $_LANG;
        if ($status == "Unpaid") {
            $status = "<span class=\"textred\">" . $_LANG["invoicesunpaid"] . "</span>";
        } else {
            if ($status == "Paid") {
                $status = "<span class=\"textgreen\">" . $_LANG["invoicespaid"] . "</span>";
            } else {
                if ($status == "Cancelled") {
                    $status = "<span class=\"textgrey\">" . $_LANG["invoicescancelled"] . "</span>";
                } else {
                    if ($status == "Refunded") {
                        $status = "<span class=\"textblack\">" . $_LANG["invoicesrefunded"] . "</span>";
                    } else {
                        if ($status == "Collections") {
                            $status = "<span class=\"textgold\">" . $_LANG["invoicescollections"] . "</span>";
                        } else {
                            if ($status == "Payment Pending") {
                                $status = "<span class=\"textgreen\">" . $_LANG["invoicesPaymentPending"] . "</span>";
                            }
                        }
                    }
                }
            }
        }
    }
    return $status;
}
function getInvoicePayUntilDate($nextduedate, $billingcycle, $fulldate = "")
{
    $year = substr($nextduedate, 0, 4);
    $month = substr($nextduedate, 5, 2);
    $day = substr($nextduedate, 8, 2);
    $daysadjust = $months = 0;
    $months = is_numeric($billingcycle) ? $billingcycle * 12 : getBillingCycleMonths($billingcycle);
    if (!$fulldate) {
        $daysadjust = 1;
    }
    $new_time = mktime(0, 0, 0, $month + $months, $day - $daysadjust, $year);
    $invoicepayuntildate = $billingcycle != "One Time" ? date("Y-m-d", $new_time) : "";
    return $invoicepayuntildate;
}
function addTransaction($userid, $currencyid, $description, $amountin, $fees, $amountout, $gateway = "", $transid = "", $invoiceid = "", $date = "", $refundid = "", $rate = "")
{
    $date = $date ? toMySQLDate($date) . date(" H:i:s") : "now()";
    if ($userid) {
        $currency = getCurrency($userid);
        $currencyid = $currency["id"];
    }
    if (!is_numeric($rate)) {
        if (empty($currencyid)) {
            $currency = getCurrency();
            $currencyid = $currency["id"];
        }
        $result = select_query("tblcurrencies", "rate", array("id" => $currencyid));
        $data = mysql_fetch_array($result);
        $rate = $data["rate"];
    }
    if ($userid) {
        $currencyid = 0;
    }
    $array = array("userid" => $userid, "currency" => $currencyid, "gateway" => $gateway, "date" => $date, "description" => $description, "amountin" => $amountin, "fees" => $fees, "amountout" => $amountout, "rate" => $rate, "transid" => $transid, "invoiceid" => $invoiceid, "refundid" => $refundid);
    $saveid = insert_query("tblaccounts", $array);
    logActivity("Added Transaction - Transaction ID: " . $saveid, $userid);
    $array["id"] = $saveid;
    run_hook("AddTransaction", $array);
}
function updateInvoiceTotal($id)
{
    global $CONFIG;
    $taxsubtotal = 0;
    $nontaxsubtotal = 0;
    $result = select_query("tblinvoices", "userid,credit,taxrate,taxrate2", array("id" => $id));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $credit = $data["credit"];
    $taxrate = $data["taxrate"];
    $taxrate2 = $data["taxrate2"];
    if (!function_exists("getClientsDetails")) {
        require_once dirname(__FILE__) . "/clientfunctions.php";
    }
    $clientsdetails = getClientsDetails($userid);
    $taxCalculator = new WHMCS\Billing\Tax();
    $taxCalculator->setIsInclusive($CONFIG["TaxType"] == "Inclusive")->setIsCompound($CONFIG["TaxL2Compound"]);
    if (is_numeric($taxrate)) {
        $taxCalculator->setLevel1Percentage($taxrate);
    }
    if (is_numeric($taxrate2)) {
        $taxCalculator->setLevel2Percentage($taxrate2);
    }
    $tax = $tax2 = 0;
    $result = select_query("tblinvoiceitems", "", array("invoiceid" => $id));
    while ($data = mysql_fetch_array($result)) {
        if ($data["taxed"] == "1" && $CONFIG["TaxEnabled"] == "on" && !$clientsdetails["taxexempt"]) {
            if (WHMCS\Config\Setting::getValue("TaxPerLineItem")) {
                $taxCalculator->setTaxBase($data["amount"]);
                $tax += $taxCalculator->getLevel1TaxTotal();
                $tax2 += $taxCalculator->getLevel2TaxTotal();
                $taxsubtotal += $taxCalculator->getTotalBeforeTaxes();
            } else {
                $taxsubtotal += $data["amount"];
            }
        } else {
            $nontaxsubtotal += $data["amount"];
        }
    }
    if (!WHMCS\Config\Setting::getValue("TaxPerLineItem")) {
        $taxCalculator->setTaxBase($taxsubtotal);
        $tax = $taxCalculator->getLevel1TaxTotal();
        $tax2 = $taxCalculator->getLevel2TaxTotal();
        $taxsubtotal = $taxCalculator->getTotalBeforeTaxes();
    }
    $subtotal = $nontaxsubtotal + $taxsubtotal;
    $total = $subtotal + $tax + $tax2;
    if (0 < $credit) {
        if ($total < $credit) {
            $total = 0;
            $remainingcredit = $total - $credit;
        } else {
            $total -= $credit;
        }
    }
    update_query("tblinvoices", array("subtotal" => $subtotal, "tax" => $tax, "tax2" => $tax2, "total" => $total), array("id" => $id));
    run_hook("UpdateInvoiceTotal", array("invoiceid" => $id));
}
function addInvoicePayment($invoiceId, $transactionId, $amount, $fees, $gateway, $noEmail = false, $date = NULL)
{
    try {
        $invoice = WHMCS\Billing\Invoice::findOrFail($invoiceId);
        if (!$amount) {
            $amount = $invoice->balance;
            if ($amount <= 0) {
                throw new WHMCS\Exception\Module\NotServicable("Invoice Amount Invalid");
            }
        }
        if ($date && !$date instanceof WHMCS\Carbon) {
            $date = WHMCS\Carbon::createFromFormat("Y-m-d", toMySQLDate($date));
        }
        if (!$date instanceof WHMCS\Carbon) {
            $date = NULL;
        }
        return $invoice->addPayment($amount, $transactionId, $fees, $gateway, (bool) $noEmail, $date);
    } catch (Exception $e) {
        return false;
    }
}
function removeOverpaymentCredit($userid, $transid, $amount)
{
    $where = array("id" => $userid);
    $result = select_query("tblclients", "credit", $where);
    $data = mysql_fetch_array($result);
    $creditBalance = $data["credit"] - $amount;
    if ($creditBalance < 0) {
        $creditBalance = 0;
    }
    $update = array("credit" => $creditBalance);
    update_query("tblclients", $update, $where);
    $where = array("id" => $transid, "userid" => $userid);
    $result = select_query("tblaccounts", "invoiceid", $where);
    $data = mysql_fetch_array($result);
    if (isset($data["invoiceid"])) {
        $invoiceid = $data["invoiceid"];
        $insert = array("clientid" => $userid, "date" => "now()", "description" => "Removal of Credit from Invoice #" . $invoiceid, "amount" => "-" . $amount, "relid" => $transid);
        insert_query("tblcredit", $insert);
        logActivity("Removal of Credit from Invoice #" . $invoiceid, $userid);
    }
}
function refundInvoicePayment($transid, $amount, $sendtogateway, $addascredit = "", $sendemail = true, $refundtransid = "", $reverse = false)
{
    try {
        $transaction = WHMCS\Billing\Payment\Transaction::findOrFail($transid);
        $transid = $transaction->id;
        $invoiceid = $transaction->invoiceId;
        $gateway = $transaction->paymentGateway;
        $fullamount = $transaction->amountIn;
        $fees = $transaction->fees;
        $gatewaytransid = $transaction->transactionId;
        $rate = $transaction->exchangeRate;
        $userid = $transaction->clientId;
    } catch (Exception $e) {
        return "amounterror";
    }
    if (!$userid && $transaction->invoiceId) {
        $userid = $transaction->invoice->clientId;
    }
    $gateway = WHMCS\Gateways::makeSafeName($gateway);
    $result = select_query("tblaccounts", "SUM(amountout),SUM(fees)", array("refundid" => $transid));
    $data = mysql_fetch_array($result);
    list($alreadyrefunded, $alreadyrefundedfees) = $data;
    $fullamount -= $alreadyrefunded;
    $fees -= $alreadyrefundedfees * -1;
    if ($fees <= 0) {
        $fees = 0;
    }
    $result = select_query("tblaccounts", "SUM(amountin),SUM(amountout)", array("invoiceid" => $invoiceid));
    $data = mysql_fetch_array($result);
    list($invoicetotalpaid, $invoicetotalrefunded) = $data;
    if (!$amount) {
        $amount = $fullamount;
    }
    if (!$amount || $fullamount < $amount) {
        return "amounterror";
    }
    $amount = format_as_currency($amount);
    if ($addascredit) {
        addtransaction($userid, 0, "Refund of Transaction ID " . $gatewaytransid . " to Credit Balance", 0, $fees * -1, $amount, "", "", $invoiceid, "", $transid, $rate);
        addtransaction($userid, 0, "Credit from Refund of Invoice ID " . $invoiceid, $amount, $fees, 0, "", "", "", "", "", "");
        logActivity("Refunded Invoice Payment to Credit Balance - Invoice ID: " . $invoiceid, $userid);
        insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Credit from Refund of Invoice ID " . $invoiceid, "amount" => $amount));
        update_query("tblclients", array("credit" => "+=" . $amount), array("id" => (int) $userid));
        if ($invoicetotalpaid - $invoicetotalrefunded - $amount <= 0) {
            update_query("tblinvoices", array("status" => "Refunded"), array("id" => $invoiceid));
            run_hook("InvoiceRefunded", array("invoiceid" => $invoiceid));
        }
        if ($sendemail) {
            sendMessage("Invoice Refund Confirmation", $invoiceid, array("invoice_refund_type" => "credit"));
        }
        return "creditsuccess";
    }
    $result = select_query("tblpaymentgateways", "value", array("gateway" => $gateway, "setting" => "convertto"));
    $data = mysql_fetch_array($result);
    $convertto = $data["value"];
    $client = WHMCS\User\Client::findOrFail($userid);
    if ($convertto) {
        $convertedamount = convertCurrency($amount, $client->currencyId, $convertto, $rate);
        $refundCurrencyId = $convertto;
    } else {
        $convertedamount = NULL;
        $refundCurrencyId = $client->currencyId;
    }
    $params = array();
    if ($gateway) {
        $params = getCCVariables($invoiceid, $gateway);
    }
    if ($sendtogateway) {
        $gatewayModule = new WHMCS\Module\Gateway();
        $gatewayModule->load($gateway);
        if ($gatewayModule->functionExists("refund")) {
            $params["amount"] = $convertedamount ? $convertedamount : $amount;
            $params["transid"] = $gatewaytransid;
            $params["paymentmethod"] = $gateway;
            if ($refundCurrencyId) {
                $refundCurrency = WHMCS\Billing\Currency::find($refundCurrencyId);
                if ($refundCurrency) {
                    $params["currency"] = $refundCurrency->code;
                }
            }
            if (!isset($params["currency"])) {
                $params["currency"] = "";
            }
            $gatewayresult = $gatewayModule->call("refund", $params);
            if (is_array($gatewayresult)) {
                $refundtransid = $gatewayresult["transid"];
                $rawdata = $gatewayresult["rawdata"];
                if (isset($gatewayresult["fees"])) {
                    $fees = $gatewayresult["fees"];
                }
                $gatewayresult = $gatewayresult["status"];
            } else {
                $gatewayresult = "error";
                $rawdata = "Returned false";
            }
            logTransaction($gateway, $rawdata, "Refund " . ucfirst($gatewayresult));
        } else {
            $gatewayresult = "manual";
            run_hook("ManualRefund", array("transid" => $transid, "amount" => $amount));
        }
    } else {
        $gatewayresult = "manual";
        run_hook("ManualRefund", array("transid" => $transid, "amount" => $amount));
    }
    if ($gatewayresult == "success" || $gatewayresult == "manual") {
        addtransaction($userid, 0, "Refund of Transaction ID " . $gatewaytransid, 0, $fees * -1, $amount, $gateway, $refundtransid, $invoiceid, "", $transid, $rate);
        logActivity("Refunded Invoice Payment - Invoice ID: " . $invoiceid . " - Transaction ID: " . $transid, $userid);
        $result = select_query("tblinvoices", "total", array("id" => $invoiceid));
        $data = mysql_fetch_array($result);
        $invoicetotal = $data[0];
        if ($invoicetotalpaid - $invoicetotalrefunded - $amount <= 0) {
            update_query("tblinvoices", array("status" => "Refunded"), array("id" => $invoiceid));
            run_hook("InvoiceRefunded", array("invoiceid" => $invoiceid));
        }
        if ($sendemail) {
            sendMessage("Invoice Refund Confirmation", $invoiceid, array("invoice_refund_type" => "gateway"));
        }
        if ($reverse) {
            reversePaymentActions($transaction, $refundtransid, $transaction->transactionId);
        }
    }
    return $gatewayresult;
}
function processPaidInvoice($invoiceid, $noemail = "", $date = "")
{
    try {
        $invoice = WHMCS\Billing\Invoice::findOrFail($invoiceid);
        $invoiceid = $invoice->id;
        $userid = $invoice->clientId;
        $invoicestatus = $invoice->status;
        $invoicenum = $invoice->invoiceNumber;
        if (!in_array($invoicestatus, array("Unpaid", "Payment Pending"))) {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
    $date = $date ? toMySQLDate($date) . date(" H:i:s") : WHMCS\Carbon::now();
    $invoice->status = "Paid";
    $invoice->datePaid = $date;
    $invoice->save();
    logActivity("Invoice Marked Paid - Invoice ID: " . $invoiceid, $userid);
    if (WHMCS\Invoices::isSequentialPaidInvoiceNumberingEnabled()) {
        $euVATAddonCustomInvoiceNumbersEnabled = WHMCS\Config\Setting::getValue("TaxNextCustomInvoiceNumber");
        if (!$invoicenum || $euVATAddonCustomInvoiceNumbersEnabled) {
            update_query("tblinvoices", array("invoicenum" => WHMCS\Invoices::getNextSequentialPaidInvoiceNumber()), array("id" => $invoiceid));
        }
    }
    run_hook("InvoicePaidPreEmail", array("invoiceid" => $invoiceid));
    if (!$noemail) {
        sendMessage("Invoice Payment Confirmation", $invoiceid);
    }
    $orderId = get_query_val("tblorders", "id", array("invoiceid" => $invoiceid));
    if ($orderId) {
        run_hook("OrderPaid", array("orderId" => $orderId, "userId" => $userid, "invoiceId" => $invoiceid));
    }
    $items = $invoice->items()->where("type", "!=", "")->orderBy("id", "asc")->get();
    foreach ($items as $item) {
        $userid = $item->userId;
        $type = $item->type;
        $relid = $item->relatedEntityId;
        $amount = $item->amount;
        if ($type == "Hosting") {
            makeHostingPayment($relid, $invoice);
        } else {
            if ($type == "DomainRegister" || $type == "DomainTransfer" || $type == "Domain") {
                makeDomainPayment($relid, $type);
            } else {
                if ($type == "DomainAddonDNS") {
                    $enabledcheck = get_query_val("tbldomains", "dnsmanagement", array("id" => $relid));
                    if (!$enabledcheck) {
                        $currency = getCurrency($userid);
                        $dnscost = get_query_val("tblpricing", "msetupfee", array("type" => "domainaddons", "currency" => $currency["id"], "relid" => 0));
                        update_query("tbldomains", array("dnsmanagement" => "1", "recurringamount" => "+=" . $dnscost), array("id" => $relid));
                    }
                } else {
                    if ($type == "DomainAddonEMF") {
                        $enabledcheck = get_query_val("tbldomains", "emailforwarding", array("id" => $relid));
                        if (!$enabledcheck) {
                            $currency = getCurrency($userid);
                            $emfcost = get_query_val("tblpricing", "qsetupfee", array("type" => "domainaddons", "currency" => $currency["id"], "relid" => 0));
                            update_query("tbldomains", array("emailforwarding" => "1", "recurringamount" => "+=" . $emfcost), array("id" => $relid));
                        }
                    } else {
                        if ($type == "DomainAddonIDP") {
                            $enabledcheck = get_query_val("tbldomains", "idprotection", array("id" => $relid));
                            if (!$enabledcheck) {
                                $currency = getCurrency($userid);
                                $idpcost = get_query_val("tblpricing", "ssetupfee", array("type" => "domainaddons", "currency" => $currency["id"], "relid" => 0));
                                update_query("tbldomains", array("idprotection" => "1", "recurringamount" => "+=" . $idpcost), array("id" => $relid));
                                $data = get_query_vals("tbldomains", "type,domain,registrar,registrationperiod", array("id" => $relid));
                                $domainparts = explode(".", $data["domain"], 2);
                                $params = array();
                                $params["domainid"] = $relid;
                                list($params["sld"], $params["tld"]) = $domainparts;
                                $params["regperiod"] = $data["registrationperiod"];
                                $params["registrar"] = $data["registrar"];
                                $params["regtype"] = $data["type"];
                                if (!function_exists("RegIDProtectToggle")) {
                                    require ROOTDIR . "/includes/registrarfunctions.php";
                                }
                                $values = RegIDProtectToggle($params);
                                if ($values["error"]) {
                                    logActivity("ID Protection Enabling Failed - Error: " . $values["error"] . " - Domain ID: " . $relid, $userid);
                                } else {
                                    logActivity("ID Protection Enabled Successfully - Domain ID: " . $relid, $userid);
                                }
                            }
                        } else {
                            if ($type == "Addon") {
                                makeAddonPayment($relid, $invoice);
                            } else {
                                if ($type == "Upgrade") {
                                    if (!function_exists("processUpgradePayment")) {
                                        require dirname(__FILE__) . "/upgradefunctions.php";
                                    }
                                    processUpgradePayment($relid, "", "", "true");
                                } else {
                                    if ($type == "AddFunds") {
                                        insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Add Funds Invoice #" . $invoiceid, "amount" => $amount));
                                        update_query("tblclients", array("credit" => "+=" . $amount), array("id" => (int) $userid));
                                    } else {
                                        if ($type == "Invoice") {
                                            insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Mass Invoice Payment Credit for Invoice #" . $relid, "amount" => $amount));
                                            update_query("tblclients", array("credit" => "+=" . $amount), array("id" => (int) $userid));
                                            applyCredit($relid, $userid, $amount);
                                        } else {
                                            if (substr($type, 0, 14) == "ProrataProduct") {
                                                $newduedate = substr($type, 14);
                                                update_query("tblhosting", array("nextduedate" => $newduedate, "nextinvoicedate" => $newduedate), array("id" => $relid));
                                            } else {
                                                if (substr($type, 0, 12) == "ProrataAddon") {
                                                    $newduedate = substr($type, 12);
                                                    update_query("tblhostingaddons", array("nextduedate" => $newduedate, "nextinvoicedate" => $newduedate), array("id" => $relid));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    run_hook("InvoicePaid", array("invoiceid" => $invoiceid));
}
function getTaxRate($level, $state, $country)
{
    $result = select_query("tbltax", "", array("level" => $level, "state" => $state, "country" => $country));
    $data = mysql_fetch_array($result);
    $taxname = $data["name"];
    $taxrate = $data["taxrate"];
    if (is_null($taxrate)) {
        $result = select_query("tbltax", "", array("level" => $level, "state" => "", "country" => $country));
        $data = mysql_fetch_array($result);
        $taxname = $data["name"];
        $taxrate = $data["taxrate"];
    }
    if (is_null($taxrate)) {
        $result = select_query("tbltax", "", array("level" => $level, "state" => "", "country" => ""));
        $data = mysql_fetch_array($result);
        $taxname = $data["name"];
        $taxrate = $data["taxrate"];
    }
    if (is_null($taxrate)) {
        $taxname = "";
        $taxrate = 0;
    } else {
        if (!$taxname) {
            $taxname = Lang::trans("invoicestax");
        }
    }
    return array("name" => $taxname, "rate" => $taxrate);
}
function pdfInvoice($invoiceid)
{
    global $whmcs;
    global $CONFIG;
    global $_LANG;
    global $currency;
    $invoice = new WHMCS\Invoice();
    $invoice->pdfCreate();
    $invoice->pdfInvoicePage($invoiceid);
    $pdfdata = $invoice->pdfOutput();
    return $pdfdata;
}
function makeHostingPayment($func_domainid, WHMCS\Billing\Invoice $invoice)
{
    global $CONFIG;
    global $disable_to_do_list_entries;
    $result = select_query("tblhosting", "", array("id" => $func_domainid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $orderId = $data["orderid"];
    $billingcycle = $data["billingcycle"];
    $domain = $data["domain"];
    $packageid = $data["packageid"];
    $regdate = $data["regdate"];
    $nextduedate = $data["nextduedate"];
    $status = $data["domainstatus"];
    $server = $data["server"];
    $paymentmethod = $data["paymentmethod"];
    $suspendreason = $data["suspendreason"];
    $result = select_query("tblproducts", "", array("id" => $packageid));
    $data = mysql_fetch_array($result);
    $producttype = $data["type"];
    $productname = $data["name"];
    $module = $data["servertype"];
    $proratabilling = $data["proratabilling"];
    $proratadate = $data["proratadate"];
    $proratachargenextmonth = $data["proratachargenextmonth"];
    $autosetup = $data["autosetup"];
    if ($regdate == $nextduedate && $proratabilling) {
        $orderyear = substr($regdate, 0, 4);
        $ordermonth = substr($regdate, 5, 2);
        $orderday = substr($regdate, 8, 2);
        $proratavalues = getProrataValues($billingcycle, $product_onetime, $proratadate, $proratachargenextmonth, $orderday, $ordermonth, $orderyear, $userid);
        $nextduedate = $proratavalues["date"];
    } else {
        $nextduedate = getinvoicepayuntildate($nextduedate, $billingcycle, true);
    }
    update_query("tblhosting", array("nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate), array("id" => $func_domainid));
    if (!function_exists("getModuleType")) {
        include dirname(__FILE__) . "/modulefunctions.php";
    }
    if ($status == "Pending" && $autosetup == "payment" && $module) {
        if (getNewClientAutoProvisionStatus($userid)) {
            logActivity("Running Module Create on Payment", $userid);
            $result = ServerCreateAccount($func_domainid);
            if ($result == "success") {
                if ($module != "marketconnect") {
                    sendMessage("defaultnewacc", $func_domainid);
                }
                sendAdminMessage("Automatic Setup Successful", array("client_id" => $userid, "service_id" => $func_domainid, "service_product" => $productname, "service_domain" => $domain, "error_msg" => ""), "account");
            } else {
                sendAdminMessage("Automatic Setup Failed", array("client_id" => $userid, "service_id" => $func_domainid, "service_product" => $productname, "service_domain" => $domain, "error_msg" => $result), "account");
            }
        } else {
            logActivity("Module Create on Payment Suppressed for New Client", $userid);
        }
    }
    $suspenddate = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $CONFIG["AutoSuspensionDays"], date("Y")));
    if ($status == "Suspended" && $CONFIG["AutoUnsuspend"] == "on" && $module && !$suspendreason && $suspenddate <= str_replace("-", "", $nextduedate)) {
        logActivity("Running Auto Unsuspend on Payment", $userid);
        $moduleresult = ServerUnsuspendAccount($func_domainid);
        if ($moduleresult == "success") {
            sendMessage("Service Unsuspension Notification", $func_domainid);
            sendAdminMessage("Service Unsuspension Successful", array("client_id" => $userid, "service_id" => $func_domainid, "service_product" => $productname, "service_domain" => $domain, "error_msg" => ""), "account");
        } else {
            sendAdminMessage("Service Unsuspension Failed", array("client_id" => $userid, "service_id" => $func_domainid, "service_product" => $productname, "service_domain" => $domain, "error_msg" => $moduleresult), "account");
            if (!$disable_to_do_list_entries) {
                insert_query("tbltodolist", array("date" => "now()", "title" => "Manual Unsuspend Required", "description" => "The order placed for " . $domain . " has received its next payment and the automatic unsuspend has failed<br />Client ID: " . $userid . "<br>Product/Service: " . $productname . "<br>Domain: " . $domain, "admin" => "", "status" => "Pending", "duedate" => date("Y-m-d")));
            }
        }
    }
    if ($status != "Pending" && $module) {
        $runRenew = $invoice->shouldRenewRun($func_domainid, $regdate);
        if ($runRenew) {
            $moduleResult = ServerRenew($func_domainid);
            if ($moduleResult != "success" && $moduleResult != "notsupported") {
                sendAdminMessage("Service Renewal Failed", array("client_id" => $userid, "service_id" => $func_domainid, "service_product" => $productname, "service_domain" => $domain, "addon_id" => 0, "addon_name" => "", "error_msg" => $moduleResult), "account");
                if (!$disable_to_do_list_entries) {
                    $description = "The order placed for " . $domain . " has received its next payment and the" . " automatic renewal has failed<br>Client ID: " . $userid . "<br>" . "Product/Service: " . $productname . "<br>Domain: " . $domain;
                    $date = WHMCS\Carbon::now();
                    WHMCS\Database\Capsule::table("tbltodolist")->insert(array("date" => $date->toDateString(), "title" => "Manual Renewal Required", "description" => $description, "admin" => "", "status" => "Pending", "duedate" => $date->toDateTimeString()));
                }
            }
        }
    }
    AffiliatePayment("", $func_domainid);
    $freeAddons = WHMCS\Service\Addon::with("productAddon", "productAddon.welcomeEmailTemplate")->whereIn("billingcycle", array("Free", "Free Account"))->where("addonid", ">", 0)->where("status", "Pending")->where("hostingid", $func_domainid)->get();
    foreach ($freeAddons as $freeAddon) {
        $aId = $freeAddon->id;
        $addonId = $freeAddon->addonId;
        $autoActivate = $freeAddon->productAddon->autoActivate;
        $welcomeEmail = $freeAddon->productAddon->welcomeEmailTemplateId;
        if ($autoActivate && $autoActivate == "payment") {
            switch ($freeAddon->productAddon->module) {
                case "":
                    $freeAddon->status = "Active";
                    $freeAddon->save();
                    $automationResult = "";
                    $noModule = true;
                    break;
                default:
                    $automation = WHMCS\Service\Automation\AddonAutomation::factory($freeAddon);
                    $automationResult = $automation->runAction("CreateAccount");
                    $noModule = false;
            }
            if ($noModule || $automationResult) {
                if ($welcomeEmail) {
                    sendMessage($welcomeEmail, $func_domainid, array("addon_id" => $aId, "addon_service_id" => $func_domainid, "addon_addonid" => $addonId, "addon_billing_cycle" => $freeAddon->billingCycle, "addon_status" => "Active", "addon_nextduedate" => "0000-00-00", "addon_name" => $name = $freeAddon->name ?: $freeAddon->productAddon->name));
                }
                if ($noModule) {
                    run_hook("AddonActivation", array("id" => $freeAddon->id, "userid" => $freeAddon->clientId, "serviceid" => $func_domainid, "addonid" => $freeAddon->addonId));
                }
            }
        }
    }
}
function makeDomainPayment($func_domainid, $type = "")
{
    global $whmcs;
    $result = select_query("tbldomains", "", array("id" => $func_domainid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $orderid = $data["orderid"];
    $registrationperiod = $data["registrationperiod"];
    $registrationdate = $data["registrationdate"];
    $nextduedate = $data["nextduedate"];
    $recurringamount = $data["recurringamount"];
    $domain = $data["domain"];
    $paymentmethod = $data["paymentmethod"];
    $registrar = $data["registrar"];
    $status = $data["status"];
    $year = substr($nextduedate, 0, 4);
    $month = substr($nextduedate, 5, 2);
    $day = substr($nextduedate, 8, 2);
    $newnextduedate = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year + $registrationperiod));
    update_query("tbldomains", array("nextduedate" => $newnextduedate), array("id" => $func_domainid));
    $domaintype = substr($type, 6);
    $domainparts = explode(".", $domain, 2);
    list($sld, $tld) = $domainparts;
    $params = array();
    $params["domainid"] = $func_domainid;
    $params["sld"] = $sld;
    $params["tld"] = $tld;
    if (!function_exists("getRegistrarConfigOptions")) {
        require ROOTDIR . "/includes/registrarfunctions.php";
    }
    if ($domaintype == "Register" || $domaintype == "Transfer") {
        $result = select_query("tbldomainpricing", "autoreg", array("extension" => "." . $tld));
        $data = mysql_fetch_array($result);
        $autoreg = $data[0];
        if ($status == "Pending") {
            if (getNewClientAutoProvisionStatus($userid)) {
                if ($autoreg) {
                    update_query("tbldomains", array("registrar" => $autoreg), array("id" => $func_domainid));
                    $params["registrar"] = $autoreg;
                    if ($domaintype == "Register") {
                        logActivity("Running Automatic Domain Registration on Payment", $userid);
                        $result = RegRegisterDomain($params);
                        $emailmessage = "Domain Registration Confirmation";
                    } else {
                        if ($domaintype == "Transfer") {
                            logActivity("Running Automatic Domain Transfer on Payment", $userid);
                            $result = RegTransferDomain($params);
                            $emailmessage = "Domain Transfer Initiated";
                        }
                    }
                    $result = $result["error"];
                    if ($result) {
                        sendAdminMessage("Automatic Setup Failed", array("client_id" => $userid, "domain_id" => $func_domainid, "domain_type" => $domaintype, "domain_name" => $domain, "error_msg" => $result), "account");
                        if ($whmcs->get_config("DomainToDoListEntries")) {
                            if ($domaintype == "Register") {
                                addToDoItem("Manual Domain Registration", "Client ID " . $userid . " has paid for the registration of domain " . $domain . " and the automated registration attempt has failed with the following error: " . $result);
                            } else {
                                if ($domaintype == "Transfer") {
                                    addToDoItem("Manual Domain Transfer", "Client ID " . $userid . " has paid for the transfer of domain " . $domain . " and the automated transfer attempt has failed with the following error: " . $result);
                                }
                            }
                        }
                    } else {
                        sendMessage($emailmessage, $func_domainid);
                        sendAdminMessage("Automatic Setup Successful", array("client_id" => $userid, "domain_id" => $func_domainid, "domain_type" => $domaintype, "domain_name" => $domain, "error_msg" => ""), "account");
                    }
                } else {
                    if ($whmcs->get_config("DomainToDoListEntries")) {
                        if ($domaintype == "Register") {
                            addToDoItem("Manual Domain Registration", "Client ID " . $userid . " has paid for the registration of domain " . $domain);
                        } else {
                            if ($domaintype == "Transfer") {
                                addToDoItem("Manual Domain Transfer", "Client ID " . $userid . " has paid for the transfer of domain " . $domain);
                            }
                        }
                    }
                }
            } else {
                logActivity("Automatic Domain Registration on Payment Suppressed for New Client", $userid);
            }
        } else {
            if ($autoreg) {
                logActivity("Automatic Domain Registration Suppressed as Domain Is Already Active", $userid);
            }
        }
    } else {
        if ($status != "Pending" && $status != "Cancelled" && $status != "Fraud") {
            if ($whmcs->get_config("AutoRenewDomainsonPayment") && $registrar) {
                if ($whmcs->get_config("FreeDomainAutoRenewRequiresProduct") && $recurringamount <= 0 && !get_query_val("tblhosting", "COUNT(*)", array("userid" => $userid, "domain" => $domain, "domainstatus" => "Active"))) {
                    logActivity("Suppressed Automatic Domain Renewal on Payment Due to Domain Being Free and having No Active Associated Product", $userid);
                    sendAdminNotification("account", "Free Domain Renewal Manual Action Required", "The domain " . $domain . " (ID: " . $func_domainid . ") was just invoiced for renewal and automatically marked paid due to it being free, but because no active Product/Service matching the domain was found in order to qualify for the free domain offer, the renewal has not been automatically submitted to the registrar.  You must login to review & process this renewal manually should it be desired.");
                } else {
                    logActivity("Running Automatic Domain Renewal on Payment", $userid);
                    $params["registrar"] = $registrar;
                    $result = RegRenewDomain($params);
                    $result = $result["error"];
                    if ($result) {
                        sendAdminMessage("Domain Renewal Failed", array("client_id" => $userid, "domain_id" => $func_domainid, "domain_name" => $domain, "error_msg" => $result), "account");
                        if ($whmcs->get_config("DomainToDoListEntries")) {
                            addToDoItem("Manual Domain Renewal", "Client ID " . $userid . " has paid for the renewal of domain " . $domain . " and the automated renewal attempt has failed with the following error: " . $result);
                        }
                    } else {
                        sendMessage("Domain Renewal Confirmation", $func_domainid);
                        sendAdminMessage("Domain Renewal Successful", array("client_id" => $userid, "domain_id" => $func_domainid, "domain_name" => $domain, "error_msg" => ""), "account");
                    }
                }
            } else {
                if ($whmcs->get_config("DomainToDoListEntries")) {
                    addToDoItem("Manual Domain Renewal", "Client ID " . $userid . " has paid for the renewal of domain " . $domain);
                }
            }
        }
    }
}
function makeAddonPayment($func_addonid, $invoice)
{
    try {
        $configuration = App::getApplicationConfig()->getData();
        $disable_to_do_list_entries = false;
        if (array_key_exists("disable_to_do_list_entries", $configuration)) {
            $disable_to_do_list_entries = (bool) $configuration["disable_to_do_list_entries"];
        }
        $addon = WHMCS\Service\Addon::with("productAddon", "productAddon.welcomeEmailTemplate", "service", "service.product")->findOrFail($func_addonid);
        $id = $addon->id;
        $serviceId = $addon->serviceId;
        $addonId = $addon->addonId;
        $billingCycle = $addon->billingCycle;
        $status = $addon->status;
        $nextDueDate = $addon->nextDueDate;
        $userId = $addon->clientId;
        $nextDueDate = getinvoicepayuntildate($nextDueDate, $billingCycle, true);
        $name = $addon->name ?: $addon->productAddon->name;
        $addon->nextDueDate = $nextDueDate;
        $addon->save();
        if ($status == "Pending") {
            $autoActivate = "";
            $welcomeEmail = 0;
            if ($addonId) {
                $autoActivate = $addon->productAddon->autoActivate;
                $welcomeEmail = $addon->productAddon->welcomeEmailTemplate;
            }
            if ($autoActivate && $autoActivate == "payment") {
                switch ($addon->productAddon->module) {
                    case "":
                        $addon->status = "Active";
                        $addon->save();
                        $automationResult = "";
                        $noModule = true;
                        break;
                    default:
                        $automation = WHMCS\Service\Automation\AddonAutomation::factory($addon);
                        $automationResult = $automation->runAction("CreateAccount");
                        $noModule = false;
                }
                if ($noModule || $automationResult) {
                    if ($welcomeEmail) {
                        sendMessage($welcomeEmail, $serviceId, array("addon_id" => $id, "addon_service_id" => $serviceId, "addon_addonid" => $addonId, "addon_billing_cycle" => $billingCycle, "addon_status" => $status, "addon_nextduedate" => $nextDueDate, "addon_name" => $name));
                    }
                    if ($noModule) {
                        run_hook("AddonActivation", array("id" => $addon->id, "userid" => $userId, "serviceid" => $addon->serviceId, "addonid" => $addon->addonId));
                    }
                }
            }
        } else {
            if ($status == "Suspended") {
                if ($addonId && $addon->productAddon->module) {
                    $automation = WHMCS\Service\Automation\AddonAutomation::factory($addon);
                    $automationResult = $automation->runAction("UnsuspendAccount");
                    $noModule = false;
                } else {
                    $automationResult = "";
                    $addon->status = "Active";
                    $addon->save();
                    $noModule = true;
                    run_hook("AddonUnsuspended", array("id" => $addon->id, "userid" => $userId, "serviceid" => $serviceId, "addonid" => $addonId));
                }
                if (($automationResult || $noModule) && $addon->productAddon->suspendProduct && $addon->service->domainStatus == "Suspended" && $addon->service->product->module) {
                    logActivity("Unsuspending Parent Service for Addon Payment - Service ID: " . $serviceId, $userId);
                    if (!function_exists("getModuleType")) {
                        include dirname(__FILE__) . "/modulefunctions.php";
                    }
                    ServerUnsuspendAccount($serviceId);
                }
            } else {
                if ($status == "Active") {
                    $noModule = true;
                    if ($addonId) {
                        switch ($addon->productAddon->module) {
                            case "":
                                break;
                            default:
                                $registrationDate = $addon->registrationDate;
                                if ($registrationDate instanceof WHMCS\Carbon) {
                                    $registrationDate = $registrationDate->toDateString();
                                }
                                $runRenew = $invoice->shouldRenewRun($func_addonid, $registrationDate, "Addon");
                                if ($runRenew) {
                                    $automation = WHMCS\Service\Automation\AddonAutomation::factory($addon);
                                    $success = $automation->runAction("Renew");
                                    if (!$success && $automation->getError() != "notsupported") {
                                        $addonName = $addon->name;
                                        if (!$addonName && $addon->addonId) {
                                            $addonName = $addon->productAddon->name;
                                        }
                                        sendAdminMessage("Service Renewal Failed", array("client_id" => $userId, "service_id" => $addon->serviceId, "service_product" => $addon->service->product->name, "service_domain" => $addon->service->domain, "addon_id" => $addon->id, "addon_name" => $addonName, "error_msg" => $automation->getError()), "account");
                                        if (!$disable_to_do_list_entries) {
                                            $domain = $addon->serviceProperties->get("Domain Name");
                                            if (!$domain) {
                                                $domain = $addon->service->product->name;
                                            }
                                            $productName = $addon->service->product->name;
                                            $description = "The order placed for " . $domain . " has received its" . " next payment and the automatic renewal has failed<br>" . "Client ID: " . $userId . "<br>Product/Service: " . $productName . "<br>" . "Domain: " . $domain . "<br>Addon: " . $addonName;
                                            $date = WHMCS\Carbon::now();
                                            WHMCS\Database\Capsule::table("tbltodolist")->insert(array("date" => $date->toDateString(), "title" => "Manual Renewal Required", "description" => $description, "admin" => "", "status" => "Pending", "duedate" => $date->toDateTimeString()));
                                        }
                                    }
                                    $noModule = false;
                                }
                        }
                    }
                    if ($noModule) {
                        run_hook("AddonRenewal", array("id" => $addon->id, "userid" => $userId, "serviceid" => $addon->serviceId, "addonid" => $addon->addonId));
                    }
                }
            }
        }
    } catch (Exception $e) {
    }
}
function getProrataValues($billingcycle, $amount, $proratadate, $proratachargenextmonth, $day, $month, $year, $userid)
{
    global $CONFIG;
    if ($CONFIG["ProrataClientsAnniversaryDate"]) {
        $result = select_query("tblclients", "datecreated", array("id" => $userid));
        $data = mysql_fetch_array($result);
        $clientregdate = $data[0];
        $clientregdate = explode("-", $clientregdate);
        $proratadate = $clientregdate[2];
        if ($proratadate <= 0) {
            $proratadate = date("d");
        }
    }
    $billingcycle = str_replace("-", "", strtolower($billingcycle));
    $proratamonths = getBillingCycleMonths($billingcycle);
    if ($billingcycle != "monthly") {
        $proratachargenextmonth = 0;
    }
    if ($billingcycle == "monthly") {
        if ($day < $proratadate) {
            $proratamonth = $month;
        } else {
            $proratamonth = $month + 1;
        }
    } else {
        $proratamonth = $month + $proratamonths;
    }
    $proratadateuntil = date("Y-m-d", mktime(0, 0, 0, $proratamonth, $proratadate, $year));
    $proratainvoicedate = date("Y-m-d", mktime(0, 0, 0, $proratamonth, $proratadate - 1, $year));
    $monthnumdays = array("31", "28", "31", "30", "31", "30", "31", "31", "30", "31", "30", "31");
    if ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0) {
        $monthnumdays[1] = 29;
    }
    $totaldays = $extraamount = 0;
    if ($billingcycle == "monthly") {
        if ($proratachargenextmonth < $proratadate && $day < $proratadate && $proratachargenextmonth <= $day || $proratadate <= $proratachargenextmonth && $proratadate <= $day && $proratachargenextmonth <= $day) {
            $proratamonth++;
            $extraamount = $amount;
        }
        $totaldays += $monthnumdays[$month - 1];
        $days = ceil((strtotime($proratadateuntil) - strtotime((string) $year . "-" . $month . "-" . $day)) / (60 * 60 * 24));
        $proratadateuntil = date("Y-m-d", mktime(0, 0, 0, $proratamonth, $proratadate, $year));
        $proratainvoicedate = date("Y-m-d", mktime(0, 0, 0, $proratamonth, $proratadate - 1, $year));
    } else {
        for ($counter = $month; $counter <= $month + $proratamonths - 1; $counter++) {
            $month2 = round($counter);
            if (12 < $month2) {
                $month2 = $month2 - 12;
            }
            if (12 < $month2) {
                $month2 = $month2 - 12;
            }
            if (12 < $month2) {
                $month2 = $month2 - 12;
            }
            $totaldays += $monthnumdays[$month2 - 1];
        }
        $days = ceil((strtotime($proratadateuntil) - strtotime((string) $year . "-" . $month . "-" . $day)) / (60 * 60 * 24));
    }
    $prorataamount = round($amount * $days / $totaldays, 2) + $extraamount;
    $days = ceil((strtotime($proratadateuntil) - strtotime((string) $year . "-" . $month . "-" . $day)) / (60 * 60 * 24));
    return array("amount" => $prorataamount, "date" => $proratadateuntil, "invoicedate" => $proratainvoicedate, "days" => $days);
}
function getNewClientAutoProvisionStatus($userid)
{
    global $CONFIG;
    if ($CONFIG["AutoProvisionExistingOnly"]) {
        $result = select_query("tblhosting", "COUNT(*)", array("userid" => $userid, "domainstatus" => "Active"));
        $data = mysql_fetch_array($result);
        $result = select_query("tbldomains", "COUNT(*)", array("userid" => $userid, "status" => "Active"));
        $data2 = mysql_fetch_array($result);
        if ($data[0] + $data2[0]) {
            return true;
        }
        return false;
    }
    return true;
}
function applyCredit($invoiceid, $userid, $amount, $noemail = "")
{
    $amount = round($amount, 2);
    update_query("tblinvoices", array("credit" => "+=" . $amount), array("id" => (int) $invoiceid));
    update_query("tblclients", array("credit" => "-=" . $amount), array("id" => (int) $userid));
    insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Credit Applied to Invoice #" . $invoiceid, "amount" => $amount * -1));
    logActivity("Credit Applied - Amount: " . $amount . " - Invoice ID: " . $invoiceid, $userid);
    updateinvoicetotal($invoiceid);
    $result = select_query("tblinvoices", "total", array("id" => $invoiceid));
    $data = mysql_fetch_array($result);
    $total = $data["total"];
    $result = select_query("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
    $data = mysql_fetch_array($result);
    $amountpaid = $data[0];
    $balance = $total - $amountpaid;
    if ($balance <= 0) {
        processpaidinvoice($invoiceid, $noemail);
    }
}
function getBillingCycleDays($billingcycle)
{
    $totaldays = 0;
    if ($billingcycle == "Monthly") {
        $totaldays = 30;
    } else {
        if ($billingcycle == "Quarterly") {
            $totaldays = 90;
        } else {
            if ($billingcycle == "Semi-Annually") {
                $totaldays = 180;
            } else {
                if ($billingcycle == "Annually") {
                    $totaldays = 365;
                } else {
                    if ($billingcycle == "Biennially") {
                        $totaldays = 730;
                    } else {
                        if ($billingcycle == "Triennially") {
                            $totaldays = 1095;
                        }
                    }
                }
            }
        }
    }
    return $totaldays;
}
function getBillingCycleMonths($billingcycle)
{
    try {
        $months = (new WHMCS\Billing\Cycles())->getNumberOfMonths($billingcycle);
    } catch (Exception $e) {
        $months = 1;
    }
    return $months;
}
function isUniqueTransactionID($transactionID, $gateway)
{
    $transactionID = get_query_val("tblaccounts", "id", array("transid" => $transactionID, "gateway" => $gateway));
    if ($transactionID) {
        return false;
    }
    return true;
}
function removeCreditOnInvoiceDelete($invoiceID)
{
    $invoiceData = WHMCS\Database\Capsule::table("tblinvoices")->find($invoiceID, array("userid", "credit"));
    $creditAmount = $invoiceData->credit;
    $userID = $invoiceData->userid;
    if (0 < $creditAmount) {
        WHMCS\Database\Capsule::table("tblinvoices")->where("id", "=", $invoiceID)->update(array("credit" => 0));
        updateinvoicetotal($invoiceID);
        $client = WHMCS\User\Client::find($userID);
        $client->credit += $creditAmount;
        $client->save();
        WHMCS\Database\Capsule::table("tblcredit")->insert(array("clientid" => $userID, "date" => date("Y-m-d"), "description" => "Credit Removed on deletion of Invoice #" . $invoiceID, "amount" => $creditAmount));
        logActivity("Credit Removed on Invoice Deletion - Amount: " . $creditAmount . " - Invoice ID: " . $invoiceID, $userID);
    }
}
function refundCreditOnStatusChange($invoiceId, $status = "Fraud")
{
    $invoiceData = WHMCS\Database\Capsule::table("tblinvoices")->find($invoiceId, array("userid", "credit"));
    $creditAmount = $invoiceData->credit;
    $userId = $invoiceData->userid;
    if (0 < $creditAmount) {
        WHMCS\Database\Capsule::table("tblinvoices")->where("id", $invoiceId)->update(array("credit" => 0));
        updateinvoicetotal($invoiceId);
        $client = WHMCS\User\Client::find($userId);
        $client->credit += $creditAmount;
        $client->save();
        WHMCS\Database\Capsule::table("tblcredit")->insert(array("clientid" => $userId, "date" => WHMCS\Carbon::now()->format("Y-m-d"), "description" => "Credit Removed from Invoice #" . $invoiceId . " due to Order Status being changed to " . $status, "amount" => $creditAmount));
        logActivity("Credit Removed from Invoice ID: " . $invoiceId . " due to Order Status being changed to " . $status . " - Amount: " . $creditAmount, $userId);
    }
}
function paymentReversed($reverseTransactionId, $originalTransactionId, $invoiceId = 0, $gateway = NULL)
{
    $transaction = WHMCS\Billing\Payment\Transaction::with("client")->where("transid", "=", $originalTransactionId);
    if ($invoiceId) {
        $transaction = $transaction->where("invoiceid", "=", $invoiceId);
    }
    if ($gateway) {
        $transaction = $transaction->where("gateway", "=", $gateway);
    }
    if (1 < $transaction->count()) {
        throw new WHMCS\Exception("Multiple Original Transaction matches - Reversal not Available");
    }
    $transaction = $transaction->first();
    if (!$transaction) {
        throw new WHMCS\Exception("Original Transaction Not Found");
    }
    $existingRefundTransaction = WHMCS\Billing\Payment\Transaction::where("refundid", "=", $transaction->id)->first();
    $reverseTransactionWithSameId = WHMCS\Billing\Payment\Transaction::where("transid", "=", $reverseTransactionId)->first();
    if ($existingRefundTransaction || $reverseTransactionWithSameId) {
        throw new WHMCS\Exception("Transaction Already Reversed");
    }
    $invoice = $transaction->invoice;
    $reversedTransaction = new WHMCS\Billing\Payment\Transaction();
    $reversedTransaction->amountOut = $transaction->amountIn;
    $reversedTransaction->refundId = $transaction->id;
    $reversedTransaction->transactionId = $reverseTransactionId;
    $reversedTransaction->invoiceId = $transaction->invoiceId;
    $reversedTransaction->exchangeRate = $transaction->exchangeRate;
    $reversedTransaction->fees = $transaction->fees * -1;
    $reversedTransaction->clientId = $transaction->clientId;
    $reversedTransaction->description = "Reversed Transaction ID: " . $transaction->transactionId;
    $reversedTransaction->paymentGateway = $transaction->paymentGateway;
    $reversedTransaction->date = WHMCS\Carbon::now();
    $reversedTransaction->save();
    if ($invoice) {
        reversePaymentActions($transaction, $reverseTransactionId, $originalTransactionId);
    }
    $gateway = $transaction->paymentGateway;
    $paymentGateway = "No Gateway";
    if ($gateway) {
        try {
            $paymentGateway = WHMCS\Module\Gateway::factory($gateway)->getDisplayName();
        } catch (Exception $e) {
            $paymentGateway = $gateway;
        }
    }
    sendAdminMessage("Payment Reversed Notification", array("invoice_id" => $invoice->id, "transaction_id" => $originalTransactionId, "transaction_date" => fromMySQLDate($transaction->date), "transaction_amount" => new WHMCS\View\Formatter\Price($transaction->amountIn, getCurrency($transaction->clientId)), "payment_method" => $paymentGateway), "account");
}
function reversePaymentActions(WHMCS\Billing\Payment\Transaction $transaction, $reverseTransactionId, $originalTransactionId)
{
    $invoice = $transaction->invoice;
    $doChangeInvoiceStatus = (bool) WHMCS\Config\Setting::getValue("ReversalChangeInvoiceStatus");
    $doChangeDueDates = (bool) WHMCS\Config\Setting::getValue("ReversalChangeDueDates");
    if ($doChangeInvoiceStatus) {
        $invoice->status = "Collections";
        $invoice->save();
        logActivity("Payment Reversal - Invoice Status set to Collections - Invoice ID: " . $invoice->id, $invoice->clientId);
    }
    foreach ($invoice->items as $item) {
        switch ($item->type) {
            case "Addon":
            case "Hosting":
                if ($doChangeDueDates) {
                    if ($item->type == "Addon") {
                        $model = WHMCS\Service\Addon::find($item->relatedEntityId);
                        $activityLogEntry = "Payment Reversal - Modified Service Addon - Next Due Date changed from ";
                        $activityLogSuffix = " - Service ID: " . $model->serviceId . " - Addon ID: " . $model->id;
                    } else {
                        $model = WHMCS\Service\Service::find($item->relatedEntityId);
                        $activityLogEntry = "Payment Reversal - Modified Product/Service - Next Due Date changed from ";
                        $activityLogSuffix = " - Service ID: " . $model->id;
                    }
                    $defaultNextDueDate = $model->registrationDate;
                    $nextDueDate = $model->nextDueDate;
                    if (!$nextDueDate instanceof WHMCS\Carbon && $nextDueDate != "0000-00-00" && $nextDueDate != "1970-01-01") {
                        $nextDueDate = WHMCS\Carbon::createFromFormat("Y-m-d", $nextDueDate);
                    }
                    if ($nextDueDate instanceof WHMCS\Carbon) {
                        $activityLogEntry .= (string) $nextDueDate->toDateString() . " to";
                        $nextDueDate = $nextDueDate->subMonths(getbillingcyclemonths($model->billingCycle));
                        $activityLogEntry .= " " . $nextDueDate->toDateString();
                    } else {
                        $activityLogEntry .= (string) $nextDueDate . " to " . $defaultNextDueDate;
                    }
                    $activityLogEntry .= " - User ID: " . $model->clientId;
                    $model->nextDueDate = $nextDueDate;
                    $model->save();
                    logActivity($activityLogEntry . $activityLogSuffix, $model->clientId);
                }
                break;
            case "Upgrade":
                $upgrade = WHMCS\Database\Capsule::table("tblupgrades")->find($item->relatedEntityId);
                $service = WHMCS\Service\Service::find($upgrade->relid);
                if ($service->serverId) {
                    $server = new WHMCS\Module\Server();
                    $server->loadByServiceID($service->id);
                    if ($server->functionExists("SuspendAccount")) {
                        $server->call("SuspendAccount");
                    }
                }
                break;
            case "AddFunds":
                WHMCS\Database\Capsule::table("tblcredit")->insert(array("clientid" => $item->userId, "date" => WHMCS\Carbon::now()->toDateString(), "description" => "Reversed Transaction ID: " . $originalTransactionId, "amount" => $transaction->amountIn * -1));
                $transaction->client->credit -= $transaction->amountIn;
                $transaction->client->save();
                logActivity("Payment Reversal - Removed Credit - User ID: " . $item->userId . " - Amount: " . formatCurrency($transaction->amountIn), $item->userId);
                break;
            case "Invoice":
                $reversedTransaction = new WHMCS\Billing\Payment\Transaction();
                $reversedTransaction->amountOut = $item->amount;
                $reversedTransaction->refundId = $transaction->id;
                $reversedTransaction->transactionId = $reverseTransactionId;
                $reversedTransaction->invoiceId = $item->relatedEntityId;
                $reversedTransaction->exchangeRate = $transaction->exchangeRate;
                $reversedTransaction->fees = 0;
                $reversedTransaction->clientId = $item->userId;
                $reversedTransaction->description = "Invoice Payment Reversal: Invoice ID: #" . $item->invoiceId;
                $reversedTransaction->paymentGateway = $transaction->paymentGateway;
                $reversedTransaction->date = WHMCS\Carbon::now();
                $reversedTransaction->save();
                if ($doChangeInvoiceStatus) {
                    $reversedTransaction->invoice->status = "Collections";
                    $reversedTransaction->invoice->save();
                    logActivity("Payment Reversal - Invoice Status set to Collections - Invoice ID: " . $reversedTransaction->invoice->id, $item->userId);
                }
                break;
            case "DomainRegister":
            case "DomainRenew":
            case "DomainTransfer":
            case "DomainAddonDNS":
            case "DomainAddonEMF":
            case "DomainAddonIDP":
                break;
            default:
                if ($doChangeDueDates) {
                    $model = NULL;
                    $previousInvoiceItem = NULL;
                    $activityLogEntry = "";
                    $activityLogSuffix = "";
                    if (substr($item->type, 0, 14) == "ProrataProduct") {
                        $model = WHMCS\Service\Service::find($item->relatedEntityId);
                        $previousInvoiceItem = WHMCS\Billing\Invoice\Item::where("relid", "=", $item->relatedEntityId)->where("type", "=", "Service")->orderBy("id", "DESC")->first();
                        $activityLogEntry = "Payment Reversal - Modified Product/Service - Next Due Date changed from ";
                        $activityLogSuffix = " - Service ID: " . $model->id;
                    } else {
                        if (substr($item->type, 0, 12) == "ProrataAddon") {
                            $model = WHMCS\Service\Addon::find($item->relatedEntityId);
                            $previousInvoiceItem = WHMCS\Billing\Invoice\Item::where("relid", "=", $item->relatedEntityId)->where("type", "=", "Addon")->orderBy("id", "DESC")->first();
                            $activityLogEntry = "Payment Reversal - Modified Service Addon - Next Due Date changed from ";
                            $activityLogSuffix = " - Service ID: " . $model->serviceId . " - Addon ID: " . $model->id;
                        }
                    }
                    if ($model && $previousInvoiceItem) {
                        $activityLogEntry .= (string) $model->nextDueDate . " to " . $previousInvoiceItem->dueDate . " - User ID: " . $model->clientId;
                        $model->nextDueDate = $previousInvoiceItem->dueDate;
                        $model->save();
                        logActivity($activityLogEntry . $activityLogSuffix, $model->clientId);
                    }
                }
        }
    }
}

?>