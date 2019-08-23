<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Invoices extends TableModel
{
    protected static $invoiceStatusValues = array("Draft", "Unpaid", "Paid", "Cancelled", "Refunded", "Collections", "Payment Pending");
    public function _execute($criteria = NULL)
    {
        return $this->getInvoices($criteria);
    }
    public function getInvoices($criteria = array())
    {
        global $aInt;
        global $currency;
        $query = " FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid";
        $filters = $this->buildCriteria($criteria);
        $query .= count($filters) ? " WHERE " . implode(" AND ", $filters) : "";
        $result = full_query("SELECT COUNT(*)" . $query);
        $data = mysql_fetch_array($result);
        $this->getPageObj()->setNumResults($data[0]);
        $gateways = new Gateways();
        $gatewaysAndTypes = Database\Capsule::table("tblpaymentgateways")->where("setting", "=", "type")->pluck("value", "gateway");
        $orderby = $this->getPageObj()->getOrderBy();
        if ($orderby == "clientname") {
            $orderby = "firstname " . $this->getPageObj()->getSortDirection() . ",lastname " . $this->getPageObj()->getSortDirection() . ",companyname";
        }
        if ($orderby == "id") {
            $orderby = "tblinvoices.invoicenum " . $this->getPageObj()->getSortDirection() . ",tblinvoices.id";
        }
        $invoices = array();
        $query = "SELECT tblinvoices.*,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid,tblclients.currency" . $query . " ORDER BY " . $orderby . " " . $this->getPageObj()->getSortDirection() . " LIMIT " . $this->getQueryLimit();
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $invoicenum = $data["invoicenum"];
            $userid = $data["userid"];
            $date = $data["date"];
            $duedate = $data["duedate"];
            $subtotal = $data["subtotal"];
            $credit = $data["credit"];
            $total = $data["total"];
            $gateway = $data["paymentmethod"];
            $status = $data["status"];
            $lastCaptureAttempt = $data["last_capture_attempt"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $groupid = $data["groupid"];
            $currency = $data["currency"];
            $clientname = $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid);
            $paymentmethod = $gateways->getDisplayName($gateway);
            $currency = getCurrency("", $currency);
            $totalformatted = formatCurrency($credit + $total);
            $statusformatted = $this->formatStatus($status);
            $date = fromMySQLDate($date);
            $duedate = fromMySQLDate($duedate);
            $lastCaptureAttempt = $gatewaysAndTypes[$gateway] == "CC" ? $lastCaptureAttempt != "0000-00-00 00:00:00" ? fromMySQLDate($lastCaptureAttempt) : "-" : \AdminLang::trans("global.na");
            if (!$invoicenum) {
                $invoicenum = $id;
            }
            $invoices[] = array("id" => $id, "invoicenum" => $invoicenum, "userid" => $userid, "clientname" => $clientname, "date" => $date, "duedate" => $duedate, "lastCaptureAttempt" => $lastCaptureAttempt, "subtotal" => $subtotal, "credit" => $credit, "total" => $total, "totalformatted" => $totalformatted, "gateway" => $gateway, "paymentmethod" => $paymentmethod, "status" => $status, "statusformatted" => $statusformatted);
        }
        return $invoices;
    }
    private function buildCriteria($criteria)
    {
        $filters = array();
        if ($criteria["clientid"]) {
            $filters[] = "userid=" . (int) $criteria["clientid"];
        }
        if ($criteria["clientname"]) {
            $filters[] = "concat(firstname,' ',lastname) LIKE '%" . db_escape_string($criteria["clientname"]) . "%'";
        }
        if ($criteria["invoicenum"]) {
            $filters[] = "(tblinvoices.id='" . db_escape_string($criteria["invoicenum"]) . "' OR tblinvoices.invoicenum='" . db_escape_string($criteria["invoicenum"]) . "')";
        }
        if ($criteria["lineitem"]) {
            $filters[] = "tblinvoices.id IN (SELECT invoiceid FROM tblinvoiceitems WHERE description LIKE '%" . db_escape_string($criteria["lineitem"]) . "%')";
        }
        if ($criteria["paymentmethod"]) {
            $filters[] = "tblinvoices.paymentmethod='" . db_escape_string($criteria["paymentmethod"]) . "'";
        }
        if ($criteria["invoicedate"]) {
            $dateRange = $criteria["invoicedate"];
            $dateRange = Carbon::parseDateRangeValue($dateRange);
            $dateFrom = $dateRange["from"];
            $dateTo = $dateRange["to"];
            $filters[] = "tblinvoices.date >= '" . $dateFrom->toDateTimeString() . "'" . " AND tblinvoices.date <= '" . $dateTo->toDateTimeString() . "'";
        }
        if ($criteria["duedate"]) {
            $dateRange = $criteria["duedate"];
            $dateRange = Carbon::parseDateRangeValue($dateRange);
            $dateFrom = $dateRange["from"];
            $dateTo = $dateRange["to"];
            $filters[] = "tblinvoices.duedate >= '" . $dateFrom->toDateTimeString() . "'" . " AND tblinvoices.duedate <= '" . $dateTo->toDateTimeString() . "'";
        }
        if ($criteria["datepaid"]) {
            $dateRange = $criteria["datepaid"];
            $dateRange = Carbon::parseDateRangeValue($dateRange);
            $dateFrom = $dateRange["from"];
            $dateTo = $dateRange["to"];
            $filters[] = "tblinvoices.datepaid >= '" . $dateFrom->toDateTimeString() . "'" . " AND tblinvoices.datepaid <= '" . $dateTo->toDateTimeString() . "'";
        }
        if (array_key_exists("last_capture_attempt", $criteria) && $criteria["last_capture_attempt"]) {
            $dateRange = $criteria["last_capture_attempt"];
            $dateRange = Carbon::parseDateRangeValue($dateRange);
            $dateFrom = $dateRange["from"];
            $dateTo = $dateRange["to"];
            $filters[] = "tblinvoices.last_capture_attempt >= '" . $dateFrom->toDateTimeString() . "'" . " AND tblinvoices.last_capture_attempt <= '" . $dateTo->toDateTimeString() . "'";
        }
        if ($criteria["totalfrom"]) {
            $filters[] = "tblinvoices.total>='" . db_escape_string($criteria["totalfrom"]) . "'";
        }
        if ($criteria["totalto"]) {
            $filters[] = "tblinvoices.total<='" . db_escape_string($criteria["totalto"]) . "'";
        }
        if ($criteria["status"]) {
            if ($criteria["status"] == "Overdue") {
                $filters[] = "tblinvoices.status='Unpaid' AND tblinvoices.duedate<'" . date("Ymd") . "'";
            } else {
                $filters[] = "tblinvoices.status='" . db_escape_string($criteria["status"]) . "'";
            }
        }
        return $filters;
    }
    public function formatStatus($status)
    {
        if (defined("ADMINAREA")) {
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
                                        $status = "<span class=\"textgreen\">" . \AdminLang::trans("status.paymentpending") . "</span>";
                                    } else {
                                        $status = "Unrecognised";
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
                                } else {
                                    $status = "Unrecognised";
                                }
                            }
                        }
                    }
                }
            }
        }
        return $status;
    }
    public function getInvoiceTotals()
    {
        global $currency;
        $invoicesummary = array();
        $result = full_query("SELECT currency,COUNT(tblinvoices.id),SUM(total) FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.status='Paid' GROUP BY tblclients.currency");
        while ($data = mysql_fetch_array($result)) {
            $invoicesummary[$data[0]]["paid"] = $data[2];
        }
        $result = full_query("SELECT currency,COUNT(tblinvoices.id),SUM(total)-COALESCE(SUM((SELECT SUM(amountin) FROM tblaccounts WHERE tblaccounts.invoiceid=tblinvoices.id)),0) FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.status='Unpaid' AND tblinvoices.duedate>='" . date("Ymd") . "' GROUP BY tblclients.currency");
        while ($data = mysql_fetch_array($result)) {
            $invoicesummary[$data[0]]["unpaid"] = $data[2];
        }
        $result = full_query("SELECT currency,COUNT(tblinvoices.id),SUM(total)-COALESCE(SUM((SELECT SUM(amountin) FROM tblaccounts WHERE tblaccounts.invoiceid=tblinvoices.id)),0) FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.status='Unpaid' AND tblinvoices.duedate<'" . date("Ymd") . "' GROUP BY tblclients.currency");
        while ($data = mysql_fetch_array($result)) {
            $invoicesummary[$data[0]]["overdue"] = $data[2];
        }
        $totals = array();
        foreach ($invoicesummary as $currency => $vals) {
            $currency = getCurrency("", $currency);
            if (!isset($vals["paid"])) {
                $vals["paid"] = 0;
            }
            if (!isset($vals["unpaid"])) {
                $vals["unpaid"] = 0;
            }
            if (!isset($vals["overdue"])) {
                $vals["overdue"] = 0;
            }
            $paid = formatCurrency($vals["paid"]);
            $unpaid = formatCurrency($vals["unpaid"]);
            $overdue = formatCurrency($vals["overdue"]);
            $totals[] = array("currencycode" => $currency["code"], "paid" => $paid, "unpaid" => $unpaid, "overdue" => $overdue);
        }
        return $totals;
    }
    public function duplicate($invoiceid)
    {
        $existingInvoice = Billing\Invoice::with("items")->find($invoiceid);
        $newInvoice = $existingInvoice->replicate(array("invoicenum"));
        $newInvoice->status = "Draft";
        $newInvoice->save();
        $userid = $newInvoice->clientId;
        $newid = $newInvoice->id;
        $newItems = array();
        foreach ($existingInvoice->items as $invoiceItem) {
            $newItems[] = $invoiceItem->replicate();
        }
        $newInvoice->items()->saveMany($newItems);
        logActivity("Duplicated Invoice - Existing Invoice ID: " . $invoiceid . " - New Invoice ID: " . $newid, $userid);
        return true;
    }
    public static function isSequentialPaidInvoiceNumberingEnabled()
    {
        $whmcs = Application::getInstance();
        return $whmcs->get_config("SequentialInvoiceNumbering") ? true : false;
    }
    public static function getNextSequentialPaidInvoiceNumber()
    {
        $numberToAssign = Config\Setting::getValue("SequentialInvoiceNumberFormat");
        $nextNumber = Database\Capsule::table("tblconfiguration")->where("setting", "SequentialInvoiceNumberValue")->value("value");
        Config\Setting::setValue("SequentialInvoiceNumberValue", self::padAndIncrement($nextNumber));
        $numberToAssign = str_replace("{YEAR}", date("Y"), $numberToAssign);
        $numberToAssign = str_replace("{MONTH}", date("m"), $numberToAssign);
        $numberToAssign = str_replace("{DAY}", date("d"), $numberToAssign);
        $numberToAssign = str_replace("{NUMBER}", $nextNumber, $numberToAssign);
        return $numberToAssign;
    }
    public static function padAndIncrement($number, $incrementAmount = 1)
    {
        $newNumber = $number + $incrementAmount;
        if (substr($number, 0, 1) == "0") {
            $numberLength = strlen($number);
            $newNumber = str_pad($newNumber, $numberLength, "0", STR_PAD_LEFT);
        }
        return $newNumber;
    }
    public static function adjustIncrementForNextInvoice($lastInvoiceId)
    {
        $incrementValue = (int) Config\Setting::getValue("InvoiceIncrement");
        if (1 < $incrementValue) {
            $incrementedId = $lastInvoiceId + $incrementValue - 1;
            insert_query("tblinvoices", array("id" => $incrementedId));
            delete_query("tblinvoices", array("id" => $incrementedId));
        }
    }
    public static function getInvoiceStatusValues()
    {
        return self::$invoiceStatusValues;
    }
}

?>