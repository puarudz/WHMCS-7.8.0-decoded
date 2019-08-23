<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class FixedTermTerminations extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1600;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Process Fixed Term Terminations";
    protected $defaultName = "Fixed Term Terminations";
    protected $systemName = "FixedTermTerminations";
    protected $outputs = array("terminations" => array("defaultValue" => 0, "identifier" => "terminations", "name" => "Services Terminated"), "manual" => array("defaultValue" => 0, "identifier" => "manual", "name" => "Manual Terminations Required"));
    protected $icon = "fas fa-plug";
    protected $successCountIdentifier = "terminations";
    protected $failureCountIdentifier = "manual";
    protected $successKeyword = "Terminated";
    public function __invoke()
    {
        $successfulTerminations = 0;
        $manualTerminationsRequired = 0;
        $result = select_query("tblproducts", "id,autoterminatedays,autoterminateemail,servertype,name", "autoterminatedays>0", "id", "ASC");
        while ($data = mysql_fetch_array($result)) {
            list($pid, $autoterminatedays, $autoterminateemail, $module, $prodname) = $data;
            if ($autoterminateemail) {
                $autoTerminateMailTemplate = \WHMCS\Mail\Template::find($autoterminateemail);
            }
            $terminatebefore = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $autoterminatedays, date("Y")));
            $result2 = select_query("tblhosting", "tblhosting.id,userid,domain,firstname,lastname", "packageid=" . $pid . " AND regdate<='" . $terminatebefore . "' AND (domainstatus='Active' OR domainstatus='Suspended')", "id", "ASC", "", "tblclients ON tblclients.id=tblhosting.userid");
            while ($data = mysql_fetch_array($result2)) {
                list($serviceid, $userid, $domain, $firstname, $lastname) = $data;
                $moduleresult = "No Module";
                logActivity("Cron Job: Auto Terminating Fixed Term Service - Service ID: " . $serviceid);
                if ($module) {
                    $moduleresult = ServerTerminateAccount($serviceid);
                }
                if ($domain) {
                    $domain = " - " . $domain;
                }
                $loginfo = sprintf("%s%s - %s %s (Service ID: %s - User ID: %s)", $prodname, $domain, $firstname, $lastname, $serviceid, $userid);
                if ($moduleresult == "success") {
                    if ($autoterminateemail) {
                        sendMessage($autoTerminateMailTemplate, $serviceid);
                    }
                    $msg = "SUCCESS: " . $loginfo;
                    $successfulTerminations++;
                } else {
                    $msg = "ERROR: Manual Terminate Required - " . $moduleresult . " - " . $loginfo;
                    $manualTerminationsRequired++;
                }
                logActivity("Cron Job: " . $msg);
            }
        }
        $this->output("terminations")->write($successfulTerminations);
        $this->output("manual")->write($manualTerminationsRequired);
        return $this;
    }
}

?>