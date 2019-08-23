<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class DomainRenewalNotices extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1560;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Processing Domain Renewal Notices";
    protected $defaultName = "Domain Renewal Notices";
    protected $systemName = "DomainRenewalNotices";
    protected $outputs = array("sent" => array("defaultValue" => 0, "identifier" => "sent", "name" => "Renewal Notices"));
    protected $icon = "fas fa-globe";
    protected $successCountIdentifier = "sent";
    protected $successKeyword = "Sent";
    public function __invoke()
    {
        if (!function_exists("RegGetRegistrantContactEmailAddress")) {
            include_once ROOTDIR . "/includes/registrarfunctions.php";
        }
        $whmcs = \DI::make("app");
        $renewalsNoticesCount = 0;
        $renewals = explode(",", $whmcs->get_config("DomainRenewalNotices"));
        foreach ($renewals as $count => $renewal) {
            if ((int) $renewal != 0) {
                $renewalDate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + (int) $renewal, date("Y")));
                if ($renewal < -1) {
                    $status = "'Expired', 'Grace', 'Redemption'";
                    $emailToSend = "Expired Domain Notice";
                } else {
                    if ($renewal == -1) {
                        $status = "'Active'";
                        $emailToSend = "Expired Domain Notice";
                    } else {
                        $status = "'Active'";
                        $emailToSend = "Upcoming Domain Renewal Notice";
                    }
                }
                for ($result = select_query("tbldomains", "id,userid,domain,registrar,reminders", "status IN (" . $status . ") AND nextduedate='" . $renewalDate . "' AND " . "recurringamount!='0.00' AND reminders NOT LIKE '%|" . (int) $renewal . "|%'"); $data = mysql_fetch_array($result); $renewalsNoticesCount++) {
                    $params = array();
                    $params["domainid"] = $data["id"];
                    $domainParts = explode(".", $data["domain"]);
                    list($params["sld"], $params["tld"]) = $domainParts;
                    $params["registrar"] = $data["registrar"];
                    $extra = RegGetRegistrantContactEmailAddress($params);
                    $client = new \WHMCS\Client($data["userid"]);
                    $details = $client->getDetails();
                    $recipients = array();
                    $recipients[] = $details["email"];
                    if (isset($extra["registrantEmail"])) {
                        $recipients[] = $extra["registrantEmail"];
                    }
                    if (sendMessage($emailToSend, $data["id"], $extra) === true) {
                        update_query("tbldomains", array("reminders" => $data["reminders"] . "|" . (int) $renewal . "|"), array("id" => $data["id"]));
                        insert_query("tbldomainreminders", array("domain_id" => $data["id"], "date" => date("Y-m-d"), "recipients" => implode(",", $recipients), "type" => $count + 1, "days_before_expiry" => $renewal));
                    }
                }
            }
        }
        $this->output("sent")->write($renewalsNoticesCount);
        return $this;
    }
}

?>