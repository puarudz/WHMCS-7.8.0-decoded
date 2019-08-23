<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class AffiliateCommissions extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1620;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Process Delayed Affiliate Commissions";
    protected $defaultName = "Delayed Affiliate Commissions";
    protected $systemName = "AffiliateCommissions";
    protected $outputs = array("payments" => array("defaultValue" => 0, "identifier" => "payments", "name" => "Affiliate Payments"));
    protected $icon = "far fa-money-bill-alt";
    protected $successCountIdentifier = "payments";
    protected $successKeyword = "Cleared";
    public function __invoke()
    {
        if (!\WHMCS\Config\Setting::getValue("AffiliatesDelayCommission")) {
            return $this;
        }
        $affiliatepaymentscleared = 0;
        $query = "SELECT * FROM tblaffiliatespending WHERE clearingdate<='" . date("Y-m-d") . "'";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $affaccid = $data["affaccid"];
            $amount = $data["amount"];
            $result2 = select_query("tblaffiliatesaccounts", "", array("id" => $affaccid));
            $data = mysql_fetch_array($result2);
            $affaccid = $data["id"];
            $relid = $data["relid"];
            $affid = $data["affiliateid"];
            $result2 = select_query("tblhosting", "domainstatus", array("id" => $relid));
            $data = mysql_fetch_array($result2);
            $domainstatus = $data["domainstatus"];
            if ($affaccid && $domainstatus == "Active") {
                update_query("tblaffiliates", array("balance" => "+=" . $amount), array("id" => (int) $affid));
                update_query("tblaffiliatesaccounts", array("lastpaid" => "now()"), array("id" => (int) $affaccid));
                insert_query("tblaffiliateshistory", array("affiliateid" => $affid, "date" => "now()", "affaccid" => $affaccid, "amount" => $amount));
                $affiliatepaymentscleared++;
            }
        }
        $query = "DELETE FROM tblaffiliatespending WHERE clearingdate<='" . date("Y-m-d") . "'";
        full_query($query);
        $this->output("payments")->write($affiliatepaymentscleared);
        return $this;
    }
}

?>