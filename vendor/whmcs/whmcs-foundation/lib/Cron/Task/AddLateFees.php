<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class AddLateFees extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1530;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Apply Late Fees";
    protected $defaultName = "Late Fees";
    protected $systemName = "AddLateFees";
    protected $outputs = array("invoice.latefees" => array("defaultValue" => 0, "identifier" => "invoice.latefees", "name" => "Late Fee Invoices"));
    protected $icon = "fas fa-gavel";
    protected $successCountIdentifier = "invoice.latefees";
    protected $successKeyword = "Added";
    public function __invoke()
    {
        $this->addLateFeesToInvoices();
        return $this;
    }
    protected function addLateFeesToInvoices()
    {
        $configTaxLateFee = \WHMCS\Config\Setting::getValue("TaxLateFee");
        $configInvoiceLateFeeAmount = \WHMCS\Config\Setting::getValue("InvoiceLateFeeAmount");
        $configAddLateFeeDays = \WHMCS\Config\Setting::getValue("AddLateFeeDays");
        $configLateFeeType = \WHMCS\Config\Setting::getValue("LateFeeType");
        $configLateFeeMinimum = \WHMCS\Config\Setting::getValue("LateFeeMinimum");
        if ($configTaxLateFee) {
            $taxlatefee = "1";
        }
        $invoiceids = array();
        if ($configInvoiceLateFeeAmount != "0.00") {
            if ($configAddLateFeeDays == "") {
                $configAddLateFeeDays = "0";
            }
            $adddate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $configAddLateFeeDays, date("Y")));
            $query = "SELECT tblinvoices.* FROM tblinvoices" . " INNER JOIN tblclients ON tblclients.id=tblinvoices.userid" . " WHERE duedate<='" . $adddate . "'" . " AND tblinvoices.status='Unpaid'" . " AND duedate!=date" . " AND latefeeoveride='0'";
            $result = full_query($query);
            while ($data = mysql_fetch_array($result)) {
                $userid = $data["userid"];
                $invoiceid = $data["id"];
                $duedate = $data["duedate"];
                $paymentmethod = $data["paymentmethod"];
                $total = $data["total"];
                $lateFeeInvoiceCount = get_query_val("tblinvoiceitems", "COUNT(id)", array("type" => "LateFee", "invoiceid" => $invoiceid));
                if (!$lateFeeInvoiceCount) {
                    if ($configLateFeeType == "Percentage") {
                        $amountpaid = get_query_val("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
                        $balance = round($total - $amountpaid, 2);
                        $latefeeamount = format_as_currency($balance * $configInvoiceLateFeeAmount / 100);
                    } else {
                        $latefeeamount = $configInvoiceLateFeeAmount;
                    }
                    if (0 < $configLateFeeMinimum && $latefeeamount < $configLateFeeMinimum) {
                        $latefeeamount = $configLateFeeMinimum;
                    }
                    getUsersLang($userid);
                    insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "LateFee", "invoiceid" => $invoiceid, "description" => sprintf("%s (%s %s)", \Lang::trans("latefee"), \Lang::trans("latefeeadded"), fromMySQLDate(date("Y-m-d"))), "amount" => $latefeeamount, "duedate" => $duedate, "paymentmethod" => $paymentmethod, "taxed" => $taxlatefee));
                    if (!function_exists("updateInvoiceTotal")) {
                        include_once ROOTDIR . "/includes/invoicefunctions.php";
                    }
                    updateInvoiceTotal($invoiceid);
                    run_hook("AddInvoiceLateFee", array("invoiceid" => $invoiceid));
                    try {
                        sendMessage(\WHMCS\Mail\Template::where("name", "Invoice Modified")->firstOrFail(), $invoiceid);
                    } catch (\Exception $e) {
                    }
                    $invoiceids[] = $invoiceid;
                }
            }
        }
        $invoiceTotalCount = count($invoiceids);
        $invoiceTotalMessage = "";
        if ($invoiceTotalCount) {
            $invoiceTotalMessage = " to Invoice Numbers " . implode(",", $invoiceids);
        }
        logActivity(sprintf("Cron Job: Late Invoice Fees added to %s Invoices%s", $invoiceTotalCount, $invoiceTotalMessage));
        $this->output("invoice.latefees")->write($invoiceTotalCount);
    }
}

?>