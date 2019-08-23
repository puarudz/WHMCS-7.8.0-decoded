<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

abstract class SidebarTrait
{
    protected $sidebarName = "";
    protected $sidebarNameOptions = array("support", "config", "home", "clients", "utilities", "billing", "orders", "addonmodules", "reports");
    public abstract function getAdminUser();
    public function getSidebarName()
    {
        return $this->sidebarName;
    }
    public function setSidebarName($name)
    {
        if (in_array($name, $this->sidebarNameOptions)) {
            $this->sidebarName = $name;
        } else {
            if (empty($name)) {
                $this->sidebarName = "";
            }
        }
        return $this;
    }
    public function isSidebarMinimized()
    {
        return (bool) \WHMCS\Cookie::get("MinSidebar");
    }
    public function getSidebarVariables()
    {
        $sidebarVariables = array();
        $ticketStats = null;
        $appConfig = \DI::make("config");
        $disableAdminTicketPageCounts = (bool) $appConfig->disable_admin_ticket_page_counts;
        if ($this->getSidebarName() == "support") {
            $ticketStats = localApi("GetTicketCounts", array("includeCountsByStatus" => !$disableAdminTicketPageCounts));
            $ticketCounts = array();
            $ticketStatuses = \WHMCS\Database\Capsule::table("tblticketstatuses")->orderBy("sortorder")->pluck("title");
            foreach ($ticketStatuses as $status) {
                $normalisedStatus = preg_replace("/[^a-z0-9]/", "", strtolower($status));
                $ticketCounts[] = array("title" => $status, "count" => isset($ticketStats["status"][$normalisedStatus]["count"]) ? $ticketStats["status"][$normalisedStatus]["count"] : 0);
            }
            $departments = array();
            $departmentsData = \WHMCS\Support\Department::whereIn("id", $ticketStats["filteredDepartments"])->orderBy("order")->get(array("id", "name"));
            foreach ($departmentsData as $department) {
                $departments[] = array("id" => $department->id, "name" => $department->name);
            }
            $sidebarVariables = array("ticketsallactive" => $ticketStats["allActive"], "ticketsawaitingreply" => $ticketStats["awaitingReply"], "ticketsflagged" => $ticketStats["flaggedTickets"], "ticketcounts" => $ticketCounts, "ticketstatuses" => $ticketCounts, "ticketdepts" => $departments);
        }
        if ($this->getAdminUser()->hasPermission("Sidebar Statistics")) {
            $statsVariables = array("orders" => array(), "clients" => array(), "services" => array(), "domains" => array(), "invoices" => array(), "tickets" => array());
            $pendingOrderStatuses = array();
            $dbPendingOrderStatuses = \WHMCS\Database\Capsule::table("tblorderstatuses")->where("showpending", "=", 1)->get(array("title"));
            foreach ($dbPendingOrderStatuses as $pendingOrderStatus) {
                $pendingOrderStatuses[] = $pendingOrderStatus->title;
            }
            if (0 < count($pendingOrderStatuses)) {
                $pendingOrderCounts = \WHMCS\Database\Capsule::table("tblorders")->join("tblclients", "tblclients.id", "=", "tblorders.userid")->whereIn("tblorders.status", $pendingOrderStatuses)->count();
                $statsVariables["orders"]["pending"] = $pendingOrderCounts;
            }
            $clients = \WHMCS\User\Client::groupBy("status")->selectRaw("count(id) as count, status")->pluck("count", "status")->all();
            foreach (array("Active", "Inactive", "Closed") as $status) {
                $statsVariables["clients"][strtolower($status)] = array_key_exists($status, $clients) ? $clients[$status] : 0;
            }
            $services = \WHMCS\Service\Service::groupBy("domainstatus")->selectRaw("count(id) as count, domainstatus")->pluck("count", "domainstatus")->all();
            foreach (array("Pending", "Active", "Suspended", "Completed", "Terminated", "Cancelled", "Fraud") as $status) {
                $statsVariables["services"][strtolower($status)] = array_key_exists($status, $services) ? $services[$status] : 0;
            }
            $domains = \WHMCS\Domain\Domain::groupBy("status")->selectRaw("count(id) as count, status")->pluck("count", "status")->all();
            foreach ((new \WHMCS\Domain\Status())->all() as $status) {
                $statsVariables["domains"][str_replace(" ", "", strtolower($status))] = array_key_exists($status, $domains) ? $domains[$status] : 0;
            }
            $statsVariables["invoices"]["unpaid"] = \WHMCS\Billing\Invoice::unpaid()->count("id");
            $statsVariables["invoices"]["overdue"] = \WHMCS\Billing\Invoice::overdue()->count("id");
            if (!$disableAdminTicketPageCounts) {
                if (is_null($ticketStats)) {
                    $ticketStats = localApi("GetTicketCounts", array("includeCountsByStatus" => !$disableAdminTicketPageCounts));
                }
                $statsVariables["tickets"]["active"] = $ticketStats["allActive"];
                $statsVariables["tickets"]["awaitingreply"] = $ticketStats["awaitingReply"];
                $statsVariables["tickets"]["flagged"] = $ticketStats["flaggedTickets"];
                $ticketStatistics = array();
                if (!empty($ticketStats["status"])) {
                    foreach ($ticketStats["status"] as $status) {
                        $ticketStatistics[$status["title"]] = $status["count"];
                    }
                }
                $statsVariables["tickets"]["onhold"] = array_key_exists("On Hold", $ticketStatistics) ? $ticketStatistics["On Hold"] : "0";
                $statsVariables["tickets"]["inprogress"] = array_key_exists("In Progress", $ticketStatistics) ? $ticketStatistics["In Progress"] : "0";
            }
            $sidebarVariables["sidebarstats"] = $statsVariables;
        }
        if ($this->getSidebarName() == "home") {
            $updater = new \WHMCS\Installer\Update\Updater();
            $licensing = \DI::make("license");
            $sidebarVariables["licenseinfo"] = array("registeredname" => $licensing->getRegisteredName(), "productname" => $licensing->getProductName(), "expires" => $licensing->getExpiryDate(), "currentversion" => \App::self()->getVersion()->getCasual(), "latestversion" => $updater->getLatestVersion()->getCasual(), "updateavailable" => $updater->isUpdateAvailable());
        }
        return $sidebarVariables;
    }
}

?>