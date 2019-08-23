<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class AutoClientStatusSync extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1680;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Synchronise Client Status";
    protected $defaultName = "Client Status Update";
    protected $systemName = "AutoClientStatusSync";
    protected $outputs = array("active.product.domain" => array("defaultValue" => 0, "identifier" => "active.product.domain", "name" => "Active due to domain"), "active.product.addon" => array("defaultValue" => 0, "identifier" => "active.product.addon", "name" => "Active due to addon"), "active.product.service" => array("defaultValue" => 0, "identifier" => "active.product.addon", "name" => "Active due to service"), "inactive.login" => array("defaultValue" => 0, "identifier" => "inactive.login", "name" => "Inactive due to no login"), "processed" => array("defaultValue" => 0, "identifier" => "processed", "name" => "Client Status Synced"));
    protected $icon = "fas fa-sort";
    protected $isBooleanStatus = true;
    protected $successCountIdentifier = "processed";
    protected $successKeyword = "Completed";
    public function __invoke()
    {
        if (\WHMCS\Config\Setting::getValue("AutoClientStatusChange") == "1") {
            $this->output("processed")->write(0);
            return $this;
        }
        $this->deactivateClientsWithoutLoginActivity()->activateClientsWithActiveHostingProduct()->activateClientsWithActiveProductAddon()->activateClientsWithActiveDomainProduct();
        $this->output("processed")->write(1);
        return $this;
    }
    protected function activateClientsWithActiveDomainProduct()
    {
        $clientsModified = 0;
        for ($result = full_query("SELECT tbldomains.userid FROM tbldomains" . " INNER JOIN tblclients ON tblclients.id=tbldomains.userid" . " WHERE tblclients.status='Inactive'" . " AND tblclients.overrideautoclose='0'" . " AND tbldomains.status IN ('Active','Pending-Transfer')"); $data = mysql_fetch_array($result); $clientsModified++) {
            $userid = $data["userid"];
            update_query("tblclients", array("status" => "Active"), array("id" => $userid));
        }
        $this->output("active.product.domain")->write($clientsModified);
        return $this;
    }
    protected function activateClientsWithActiveProductAddon()
    {
        $clientsModified = 0;
        for ($result = full_query("SELECT tblhosting.userid FROM tblhostingaddons" . " INNER JOIN tblhosting ON tblhosting.id=tblhostingaddons.hostingid" . " INNER JOIN tblclients ON tblclients.id=tblhosting.userid" . " WHERE tblclients.status='Inactive'" . " AND tblclients.overrideautoclose='0'" . " AND tblhostingaddons.status IN ('Active','Suspended')"); $data = mysql_fetch_array($result); $clientsModified++) {
            $userid = $data["userid"];
            update_query("tblclients", array("status" => "Active"), array("id" => $userid));
        }
        $this->output("active.product.addon")->write($clientsModified);
        return $this;
    }
    protected function activateClientsWithActiveHostingProduct()
    {
        $clientsModified = 0;
        for ($result = full_query("SELECT tblhosting.userid FROM tblhosting" . " INNER JOIN tblclients ON tblclients.id=tblhosting.userid" . " WHERE tblclients.status='Inactive'" . " AND tblclients.overrideautoclose='0'" . " AND tblhosting.domainstatus IN ('Active','Suspended')"); $data = mysql_fetch_array($result); $clientsModified++) {
            $userid = $data["userid"];
            update_query("tblclients", array("status" => "Active"), array("id" => $userid));
        }
        $this->output("active.product.service")->write($clientsModified);
        return $this;
    }
    protected function deactivateClientsWithoutLoginActivity()
    {
        $clientsModified = 0;
        $query = "SELECT id,lastlogin FROM tblclients" . " WHERE status='Active'" . " AND overrideautoclose='0'" . " AND (" . "SELECT COUNT(id) FROM tblhosting" . " WHERE tblhosting.userid=tblclients.id" . " AND domainstatus IN ('Active','Suspended')" . ")=0";
        if (\WHMCS\Config\Setting::getValue("AutoClientStatusChange") == "3") {
            $query .= sprintf(" AND lastlogin<='%s'", date("Y-m-d", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y"))));
        }
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $userid = $data["id"];
            $result2 = full_query("SELECT (" . "SELECT COUNT(*) FROM tblhosting" . " WHERE userid=tblclients.id" . " AND domainstatus IN ('Active','Suspended')" . ")+(" . "SELECT COUNT(*) FROM tblhostingaddons" . " WHERE hostingid IN (" . "SELECT id FROM tblhosting" . " WHERE userid=tblclients.id)" . " AND status IN ('Active','Suspended')" . ")+(" . "SELECT COUNT(*) FROM tbldomains" . " WHERE userid=tblclients.id" . " AND status IN ('Active')" . ") AS activeservices FROM tblclients" . " WHERE tblclients.id=" . (int) $userid . " LIMIT 1");
            $data = mysql_fetch_array($result2);
            $totalactivecount = $data[0];
            if ($totalactivecount == 0) {
                update_query("tblclients", array("status" => "Inactive"), array("id" => $userid));
                $clientsModified++;
            }
        }
        $this->output("inactive.login")->write($clientsModified);
        return $this;
    }
}

?>