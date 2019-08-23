<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class OverageBilling extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1670;
    protected $defaultFrequency = 43200;
    protected $defaultDescription = "Process Overage Billing Charges";
    protected $defaultName = "Overage Billing Charges";
    protected $systemName = "OverageBilling";
    protected $outputs = array("invoice.created" => array("defaultValue" => 0, "identifier" => "invoice.created", "name" => "Total Overage Billing Invoices"));
    protected $icon = "far fa-file-alt";
    protected $successCountIdentifier = "invoice.created";
    protected $failedCountIdentifier = "";
    protected $successKeyword = "Generated";
    public function monthlyDayOfExecution()
    {
        return \WHMCS\Carbon::now()->endOfMonth();
    }
    public function anticipatedNextRun(\WHMCS\Carbon $date = NULL)
    {
        $endNextMonth = \WHMCS\Carbon::now()->startOfMonth()->addMonth()->endOfMonth();
        $correctDayDate = $this->anticipatedNextMonthlyRun((int) $endNextMonth->format("d"), $date);
        if ($date) {
            $correctDayDate->hour($date->format("H"))->minute($date->format("i"));
        }
        return $correctDayDate;
    }
    public function __invoke()
    {
        if (!function_exists("ModuleBuildParams")) {
            include_once ROOTDIR . "/includes/modulefunctions.php";
        }
        if (!function_exists("createInvoices")) {
            include_once ROOTDIR . "/includes/processinvoices.php";
        }
        if (!\WHMCS\Carbon::now()->isSameDay($this->monthlyDayOfExecution())) {
            return $this;
        }
        $invoiceaction = \WHMCS\Config\Setting::getValue("OverageBillingMethod");
        if (!$invoiceaction) {
            $invoiceaction = "1";
        }
        $result = select_query("tblproducts", "id,name,overagesenabled,overagesdisklimit,overagesbwlimit,overagesdiskprice,overagesbwprice", array("overagesenabled" => array("sqltype" => "NEQ", "value" => "")));
        while ($data = mysql_fetch_array($result)) {
            $pid = $data["id"];
            $prodname = $data["name"];
            $overagesenabled = $data["overagesenabled"];
            $overagesdisklimit = $data["overagesdisklimit"];
            $overagesbwlimit = $data["overagesbwlimit"];
            $overagesbasediskprice = $data["overagesdiskprice"];
            $overagesbasebwprice = $data["overagesbwprice"];
            $overagesenabled = explode(",", $overagesenabled);
            $result2 = select_query("tblhosting", "tblhosting.*,tblclients.currency", "packageid=" . $pid . " AND (domainstatus='Active' OR domainstatus='Suspended')", "", "", "", "tblclients ON tblclients.id=tblhosting.userid");
            while ($data = mysql_fetch_array($result2)) {
                $serviceid = $data["id"];
                $userid = $data["userid"];
                $currency = $data["currency"];
                $domain = $data["domain"];
                $diskusage = $data["diskusage"];
                $bwusage = $data["bwusage"];
                $result3 = select_query("tblcurrencies", "rate", array("id" => $currency));
                $data = mysql_fetch_array($result3);
                $convertrate = $data["rate"];
                if (!$convertrate) {
                    $convertrate = 1;
                }
                $overagesdiskprice = $overagesbasediskprice * $convertrate;
                $overagesbwprice = $overagesbasebwprice * $convertrate;
                $moduleparams = ModuleBuildParams($serviceid);
                $thisoveragesdisklimit = $overagesdisklimit;
                $thisoveragesbwlimit = $overagesbwlimit;
                if ($moduleparams["customfields"]["Disk Space"]) {
                    $thisoveragesdisklimit = $moduleparams["customfields"]["Disk Space"];
                }
                if ($moduleparams["customfields"]["Bandwidth"]) {
                    $thisoveragesbwlimit = $moduleparams["customfields"]["Bandwidth"];
                }
                if ($moduleparams["configoptions"]["Disk Space"]) {
                    $thisoveragesdisklimit = $moduleparams["configoptions"]["Disk Space"];
                }
                if ($moduleparams["configoptions"]["Bandwidth"]) {
                    $thisoveragesbwlimit = $moduleparams["configoptions"]["Bandwidth"];
                }
                $diskunits = "MB";
                if ($overagesenabled[1] == "GB") {
                    $diskunits = "GB";
                    $diskusage = $diskusage / 1024;
                } else {
                    if ($overagesenabled[1] == "TB") {
                        $diskunits = "TB";
                        $diskusage = $diskusage / (1024 * 1024);
                    }
                }
                $bwunits = "MB";
                if ($overagesenabled[2] == "GB") {
                    $bwunits = "GB";
                    $bwusage = $bwusage / 1024;
                } else {
                    if ($overagesenabled[2] == "TB") {
                        $bwunits = "TB";
                        $bwusage = $bwusage / (1024 * 1024);
                    }
                }
                $diskoverage = $diskusage - $thisoveragesdisklimit;
                $bwoverage = $bwusage - $thisoveragesbwlimit;
                $overagedesc = $prodname;
                if ($domain) {
                    $overagedesc .= " - " . $domain;
                }
                $overagesfrom = fromMySQLDate(date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))));
                $overagesto = getTodaysDate();
                $overagedesc .= " (" . $overagesfrom . " - " . $overagesto . ")";
                getUsersLang($userid);
                if (0 < $diskoverage) {
                    if ($diskoverage < 0) {
                        $diskoverage = 0;
                    }
                    $diskoverage = round($diskoverage, 2);
                    $diskoveragedesc = sprintf("%s\n%s = %s %s - %s = %s %s @ %s/%s", $overagedesc, \Lang::trans("overagestotaldiskusage"), $diskusage, $diskunits, \Lang::trans("overagescharges"), $diskoverage, $diskunits, $overagesdiskprice, $diskunits);
                    $diskoverageamount = $diskoverage * $overagesdiskprice;
                    insert_query("tblbillableitems", array("userid" => $userid, "description" => $diskoveragedesc, "amount" => $diskoverageamount, "recur" => 0, "recurcycle" => 0, "recurfor" => 0, "invoiceaction" => $invoiceaction, "duedate" => date("Y-m-d")));
                }
                if (0 < $bwoverage) {
                    if ($bwoverage < 0) {
                        $bwoverage = 0;
                    }
                    $bwoverage = round($bwoverage, 2);
                    $bwoveragedesc = sprintf("%s\n%s = %s %s - %s = %s %s @ %s/%s", $overagedesc, \Lang::trans("overagestotalbwusage"), $bwusage, $bwunits, \Lang::trans("overagescharges"), $bwoverage, $bwunits, $overagesbwprice, $bwunits);
                    $bwoverageamount = $bwoverage * $overagesbwprice;
                    insert_query("tblbillableitems", array("userid" => $userid, "description" => $bwoveragedesc, "amount" => $bwoverageamount, "recur" => 0, "recurcycle" => 0, "recurfor" => 0, "invoiceaction" => $invoiceaction, "duedate" => date("Y-m-d")));
                }
            }
        }
        createInvoices("", "", "", "", $this);
        return $this;
    }
}

?>