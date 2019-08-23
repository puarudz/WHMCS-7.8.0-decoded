<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function createInvoices($func_userid = "", $noemails = "", $nocredit = "", $specificitems = "", WHMCS\Scheduling\Task\TaskInterface $task = NULL)
{
    global $whmcs;
    global $CONFIG;
    global $_LANG;
    global $invoicecount;
    global $invoiceid;
    global $continuous_invoicing_active_only;
    $continvoicegen = WHMCS\Config\Setting::getValue("ContinuousInvoiceGeneration");
    $invoicedate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBefore"], date("Y")));
    $invoicedatemonthly = $CONFIG["CreateInvoiceDaysBeforeMonthly"] ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBeforeMonthly"], date("Y"))) : $invoicedate;
    $invoicedatequarterly = $CONFIG["CreateInvoiceDaysBeforeQuarterly"] ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBeforeQuarterly"], date("Y"))) : $invoicedate;
    $invoicedatesemiannually = $CONFIG["CreateInvoiceDaysBeforeSemiAnnually"] ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBeforeSemiAnnually"], date("Y"))) : $invoicedate;
    $invoicedateannually = $CONFIG["CreateInvoiceDaysBeforeAnnually"] ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBeforeAnnually"], date("Y"))) : $invoicedate;
    $invoicedatebiennially = $CONFIG["CreateInvoiceDaysBeforeBiennially"] ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBeforeBiennially"], date("Y"))) : $invoicedate;
    $invoicedatetriennially = $CONFIG["CreateInvoiceDaysBeforeTriennially"] ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateInvoiceDaysBeforeTriennially"], date("Y"))) : $invoicedate;
    $domaininvoicedate = 0 < WHMCS\Config\Setting::getValue("CreateDomainInvoiceDaysBefore") ? date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $CONFIG["CreateDomainInvoiceDaysBefore"], date("Y"))) : $invoicedate;
    $matchfield = $continvoicegen ? "nextinvoicedate" : "nextduedate";
    run_hook("PreInvoicingGenerateInvoiceItems", array());
    $statusfilter = "'Pending','Active'";
    if (!$continuous_invoicing_active_only) {
        $statusfilter .= ",'Suspended'";
    }
    $hostingquery = "domainstatus IN (" . $statusfilter . ") AND billingcycle!='Free' AND billingcycle!='Free Account' AND nextduedate!='00000000' AND nextinvoicedate!='00000000' AND ((billingcycle='Monthly' AND " . $matchfield . "<='" . $invoicedatemonthly . "') OR (billingcycle='Quarterly' AND " . $matchfield . "<='" . $invoicedatequarterly . "') OR (billingcycle='Semi-Annually' AND " . $matchfield . "<='" . $invoicedatesemiannually . "') OR (billingcycle='Annually' AND " . $matchfield . "<='" . $invoicedateannually . "') OR (billingcycle='Biennially' AND " . $matchfield . "<='" . $invoicedatebiennially . "') OR (billingcycle='Triennially' AND " . $matchfield . "<='" . $invoicedatetriennially . "') OR (billingcycle='One Time'))";
    $domainquery = "(donotrenew='' OR `status`='Pending') AND `status` IN (" . $statusfilter . ") AND " . $matchfield . "<='" . $domaininvoicedate . "'";
    $hostingaddonsquery = "tblhostingaddons.billingcycle!='Free' AND tblhostingaddons.billingcycle!='Free Account' AND tblhostingaddons.status IN (" . $statusfilter . ") AND tblhostingaddons.nextduedate!='00000000' AND tblhostingaddons.nextinvoicedate!='00000000' AND ((tblhostingaddons.billingcycle='Monthly' AND tblhostingaddons." . $matchfield . "<='" . $invoicedatemonthly . "') OR (tblhostingaddons.billingcycle='Quarterly' AND tblhostingaddons." . $matchfield . "<='" . $invoicedatequarterly . "') OR (tblhostingaddons.billingcycle='Semi-Annually' AND tblhostingaddons." . $matchfield . "<='" . $invoicedatesemiannually . "') OR (tblhostingaddons.billingcycle='Annually' AND tblhostingaddons." . $matchfield . "<='" . $invoicedateannually . "') OR (tblhostingaddons.billingcycle='Biennially' AND tblhostingaddons." . $matchfield . "<='" . $invoicedatebiennially . "') OR (tblhostingaddons.billingcycle='Triennially' AND tblhostingaddons." . $matchfield . "<='" . $invoicedatetriennially . "') OR (tblhostingaddons.billingcycle='One Time'))";
    $i = 0;
    $billableitemqry = "";
    if ($func_userid != "") {
        $hostingquery .= " AND userid=" . (int) $func_userid;
        $domainquery .= " AND userid=" . (int) $func_userid;
        $hostingaddonsquery .= " AND tblhosting.userid=" . (int) $func_userid;
        $billableitemqry = " AND userid=" . (int) $func_userid;
    }
    if (is_array($specificitems)) {
        $hostingquery = $domainquery = $hostingaddonsquery = "";
        if ($specificitems["products"]) {
            $hostingquery .= "(id IN (" . db_build_in_array($specificitems["products"]) . ") AND billingcycle!='Free' AND billingcycle!='Free Account')";
        }
        if ($specificitems["addons"]) {
            $hostingaddonsquery .= "tblhostingaddons.id IN (" . db_build_in_array($specificitems["addons"]) . ") AND tblhostingaddons.billingcycle!='Free' AND tblhostingaddons.billingcycle!='Free Account'";
        }
        if ($specificitems["domains"]) {
            $domainquery .= "id IN (" . db_build_in_array($specificitems["domains"]) . ")";
        }
    }
    $AddonsArray = $AddonSpecificIDs = array();
    $gateways = new WHMCS\Gateways();
    if ($hostingquery) {
        $cancellationreqids = array();
        $result = select_query("tblcancelrequests", "DISTINCT relid", "");
        while ($data = mysql_fetch_array($result)) {
            $cancellationreqids[] = $data[0];
        }
        $result = select_query("tblhosting", "tblhosting.id,tblhosting.userid,tblhosting.nextduedate,tblhosting.nextinvoicedate,tblhosting.billingcycle,tblhosting.regdate,tblhosting.firstpaymentamount,tblhosting.amount,tblhosting.domain,tblhosting.paymentmethod,tblhosting.packageid,tblhosting.promoid,tblhosting.domainstatus", $hostingquery, "domain", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $id = $serviceid = $data["id"];
            if (!in_array($serviceid, $cancellationreqids)) {
                $userid = $data["userid"];
                $nextduedate = $data[$matchfield];
                $billingcycle = $data["billingcycle"];
                $status = $data["domainstatus"];
                $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Hosting", "relid" => $serviceid, "duedate" => $nextduedate));
                $contblock = false;
                if (!$num_rows && $continvoicegen && $status == "Pending") {
                    $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Hosting", "relid" => $serviceid));
                    $contblock = true;
                }
                if ($num_rows == 0) {
                    $regdate = $data["regdate"];
                    $amount = $regdate == $nextduedate ? $data["firstpaymentamount"] : $data["amount"];
                    $domain = $data["domain"];
                    $paymentmethod = $data["paymentmethod"];
                    if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
                        $paymentmethod = ensurePaymentMethodIsSet($userid, $id, "tblhosting");
                    }
                    $pid = $data["packageid"];
                    $promoid = $data["promoid"];
                    $productdetails = getInvoiceProductDetails($id, $pid, $regdate, $nextduedate, $billingcycle, $domain, $userid);
                    $description = $productdetails["description"];
                    $tax = $productdetails["tax"];
                    $recurringcycles = $productdetails["recurringcycles"];
                    $recurringfinished = false;
                    if ($recurringcycles) {
                        $num_rows3 = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Hosting", "relid" => $id));
                        if ($recurringcycles <= $num_rows3) {
                            WHMCS\Database\Capsule::table("tblhosting")->where("id", "=", $id)->update(array("domainstatus" => "Completed", "completed_date" => WHMCS\Carbon::today()->toDateString()));
                            run_hook("ServiceRecurringCompleted", array("serviceid" => $id, "recurringinvoices" => $num_rows3));
                            $recurringfinished = true;
                        }
                    }
                    if (!$recurringfinished) {
                        $promovals = getInvoiceProductPromo($amount, $promoid, $userid, $id);
                        if (isset($promovals["description"])) {
                            $amount -= $promovals["amount"];
                        }
                        insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "Hosting", "relid" => $id, "description" => $description, "amount" => $amount, "taxed" => $tax, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod));
                        cancelUnpaidUpgrade((int) $id);
                        if (isset($promovals["description"])) {
                            insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "PromoHosting", "relid" => $id, "description" => $promovals["description"], "amount" => $promovals["amount"], "taxed" => $tax, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod));
                        }
                    }
                } else {
                    if (!$contblock && $continvoicegen && $billingcycle != "One Time") {
                        update_query("tblhosting", array("nextinvoicedate" => getInvoicePayUntilDate($nextduedate, $billingcycle, true)), array("id" => $id));
                    }
                }
            }
            if ($hostingaddonsquery) {
                $result3 = select_query("tblhostingaddons", "tblhostingaddons.*,tblhostingaddons.regdate AS addonregdate,tblhosting.userid,tblhosting.domain", $hostingaddonsquery . " AND tblhostingaddons.hostingid='" . $id . "'", "tblhostingaddons`.`name", "ASC", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid");
                while ($data = mysql_fetch_array($result3)) {
                    $id = $data["id"];
                    $userid = $data["userid"];
                    $nextduedate = $data[$matchfield];
                    $status = $data["status"];
                    $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Addon", "relid" => $id, "duedate" => $nextduedate));
                    $contblock = false;
                    if (!$num_rows && $continvoicegen && $status == "Pending") {
                        $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Addon", "relid" => $id));
                        $contblock = true;
                    }
                    if ($num_rows == 0) {
                        $hostingid = $serviceid = $data["hostingid"];
                        $addonid = $data["addonid"];
                        $domain = $data["domain"];
                        $regdate = $data["addonregdate"];
                        $name = $data["name"];
                        $setupfee = $data["setupfee"];
                        $amount = $data["recurring"];
                        $paymentmethod = $data["paymentmethod"];
                        $billingcycle = $data["billingcycle"];
                        $tax = $data["tax"];
                        if (!$name) {
                            if (isset($AddonsArray[$addonid])) {
                                $name = $AddonsArray[$addonid];
                            } else {
                                $AddonsArray[$addonid] = $name = get_query_val("tbladdons", "name", array("id" => $addonid));
                            }
                        }
                        if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
                            $paymentmethod = ensurePaymentMethodIsSet($userid, $id, "tblhostingaddons");
                        }
                        $tax = $CONFIG["TaxEnabled"] && $tax ? "1" : "0";
                        $invoicepayuntildate = getInvoicePayUntilDate($nextduedate, $billingcycle);
                        $paydates = "";
                        if ($billingcycle != "One Time") {
                            $paydates = "(" . fromMySQLDate($nextduedate) . " - " . fromMySQLDate($invoicepayuntildate) . ")";
                        }
                        $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Addon", "relid" => $id, "duedate" => $nextduedate));
                        if ($num_rows == 0) {
                            if (!in_array($serviceid, $cancellationreqids)) {
                                if ($regdate == $nextduedate) {
                                    $amount = $amount + $setupfee;
                                }
                                if ($domain) {
                                    $domain = "(" . $domain . ") ";
                                }
                                $description = $_LANG["orderaddon"] . " " . $domain . "- " . $name . " " . $paydates;
                                insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "Addon", "relid" => $id, "description" => $description, "amount" => $amount, "taxed" => $tax, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod));
                                $AddonSpecificIDs[] = $id;
                            }
                        } else {
                            if (!$contblock && $continvoicegen) {
                                update_query("tblhostingaddons", array("nextinvoicedate" => getInvoicePayUntilDate($nextduedate, $billingcycle, true)), array("id" => $id));
                            }
                        }
                    }
                }
            }
        }
    }
    if ($hostingaddonsquery) {
        if (count($AddonSpecificIDs)) {
            $hostingaddonsquery .= " AND tblhostingaddons.id NOT IN (" . db_build_in_array($AddonSpecificIDs) . ")";
        }
        $result = select_query("tblhostingaddons", "tblhostingaddons.*,tblhostingaddons.regdate AS addonregdate,tblhosting.userid,tblhosting.domain", $hostingaddonsquery, "tblhostingaddons`.`name", "ASC", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $userid = $data["userid"];
            $nextduedate = $data[$matchfield];
            $status = $data["status"];
            $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Addon", "relid" => $id, "duedate" => $nextduedate));
            $contblock = false;
            if (!$num_rows && $continvoicegen && $status == "Pending") {
                $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Addon", "relid" => $id));
                $contblock = true;
            }
            if ($num_rows == 0) {
                $hostingid = $serviceid = $data["hostingid"];
                $addonid = $data["addonid"];
                $domain = $data["domain"];
                $regdate = $data["addonregdate"];
                $name = $data["name"];
                $setupfee = $data["setupfee"];
                $amount = $data["recurring"];
                $paymentmethod = $data["paymentmethod"];
                if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
                    $paymentmethod = ensurePaymentMethodIsSet($userid, $id, "tblhostingaddons");
                }
                $billingcycle = $data["billingcycle"];
                $tax = $data["tax"];
                if (!$name) {
                    if ($AddonsArray[$addonid]) {
                        $name = $AddonsArray[$addonid];
                    } else {
                        $AddonsArray[$addonid] = $name = get_query_val("tbladdons", "name", array("id" => $addonid));
                    }
                }
                $tax = $CONFIG["TaxEnabled"] && $tax ? "1" : "0";
                $invoicepayuntildate = getInvoicePayUntilDate($nextduedate, $billingcycle);
                $paydates = "";
                if ($billingcycle != "One Time") {
                    $paydates = "(" . fromMySQLDate($nextduedate) . " - " . fromMySQLDate($invoicepayuntildate) . ")";
                }
                if (!in_array($serviceid, $cancellationreqids)) {
                    if ($regdate == $nextduedate) {
                        $amount = $amount + $setupfee;
                    }
                    if ($domain) {
                        $domain = "(" . $domain . ") ";
                    }
                    $description = $_LANG["orderaddon"] . " " . $domain . "- " . $name . " " . $paydates;
                    $sslCompetitiveUpgradeAddons = WHMCS\Session::get("SslCompetitiveUpgradeAddons");
                    if (is_array($sslCompetitiveUpgradeAddons) && in_array($id, $sslCompetitiveUpgradeAddons)) {
                        $description .= "<br><small>" . Lang::trans("store.ssl.competitiveUpgradeQualified") . "</small>";
                        array_flip($sslCompetitiveUpgradeAddons);
                        unset($sslCompetitiveUpgradeAddons[$id]);
                        array_flip($sslCompetitiveUpgradeAddons);
                        WHMCS\Session::set("SslCompetitiveUpgradeAddons", $sslCompetitiveUpgradeAddons);
                    }
                    insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "Addon", "relid" => $id, "description" => $description, "amount" => $amount, "taxed" => $tax, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod));
                }
            } else {
                if (!$contblock && $continvoicegen) {
                    update_query("tblhostingaddons", array("nextinvoicedate" => getInvoicePayUntilDate($nextduedate, $billingcycle, true)), array("id" => $id));
                }
            }
        }
    }
    if ($domainquery) {
        $result = select_query("tbldomains", "", $domainquery, "domain", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $userid = $data["userid"];
            $nextduedate = $data[$matchfield];
            $status = $data["status"];
            $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", "userid=" . (int) $userid . " AND type IN ('Domain','DomainRegister','DomainTransfer') AND relid=" . (int) $id . " AND duedate='" . db_escape_string($nextduedate) . "'");
            $contblock = false;
            if (!$num_rows && $continvoicegen && $status == "Pending") {
                $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", "userid=" . (int) $userid . " AND type IN ('Domain','DomainRegister','DomainTransfer') AND relid=" . (int) $id);
                $contblock = true;
            }
            if ($num_rows == 0) {
                $type = $data["type"];
                $domain = $data["domain"];
                $registrationperiod = $data["registrationperiod"];
                $regdate = $data["registrationdate"];
                $expirydate = $data["expirydate"];
                $paymentmethod = $data["paymentmethod"];
                if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
                    $paymentmethod = ensurePaymentMethodIsSet($userid, $id, "tbldomains");
                }
                $dnsmanagement = $data["dnsmanagement"];
                $emailforwarding = $data["emailforwarding"];
                $idprotection = $data["idprotection"];
                $promoid = $data["promoid"];
                getUsersLang($userid);
                if ($expirydate == "0000-00-00") {
                    $expirydate = $nextduedate;
                }
                if ($regdate == $nextduedate) {
                    $amount = $data["firstpaymentamount"];
                    if ($type == "Transfer") {
                        $domaindesc = $_LANG["domaintransfer"];
                    } else {
                        $domaindesc = $_LANG["domainregistration"];
                        $type = "Register";
                    }
                } else {
                    $amount = $data["recurringamount"];
                    $domaindesc = $_LANG["domainrenewal"];
                    $type = "";
                }
                $tax = $CONFIG["TaxEnabled"] && $CONFIG["TaxDomains"] ? "1" : "0";
                $domaindesc .= " - " . $domain . " - " . $registrationperiod . " " . $_LANG["orderyears"];
                if ($type != "Transfer") {
                    $domaindesc .= " (" . fromMySQLDate($expirydate) . " - " . fromMySQLDate(getInvoicePayUntilDate($expirydate, $registrationperiod)) . ")";
                }
                if ($dnsmanagement) {
                    $domaindesc .= "\n + " . $_LANG["domaindnsmanagement"];
                }
                if ($emailforwarding) {
                    $domaindesc .= "\n + " . $_LANG["domainemailforwarding"];
                }
                if ($idprotection) {
                    $domaindesc .= "\n + " . $_LANG["domainidprotection"];
                }
                $promo_description = $promo_amount = 0;
                if ($promoid) {
                    $data = get_query_vals("tblpromotions", "", array("id" => $promoid));
                    $promo_id = $data["id"];
                    if ($promo_id) {
                        $promo_code = $data["code"];
                        $promo_type = $data["type"];
                        $promo_recurring = $data["recurring"];
                        $promo_value = $data["value"];
                        if ($promo_recurring || !$promo_recurring && $regdate == $nextduedate) {
                            if ($promo_type == "Percentage") {
                                $promo_amount = round($amount / (1 - $promo_value / 100), 2) - $amount;
                                $promo_value .= "%";
                            } else {
                                if ($promo_type == "Fixed Amount") {
                                    $promo_amount = $promo_value;
                                    $currency = getCurrency($userid);
                                    $promo_value = formatCurrency($promo_value);
                                }
                            }
                            $amount += $promo_amount;
                            $promo_recurring = $promo_recurring ? $_LANG["recurring"] : $_LANG["orderpaymenttermonetime"];
                            $promo_description = $_LANG["orderpromotioncode"] . ": " . $promo_code . " - " . $promo_value . " " . $promo_recurring . " " . $_LANG["orderdiscount"];
                            $promo_amount *= -1;
                        }
                    }
                }
                insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "Domain" . $type, "relid" => $id, "description" => $domaindesc, "amount" => $amount, "taxed" => $tax, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod));
                if ($promo_description) {
                    insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "PromoDomain", "relid" => $id, "description" => $promo_description, "amount" => $promo_amount, "taxed" => $tax, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod));
                }
            } else {
                if (!$contblock && $continvoicegen) {
                    $year = substr($nextduedate, 0, 4);
                    $month = substr($nextduedate, 5, 2);
                    $day = substr($nextduedate, 8, 2);
                    $new_time = mktime(0, 0, 0, $month, $day, $year + $registrationperiod);
                    $nextinvoicedate = date("Y-m-d", $new_time);
                    update_query("tbldomains", array("nextinvoicedate" => $nextinvoicedate), array("id" => $id));
                }
            }
            getUsersLang(0);
        }
    }
    if (!is_array($specificitems)) {
        $billableitemstax = $CONFIG["TaxEnabled"] && $CONFIG["TaxBillableItems"] ? "1" : "0";
        $result = select_query("tblbillableitems", "", "((invoiceaction='1' AND invoicecount='0') OR (invoiceaction='3' AND invoicecount='0' AND duedate<='" . $invoicedate . "') OR (invoiceaction='4' AND duedate<='" . $invoicedate . "' AND (recurfor='0' OR invoicecount<recurfor)))" . $billableitemqry);
        while ($data = mysql_fetch_array($result)) {
            $paymentmethod = getClientsPaymentMethod($data["userid"]);
            if ($data["invoiceaction"] != "4") {
                insert_query("tblinvoiceitems", array("userid" => $data["userid"], "type" => "Item", "relid" => $data["id"], "description" => $data["description"], "amount" => $data["amount"], "taxed" => $billableitemstax, "duedate" => $data["duedate"], "paymentmethod" => $paymentmethod));
            }
            $updatearray = array("invoicecount" => "+1");
            if ($data["invoiceaction"] == "4") {
                $num_rows = get_query_val("tblinvoiceitems", "COUNT(id)", array("type" => "Item", "relid" => $data["id"], "duedate" => $data["duedate"]));
                if ($num_rows == 0) {
                    insert_query("tblinvoiceitems", array("userid" => $data["userid"], "type" => "Item", "relid" => $data["id"], "description" => $data["description"], "amount" => $data["amount"], "taxed" => $billableitemstax, "duedate" => $data["duedate"], "paymentmethod" => $paymentmethod));
                }
                $adddays = $addmonths = $addyears = 0;
                if ($data["recurcycle"] == "Days") {
                    $adddays = $data["recur"];
                } else {
                    if ($data["recurcycle"] == "Weeks") {
                        $adddays = $data["recur"] * 7;
                    } else {
                        if ($data["recurcycle"] == "Months") {
                            $addmonths = $data["recur"];
                        } else {
                            if ($data["recurcycle"] == "Years") {
                                $addyears = $data["recur"];
                            }
                        }
                    }
                }
                $year = substr($data["duedate"], 0, 4);
                $month = substr($data["duedate"], 5, 2);
                $day = substr($data["duedate"], 8, 2);
                $updatearray["duedate"] = date("Y-m-d", mktime(0, 0, 0, $month + $addmonths, $day + $adddays, $year + $addyears));
            }
            update_query("tblbillableitems", $updatearray, array("id" => $data["id"]));
        }
    }
    run_hook("AfterInvoicingGenerateInvoiceItems", array());
    $invoicecount = $invoiceid = 0;
    $where = array();
    $where[] = "invoiceid=0";
    if ($func_userid) {
        $where[] = "userid=" . (int) $func_userid;
    }
    if (!is_array($specificitems)) {
        $where[] = "tblclients.separateinvoices='0'";
        $where[] = "(tblclientgroups.separateinvoices='0' OR tblclientgroups.separateinvoices = '' OR tblclientgroups.separateinvoices is null)";
    }
    $result = select_query("tblinvoiceitems", "DISTINCT tblinvoiceitems.userid,tblinvoiceitems.duedate,tblinvoiceitems.paymentmethod", implode(" AND ", $where), "duedate", "ASC", "", "tblclients ON tblclients.id=tblinvoiceitems.userid LEFT JOIN tblclientgroups ON tblclientgroups.id=tblclients.groupid");
    while ($data = mysql_fetch_array($result)) {
        createInvoicesProcess($data, $noemails, $nocredit);
    }
    if (!is_array($specificitems)) {
        $where = array();
        $where[] = "invoiceid=0";
        if ($func_userid) {
            $where[] = "userid=" . (int) $func_userid;
        }
        $where[] = "(tblclients.separateinvoices='on' OR tblclients.separateinvoices='1' OR tblclientgroups.separateinvoices='on')";
        $result = select_query("tblinvoiceitems", "tblinvoiceitems.id,tblinvoiceitems.userid,tblinvoiceitems.type,tblinvoiceitems.relid,tblinvoiceitems.duedate,tblinvoiceitems.paymentmethod", implode(" AND ", $where), "duedate", "ASC", "", "tblclients ON tblclients.id=tblinvoiceitems.userid LEFT JOIN tblclientgroups ON tblclientgroups.id=tblclients.groupid");
        while ($data = mysql_fetch_array($result)) {
            createInvoicesProcess($data, $noemails, $nocredit);
        }
    }
    if ($task) {
        $task->output("invoice.created")->write($invoicecount);
    }
    if ($func_userid) {
        return $invoiceid;
    }
}
function createInvoicesProcess($data, $noemails = "", $nocredit = "")
{
    global $whmcs;
    global $CONFIG;
    global $_LANG;
    global $invoicecount;
    global $invoiceid;
    $itemid = $data["id"];
    $userid = $data["userid"];
    $type = $data["type"];
    $relid = $data["relid"];
    $duedate = $data["duedate"];
    $paymentmethod = $invpaymentmethod = $data["paymentmethod"];
    $gateways = new WHMCS\Gateways();
    if (!$invpaymentmethod || !$gateways->isActiveGateway($invpaymentmethod)) {
        $invpaymentmethod = ensurePaymentMethodIsSet($userid, $itemid, "tblinvoiceitems");
    }
    $where = array("userid" => $userid, "duedate" => $duedate, "paymentmethod" => $paymentmethod, "invoiceid" => "0");
    if (!empty($itemid)) {
        $where["id"] = $itemid;
    }
    if (is_null(get_query_val("tblinvoiceitems", "id", $where))) {
        return false;
    }
    unset($where);
    $invoice = WHMCS\Billing\Invoice::newInvoice($userid, $invpaymentmethod);
    $invoice->duedate = $duedate;
    $invoice->setStatusUnpaid()->save();
    $invoiceid = $invoice->id;
    if ($paymentmethod != $invpaymentmethod) {
        logActivity(sprintf("Invalid payment method updated on invoice generation from '%s' to '%s' for Invoice ID: %d", $paymentmethod, $invpaymentmethod, $invoiceid), $userid);
    }
    if ($itemid) {
        update_query("tblinvoiceitems", array("invoiceid" => $invoiceid), array("invoiceid" => "0", "userid" => $userid, "type" => "Promo" . $type, "relid" => $relid));
        $where = array("id" => $itemid);
    } else {
        $where = array("invoiceid" => "", "duedate" => $duedate, "userid" => $userid, "paymentmethod" => $paymentmethod);
    }
    update_query("tblinvoiceitems", array("invoiceid" => $invoiceid), $where);
    logActivity("Created Invoice - Invoice ID: " . $invoiceid, $userid);
    $billableitemstax = $CONFIG["TaxEnabled"] && $CONFIG["TaxCustomInvoices"] ? "1" : "0";
    $result2 = select_query("tblbillableitems", "", array("userid" => $userid, "invoiceaction" => "2", "invoicecount" => "0"));
    while ($data = mysql_fetch_array($result2)) {
        insert_query("tblinvoiceitems", array("invoiceid" => $invoiceid, "userid" => $userid, "type" => "Item", "relid" => $data["id"], "description" => $data["description"], "amount" => $data["amount"], "taxed" => $billableitemstax));
        update_query("tblbillableitems", array("invoicecount" => "+1"), array("id" => $data["id"]));
    }
    updateInvoiceTotal($invoiceid);
    $data2 = get_query_vals("tblclients", "credit,groupid", array("id" => $userid));
    $credit = $data2["credit"];
    $groupid = $data2["groupid"];
    $data2 = get_query_vals("tblinvoices", "subtotal,total", array("id" => $invoiceid));
    $subtotal = $data2["subtotal"];
    $total = $data2["total"];
    $invoiceLineItems = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", $invoiceid)->get();
    $isaddfundsinvoice = 0 < count(array_filter($invoiceLineItems, function ($lineItem) {
        return (bool) in_array($lineItem->type, array("AddFunds", "Invoice"));
    }));
    if ($groupid && !$isaddfundsinvoice) {
        $discountPercent = get_query_val("tblclientgroups", "discountpercent", array("id" => $groupid));
        if (0 < $discountPercent) {
            foreach ($invoiceLineItems as $lineItem) {
                $discountAmount = $lineItem->amount * $discountPercent / 100 * -1;
                insert_query("tblinvoiceitems", array("invoiceid" => $invoiceid, "userid" => $userid, "type" => "GroupDiscount", "description" => $_LANG["clientgroupdiscount"] . " - " . $lineItem->description, "amount" => $discountAmount, "taxed" => $lineItem->taxed));
            }
            updateInvoiceTotal($invoiceid);
            $data2 = get_query_vals("tblclients", "credit,groupid", array("id" => $userid));
            $credit = $data2["credit"];
            $groupid = $data2["groupid"];
            $data2 = get_query_vals("tblinvoices", "subtotal,total", array("id" => $invoiceid));
            $subtotal = $data2["subtotal"];
            $total = $data2["total"];
        }
    }
    if (WHMCS\Config\Setting::getValue("ContinuousInvoiceGeneration")) {
        $result2 = select_query("tblinvoiceitems", "", array("invoiceid" => $invoiceid));
        while ($data = mysql_fetch_array($result2)) {
            $type = $data["type"];
            $relid = $data["relid"];
            $nextinvoicedate = $data["duedate"];
            $year = substr($nextinvoicedate, 0, 4);
            $month = substr($nextinvoicedate, 5, 2);
            $day = substr($nextinvoicedate, 8, 2);
            $proratabilling = false;
            if ($type == "Hosting") {
                $data = get_query_vals("tblhosting", "billingcycle,packageid,regdate,nextduedate", array("id" => $relid));
                $billingcycle = $data["billingcycle"];
                $packageid = $data["packageid"];
                $regdate = $data["regdate"];
                $nextduedate = $data["nextduedate"];
                $data = get_query_vals("tblproducts", "proratabilling,proratadate,proratachargenextmonth", array("id" => $packageid));
                $proratabilling = $data["proratabilling"];
                $proratadate = $data["proratadate"];
                $proratachargenextmonth = $data["proratachargenextmonth"];
                $proratamonths = getBillingCycleMonths($billingcycle);
                $nextinvoicedate = date("Y-m-d", mktime(0, 0, 0, $month + $proratamonths, $day, $year));
            } else {
                if ($type == "Domain" || $type == "DomainRegister" || $type == "DomainTransfer") {
                    $data = get_query_vals("tbldomains", "registrationperiod,nextduedate", array("id" => $relid));
                    $registrationperiod = $data["registrationperiod"];
                    $nextduedate = explode("-", $data["nextduedate"]);
                    $billingcycle = "";
                    $nextinvoicedate = date("Y-m-d", mktime(0, 0, 0, $nextduedate[1], $nextduedate[2], $nextduedate[0] + $registrationperiod));
                } else {
                    if ($type == "Addon") {
                        $billingcycle = get_query_val("tblhostingaddons", "billingcycle", array("id" => $relid));
                        $proratamonths = getBillingCycleMonths($billingcycle);
                        $nextinvoicedate = date("Y-m-d", mktime(0, 0, 0, $month + $proratamonths, $day, $year));
                    }
                }
            }
            if ($billingcycle == "One Time") {
                $nextinvoicedate = "0000-00-00";
            }
            if ($regdate == $nextduedate && $proratabilling) {
                if ($billingcycle != "Monthly") {
                    $proratachargenextmonth = 0;
                }
                $orderyear = substr($regdate, 0, 4);
                $ordermonth = substr($regdate, 5, 2);
                $orderday = substr($regdate, 8, 2);
                if ($orderday < $proratadate) {
                    $proratamonth = $ordermonth;
                } else {
                    $proratamonth = $ordermonth + 1;
                }
                $days = (strtotime(date("Y-m-d", mktime(0, 0, 0, $proratamonth, $proratadate, $orderyear))) - strtotime(date("Y-m-d"))) / (60 * 60 * 24);
                $totaldays = 30;
                $nextinvoicedate = date("Y-m-d", mktime(0, 0, 0, $proratamonth, $proratadate, $orderyear));
                if ($proratachargenextmonth <= $orderday && $days < 31) {
                    $nextinvoicedate = date("Y-m-d", mktime(0, 0, 0, $proratamonth + $proratamonths, $proratadate, $orderyear));
                }
            }
            if ($type == "Hosting") {
                update_query("tblhosting", array("nextinvoicedate" => $nextinvoicedate), array("id" => $relid));
            } else {
                if ($type == "Domain" || $type == "DomainRegister" || $type == "DomainTransfer") {
                    update_query("tbldomains", array("nextinvoicedate" => $nextinvoicedate), array("id" => $relid));
                } else {
                    if ($type == "Addon") {
                        update_query("tblhostingaddons", array("nextinvoicedate" => $nextinvoicedate), array("id" => $relid));
                    }
                }
            }
        }
    }
    $doprocesspaid = false;
    $inShoppingCart = defined("SHOPPING_CART");
    if (!$nocredit && $credit != "0.00" && ($inShoppingCart && App::getFromRequest("applycredit") || !$inShoppingCart && !WHMCS\Config\Setting::getValue("NoAutoApplyCredit"))) {
        if ($total <= $credit) {
            $creditleft = $credit - $total;
            $credit = $total;
            $doprocesspaid = true;
        } else {
            $creditleft = 0;
        }
        if (!$inShoppingCart) {
            logActivity("Credit Automatically Applied at Invoice Creation - Invoice ID: " . $invoiceid . " - Amount: " . $credit, $userid);
        } else {
            logActivity("Credit Applied at Client Request on Checkout - Invoice ID: " . $invoiceid . " - Amount: " . $credit, $userid);
        }
        insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Credit Applied to Invoice #" . $invoiceid, "amount" => $credit * -1));
        update_query("tblclients", array("credit" => $creditleft), array("id" => $userid));
        update_query("tblinvoices", array("credit" => $credit), array("id" => $invoiceid));
        updateInvoiceTotal($invoiceid);
    }
    $invoice = WHMCS\Billing\Invoice::find($invoiceid);
    $invoice->save();
    $invoiceArr = array("source" => "autogen", "user" => WHMCS\Session::get("adminid") ? WHMCS\Session::get("adminid") : "system", "invoiceid" => $invoiceid, "status" => "Unpaid");
    run_hook("InvoiceCreation", $invoiceArr);
    $result2 = select_query("tblpaymentgateways", "value", array("gateway" => $invpaymentmethod, "setting" => "type"));
    $data2 = mysql_fetch_array($result2);
    $paymenttype = $data2["value"];
    if ($noemails != "true") {
        run_hook("InvoiceCreationPreEmail", $invoiceArr);
        sendMessage(($paymenttype == "CC" || $paymenttype == "OfflineCC" ? "Credit Card " : "") . "Invoice Created", $invoiceid);
    }
    run_hook("InvoiceCreated", $invoiceArr);
    $total = $invoice->total;
    if ($total == "0.00") {
        $doprocesspaid = true;
    }
    WHMCS\Session::set("InOrderButNeedProcessPaidInvoiceAction", false);
    if ($doprocesspaid) {
        if (defined("INORDERFORM")) {
            WHMCS\Session::set("InOrderButNeedProcessPaidInvoiceAction", true);
        } else {
            processPaidInvoice($invoiceid);
        }
    }
    $invoicetotal = 0;
    $invoicecount++;
    WHMCS\Invoices::adjustIncrementForNextInvoice($invoiceid);
}
function getInvoiceProductDetails($id, $pid, $regdate, $nextduedate, $billingcycle, $domain, $userid)
{
    global $CONFIG;
    global $_LANG;
    global $currency;
    $data = get_query_vals("tblproducts", "name,type,tax,proratabilling,proratadate,proratachargenextmonth,recurringcycles", array("id" => $pid));
    $type = $data["type"];
    $clientLanguage = WHMCS\User\Client::find($userid, array("language"))->language ?: NULL;
    $package = WHMCS\Product\Product::getProductName($pid, $data["name"], $clientLanguage);
    $tax = $data["tax"];
    $proratabilling = $data["proratabilling"];
    $proratadate = $data["proratadate"];
    $proratachargenextmonth = $data["proratachargenextmonth"];
    $recurringcycles = $data["recurringcycles"];
    $userid = get_query_val("tblhosting", "userid", array("id" => $id));
    $currency = getCurrency($userid);
    if ($tax && $CONFIG["TaxEnabled"]) {
        $tax = "1";
    } else {
        $tax = "0";
    }
    $paydates = "";
    if ($regdate || $nextduedate) {
        if ($regdate == $nextduedate && $proratabilling) {
            $orderyear = substr($regdate, 0, 4);
            $ordermonth = substr($regdate, 5, 2);
            $orderday = substr($regdate, 8, 2);
            $proratavalues = getProrataValues($billingcycle, 0, $proratadate, $proratachargenextmonth, $orderday, $ordermonth, $orderyear, $userid);
            $invoicepayuntildate = $proratavalues["invoicedate"];
        } else {
            $invoicepayuntildate = getInvoicePayUntilDate($nextduedate, $billingcycle);
        }
        if ($billingcycle != "One Time") {
            $paydates = " (" . fromMySQLDate($nextduedate) . " - " . fromMySQLDate($invoicepayuntildate) . ")";
        }
    }
    $description = $package;
    if ($domain) {
        $description .= " - " . $domain;
    }
    $description .= $paydates;
    $configbillingcycle = $billingcycle;
    if ($configbillingcycle == "One Time" || $configbillingcycle == "Free Account") {
        $configbillingcycle = "monthly";
    }
    $configbillingcycle = strtolower(str_replace("-", "", $configbillingcycle));
    $query = "SELECT tblproductconfigoptions.id, tblproductconfigoptions.optionname AS confoption, tblproductconfigoptions.optiontype AS conftype, tblproductconfigoptionssub.optionname, tblhostingconfigoptions.qty,tblhostingconfigoptions.optionid FROM tblhostingconfigoptions INNER JOIN tblproductconfigoptions ON tblproductconfigoptions.id = tblhostingconfigoptions.configid INNER JOIN tblproductconfigoptionssub ON tblproductconfigoptionssub.id = tblhostingconfigoptions.optionid INNER JOIN tblhosting ON tblhosting.id=tblhostingconfigoptions.relid INNER JOIN tblproductconfiglinks ON tblproductconfiglinks.gid=tblproductconfigoptions.gid WHERE tblhostingconfigoptions.relid=" . (int) $id . " AND tblproductconfigoptions.hidden='0' AND tblproductconfigoptionssub.hidden='0' AND tblproductconfiglinks.pid=tblhosting.packageid ORDER BY tblproductconfigoptions.`order`,tblproductconfigoptions.id ASC";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $confoption = $data["confoption"];
        $conftype = $data["conftype"];
        if (strpos($confoption, "|")) {
            $confoption = explode("|", $confoption);
            $confoption = trim($confoption[1]);
        }
        $optionname = $data["optionname"];
        $optionqty = $data["qty"];
        $optionid = $data["optionid"];
        if (strpos($optionname, "|")) {
            $optionname = explode("|", $optionname);
            $optionname = trim($optionname[1]);
        }
        if ($conftype == 3) {
            if ($optionqty) {
                $optionname = $_LANG["yes"];
            } else {
                $optionname = $_LANG["no"];
            }
        } else {
            if ($conftype == 4) {
                $optionname = (string) $optionqty . " x " . $optionname . " ";
                $qtyprice = get_query_val("tblpricing", $configbillingcycle, array("type" => "configoptions", "currency" => $currency["id"], "relid" => $optionid));
                $optionname .= formatCurrency($qtyprice);
            }
        }
        $description .= "\n" . $confoption . ": " . $optionname;
    }
    $result = select_query("tblcustomfields", "tblcustomfields.id,tblcustomfields.fieldname,(SELECT value FROM tblcustomfieldsvalues WHERE tblcustomfieldsvalues.fieldid=tblcustomfields.id AND tblcustomfieldsvalues.relid=" . (int) $id . " LIMIT 1) AS value", array("type" => "product", "relid" => $pid, "showinvoice" => "on"));
    while ($data = mysql_fetch_assoc($result)) {
        if ($data["value"]) {
            $data["fieldname"] = WHMCS\CustomField::getFieldName($data["id"], $data["fieldname"], $clientLanguage);
            $description .= "\n" . $data["fieldname"] . ": " . $data["value"];
        }
    }
    return array("description" => $description, "tax" => $tax, "recurringcycles" => $recurringcycles);
}
function getInvoiceProductPromo($amount, $promoid, $userid = "", $serviceid = "", $orderamt = "")
{
    global $_LANG;
    global $currency;
    if (!$promoid) {
        return array();
    }
    $data = get_query_vals("tblpromotions", "", array("id" => $promoid));
    $promo_id = $data["id"];
    if (!$promo_id) {
        return array();
    }
    $promo_code = $data["code"];
    $promo_type = $data["type"];
    $promo_recurring = $data["recurring"];
    $promo_value = $data["value"];
    $promo_recurfor = $data["recurfor"];
    if ($userid) {
        $currency = getCurrency($userid);
    }
    if ($serviceid) {
        $data = get_query_vals("tblhosting", "packageid,regdate,nextduedate,firstpaymentamount,billingcycle", array("id" => $serviceid));
        $pid = $data["packageid"];
        $regdate = $data["regdate"];
        $nextduedate = $data["nextduedate"];
        $firstpaymentamount = $data["firstpaymentamount"];
        $billingcycle = $data["billingcycle"];
        $billingcycle = str_replace("-", "", strtolower($billingcycle));
        if ($billingcycle == "one time") {
            $billingcycle = "monthly";
        }
    }
    if ($serviceid && $promo_recurring && 0 < $promo_recurfor) {
        $promo_recurringcount = get_query_val("tblinvoiceitems", "COUNT(id)", array("userid" => $userid, "type" => "Hosting", "relid" => $serviceid));
        if ($promo_recurfor - 1 <= $promo_recurringcount) {
            $fullAmount = getInvoiceProductDefaultPrice($pid, $billingcycle, $regdate, $nextduedate);
            if (!function_exists("getCartConfigOptions")) {
                require ROOTDIR . "/includes/configoptionsfunctions.php";
            }
            $configoptions = getCartConfigOptions($pid, "", $billingcycle, $serviceid);
            foreach ($configoptions as $configoption) {
                $fullAmount += $configoption["selectedrecurring"];
            }
            update_query("tblhosting", array("amount" => $fullAmount, "promoid" => "0"), array("id" => $serviceid));
        }
    }
    if (!$promo_id) {
        return array();
    }
    if (!$serviceid || $promo_recurring || !$promo_recurring && $regdate == $nextduedate) {
        if ($promo_type == "Percentage") {
            if ($promo_value != 100) {
                $promo_amount = round($amount / (1 - $promo_value / 100), 2) - $amount;
            } else {
                $promo_amount = 0;
            }
            if ($orderamt) {
                $promoAmountCheck = $promo_amount + $amount;
                if ($promoAmountCheck < $orderamt) {
                    $promo_amount = $promo_amount + $orderamt - $promoAmountCheck;
                }
            }
            if (0 < $promo_value && $promo_amount <= 0) {
                $promo_amount = $orderamt ? $orderamt : getInvoiceProductDefaultPrice($pid, $billingcycle, $regdate, $nextduedate);
            }
            $promo_value .= "%";
        } else {
            if ($promo_type == "Fixed Amount") {
                if ($currency["id"] != 1) {
                    $promo_value = convertCurrency($promo_value, 1, $currency["id"]);
                }
                $default_price = "";
                $default_price = getInvoiceProductDefaultPrice($pid, $billingcycle, $regdate, $nextduedate, $serviceid, $userid);
                if ($default_price < $promo_value) {
                    $promo_value = $default_price;
                }
                $default_price = "";
                $promo_amount = $promo_value;
                $promo_value = formatCurrency($promo_value);
            } else {
                if ($promo_type == "Price Override") {
                    if ($currency["id"] != 1) {
                        $promo_value = convertCurrency($promo_value, 1, $currency["id"]);
                    }
                    $promo_amount = $orderamt ? $orderamt : getInvoiceProductDefaultPrice($pid, $billingcycle, $regdate, $nextduedate);
                    $promo_amount -= $promo_value;
                    $promo_value = formatCurrency($promo_value) . " " . $_LANG["orderpromopriceoverride"];
                } else {
                    if ($promo_type == "Free Setup") {
                        $promo_amount = $orderamt ? $orderamt : getInvoiceProductDefaultPrice($pid, $billingcycle, $regdate, $nextduedate);
                        $promo_amount -= $firstpaymentamount;
                        $promo_value = $_LANG["orderpromofreesetup"];
                    }
                }
            }
        }
        getUsersLang($userid);
        $promo_recurring = $promo_recurring ? $_LANG["recurring"] : $_LANG["orderpaymenttermonetime"];
        $promo_description = $_LANG["orderpromotioncode"] . ": " . $promo_code . " - " . $promo_value . " " . $promo_recurring . " " . $_LANG["orderdiscount"];
        getUsersLang(0);
        return array("description" => $promo_description, "amount" => $promo_amount * -1);
    }
    return array();
}
function getInvoiceProductDefaultPrice($pid, $billingCycle, $regDate, $nextDueDate, $serviceID = 0, $userID = 0)
{
    global $currency;
    $data = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "product")->where("currency", "=", $currency["id"])->where("relid", "=", $pid)->first();
    $amount = 0;
    switch ($billingCycle) {
        case "one time":
        case "monthly":
            $setupFieldName = "msetupfee";
            $amount = $data->monthly;
            break;
        case "quarterly":
            $setupFieldName = "qsetupfee";
            $amount = $data->quarterly;
            break;
        case "semiannually":
            $setupFieldName = "ssetupfee";
            $amount = $data->semiannually;
            break;
        case "annually":
            $setupFieldName = "asetupfee";
            $amount = $data->annually;
            break;
        case "biennially":
            $setupFieldName = "bsetupfee";
            $amount = $data->biennially;
            break;
        case "triennially":
            $setupFieldName = "tsetupfee";
            $amount = $data->triennally;
            break;
        default:
            throw new WHMCS\Exception("Unable to obtain pricing for billing cycle");
    }
    if ($regDate == $nextDueDate && isset($setupFieldName)) {
        $amount += $data->{$setupFieldName};
    }
    if ($serviceID) {
        if (!function_exists("recalcRecurringProductPrice")) {
            require ROOTDIR . "/includes/clientfunctions.php";
        }
        if ($billingCycle == "semiannually") {
            $billingCycle = "Semi-Annually";
        } else {
            $billingCycle = ucfirst($billingCycle);
        }
        $includeSetup = false;
        if ($regDate == $nextDueDate) {
            $includeSetup = true;
        }
        $amount = recalcRecurringProductPrice($serviceID, $userID, $pid, $billingCycle, "empty", 0, $includeSetup);
    }
    return $amount;
}
function cancelUnpaidUpgrade($serviceId)
{
    if (empty($serviceId) || !is_int($serviceId)) {
        return false;
    }
    if (!function_exists("changeOrderStatus")) {
        include ROOTDIR . "/includes/orderfunctions.php";
    }
    static $cancelledStatuses = NULL;
    if (!is_array($cancelledStatuses)) {
        $cancelledStatuses = WHMCS\Database\Capsule::table("tblorderstatuses")->where("showcancelled", 1)->pluck("title");
        $cancelledStatuses[] = "Fraud";
    }
    $upgrades = WHMCS\Database\Capsule::table("tblupgrades")->leftJoin("tblorders", "tblorders.id", "=", "tblupgrades.orderid")->where("tblupgrades.relid", "=", $serviceId)->where("tblupgrades.paid", "=", "N")->whereNotIn("tblorders.status", $cancelledStatuses)->get();
    foreach ($upgrades as $upgrade) {
        changeOrderStatus($upgrade->orderid, "Cancelled");
        $extraData = array("order_id" => $upgrade->orderid, "order_number" => get_query_val("tblorders", "ordernum", array("id" => $upgrade->orderid)), "upgrade_type" => $upgrade->type, "order_date" => fromMySQLDate($upgrade->date, "", true), "order_amount" => formatCurrency($upgrade->amount), "recurring_amount_change" => formatCurrency($upgrade->recurringchange));
        sendMessage("Upgrade Order Cancelled", $serviceId, $extraData);
    }
    return true;
}

?>