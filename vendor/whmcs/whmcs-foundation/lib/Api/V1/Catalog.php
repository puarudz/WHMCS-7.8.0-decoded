<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\V1;

class Catalog
{
    protected $groups = array();
    protected $actions = array();
    const SETTING_API_CATALOG = "ApiCatalog";
    const GROUP_ADDONS = "Addons";
    const GROUP_AFFILIATES = "Affiliates";
    const GROUP_AUTHENTICATION = "Authentication";
    const GROUP_BILLING = "Billing";
    const GROUP_CLIENT = "Client";
    const GROUP_CUSTOM = "Custom";
    const GROUP_DOMAINS = "Domains";
    const GROUP_MODULE = "Module";
    const GROUP_ORDERS = "Orders";
    const GROUP_PRODUCTS = "Products";
    const GROUP_PMA = "Project-Management";
    const GROUP_SERVERS = "Servers";
    const GROUP_SERVICE = "Service";
    const GROUP_SUPPORT = "Support";
    const GROUP_TICKETS = "Tickets";
    const GROUP_SYSTEM = "System";
    public function __construct(array $data = array())
    {
        if (!empty($data["groups"]) && is_array($data["groups"])) {
            $this->setGroups($data["groups"]);
        }
        if (!empty($data["actions"]) && is_array($data["actions"])) {
            $this->setActions($data["actions"]);
        }
    }
    public function getGroups()
    {
        return $this->groups;
    }
    public function setGroups($groups)
    {
        $this->groups = $groups;
        return $this;
    }
    public function getActions()
    {
        return $this->actions;
    }
    public function setActions($actions)
    {
        $this->actions = $actions;
        return $this;
    }
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    public function toArray()
    {
        $catalog = static::normalize($this);
        return array("groups" => $catalog->getGroups(), "actions" => $catalog->getActions());
    }
    public function getGroupedActions()
    {
        $groups = $this->getGroups();
        $actions = $this->getActions();
        uasort($groups, function ($a, $b) {
            return strnatcasecmp($a["name"], $b["name"]);
        });
        uasort($actions, function ($a, $b) {
            return strnatcasecmp($a["name"], $b["name"]);
        });
        foreach ($actions as $key => $data) {
            if (isset($groups[$data["group"]])) {
                $groups[$data["group"]]["actions"][$key] = $data;
            }
        }
        foreach ($groups as $key => $group) {
            if (!isset($group["actions"])) {
                unset($groups[$key]);
            }
        }
        return $groups;
    }
    public static function get()
    {
        $storedApiCatalog = \WHMCS\Config\Setting::getValue(static::SETTING_API_CATALOG);
        if (!empty($storedApiCatalog)) {
            $apiCatalog = json_decode($storedApiCatalog, true);
            if (is_array($apiCatalog) && !empty($apiCatalog)) {
                return new static($apiCatalog);
            }
        }
        return static::defaultCatalog();
    }
    public static function factoryApiRole(Catalog $catalog, $permissionClass = "WHMCS\\Api\\Authorization\\ApiRole")
    {
        $permissions = new $permissionClass();
        if (!$permissions instanceof \WHMCS\Authorization\Contracts\RoleInterface) {
            throw new \InvalidArgumentException("2nd argument to " . "WHMCS\\Api\\V1\\Catalog::factoryApiRole" . " must be a named class which implements " . "WHMCS\\Authorization\\Contracts\\RoleInterface" . "");
        }
        $actions = $catalog->getActions();
        if ($actions) {
            $data = array();
            foreach ($actions as $key => $actionDetails) {
                if (!empty($actionDetails["default"])) {
                    $data[] = $key;
                }
            }
            $permissions->allow($data);
        }
        return $permissions;
    }
    public static function add(array $actions = array(), array $groups = array())
    {
        $storedCatalog = static::get();
        $storedGroups = $storedCatalog->getGroups();
        $storedActions = $storedCatalog->getActions();
        foreach ($groups as $group => $data) {
            if (!array_key_exists($group, $storedGroups)) {
                $storedGroups[$group] = $data;
            } else {
                $storedGroups[$group] = array_merge($storedGroups[$group], $data);
            }
        }
        foreach ($actions as $action => $data) {
            if (!array_key_exists($action, $storedGroups)) {
                $storedActions[$action] = $data;
            } else {
                $storedActions[$action] = array_merge($storedActions[$action], $data);
            }
        }
        $updatedCatalog = new static(array("groups" => $storedGroups, "actions" => $storedActions));
        static::store($updatedCatalog);
        return $updatedCatalog;
    }
    public static function store(Catalog $catalog = NULL)
    {
        if (is_null($catalog)) {
            $data = "{}";
        } else {
            $data = $catalog->toJson();
        }
        \WHMCS\Config\Setting::setValue(static::SETTING_API_CATALOG, $data);
    }
    public static function normalize(Catalog $catalog)
    {
        $defaultCatalog = static::defaultCatalog();
        $doubleCheckGroups = $defaultCatalog->getGroups();
        $groupToNormailize = array();
        if (!$catalog->getActions()) {
            $catalog->setActions($defaultCatalog->getActions());
        } else {
            $actions = $catalog->getActions();
            foreach ($actions as $key => $data) {
                $normalizedData = array("group" => static::GROUP_CUSTOM, "name" => ucfirst($key), "default" => 0);
                if (is_array($data)) {
                    if (!empty($data["group"]) && is_string($data["group"])) {
                        $normalizedData["group"] = $data["group"];
                        $doubleCheckGroups[] = $data["group"];
                    }
                    if (!empty($data["name"]) && is_string($data["name"])) {
                        $normalizedData["name"] = $data["name"];
                    }
                    if (isset($data["default"]) && (int) $data["default"] === 1) {
                        $normalizedData["default"] = 1;
                    }
                }
                $actions[$key] = $normalizedData;
            }
            $catalog->setActions($actions);
        }
        if (!$catalog->getGroups()) {
            $catalog->setGroups($defaultCatalog->getGroups());
        } else {
            $groups = $catalog->getGroups();
            foreach ($groups as $key => $data) {
                $normalizedData = array("name" => ucfirst($key));
                if (is_array($data) && !empty($data["name"]) && is_string($data["name"])) {
                    $normalizedData["name"] = $data["name"];
                }
                $groups[$key] = $normalizedData;
            }
            $catalog->setGroups($groups);
        }
        return $catalog;
    }
    public static function defaultCatalog()
    {
        $defaults = array("groups" => array(static::GROUP_ADDONS => array("name" => static::GROUP_ADDONS), static::GROUP_AFFILIATES => array("name" => static::GROUP_AFFILIATES), static::GROUP_AUTHENTICATION => array("name" => static::GROUP_AUTHENTICATION), static::GROUP_BILLING => array("name" => static::GROUP_BILLING), static::GROUP_CLIENT => array("name" => static::GROUP_CLIENT), static::GROUP_CUSTOM => array("name" => static::GROUP_CUSTOM), static::GROUP_DOMAINS => array("name" => static::GROUP_DOMAINS), static::GROUP_MODULE => array("name" => static::GROUP_MODULE), static::GROUP_ORDERS => array("name" => static::GROUP_ORDERS), static::GROUP_PRODUCTS => array("name" => static::GROUP_PRODUCTS), static::GROUP_PMA => array("name" => str_replace("-", " ", static::GROUP_PMA)), static::GROUP_SERVERS => array("name" => static::GROUP_SERVERS), static::GROUP_SERVICE => array("name" => static::GROUP_SERVICE), static::GROUP_SUPPORT => array("name" => static::GROUP_SUPPORT), static::GROUP_TICKETS => array("name" => static::GROUP_TICKETS), static::GROUP_SYSTEM => array("name" => static::GROUP_SYSTEM)), "actions" => array("acceptorder" => array("group" => static::GROUP_ORDERS, "name" => "AcceptOrder", "default" => 0), "acceptquote" => array("group" => static::GROUP_BILLING, "name" => "AcceptQuote", "default" => 0), "activatemodule" => array("group" => static::GROUP_MODULE, "name" => "ActivateModule", "default" => 0), "addannouncement" => array("group" => static::GROUP_SUPPORT, "name" => "AddAnnouncement", "default" => 0), "addbannedip" => array("group" => static::GROUP_SYSTEM, "name" => "AddBannedIp", "default" => 0), "addbillableitem" => array("group" => static::GROUP_BILLING, "name" => "AddBillableItem", "default" => 0), "addcancelrequest" => array("group" => static::GROUP_SUPPORT, "name" => "AddCancelRequest", "default" => 0), "addclient" => array("group" => static::GROUP_CLIENT, "name" => "AddClient", "default" => 0), "addclientnote" => array("group" => static::GROUP_SUPPORT, "name" => "AddClientNote", "default" => 0), "addcontact" => array("group" => static::GROUP_CLIENT, "name" => "AddContact", "default" => 0), "addcredit" => array("group" => static::GROUP_BILLING, "name" => "AddCredit", "default" => 0), "addinvoicepayment" => array("group" => static::GROUP_BILLING, "name" => "AddInvoicePayment", "default" => 0), "addorder" => array("group" => static::GROUP_ORDERS, "name" => "AddOrder", "default" => 0), "addpaymethod" => array("group" => static::GROUP_BILLING, "name" => "AddPayMethod", "default" => 0), "addproduct" => array("group" => static::GROUP_PRODUCTS, "name" => "AddProduct", "default" => 0), "addprojectmessage" => array("group" => static::GROUP_PMA, "name" => "AddProjectMessage", "default" => 0), "addprojecttask" => array("group" => static::GROUP_PMA, "name" => "AddProjectTask", "default" => 0), "addticketnote" => array("group" => static::GROUP_TICKETS, "name" => "AddTicketNote", "default" => 0), "addticketreply" => array("group" => static::GROUP_TICKETS, "name" => "AddTicketReply", "default" => 0), "addtransaction" => array("group" => static::GROUP_BILLING, "name" => "AddTransaction", "default" => 0), "affiliateactivate" => array("group" => static::GROUP_AFFILIATES, "name" => "AffiliateActivate", "default" => 0), "applycredit" => array("group" => static::GROUP_BILLING, "name" => "ApplyCredit", "default" => 0), "cancelorder" => array("group" => static::GROUP_ORDERS, "name" => "CancelOrder", "default" => 0), "capturepayment" => array("group" => static::GROUP_BILLING, "name" => "CapturePayment", "default" => 0), "closeclient" => array("group" => static::GROUP_CLIENT, "name" => "CloseClient", "default" => 0), "createinvoice" => array("group" => static::GROUP_BILLING, "name" => "CreateInvoice", "default" => 0), "createoauthcredential" => array("group" => static::GROUP_AUTHENTICATION, "name" => "CreateOAuthCredential", "default" => 0), "createproject" => array("group" => static::GROUP_PMA, "name" => "CreateProject", "default" => 0), "createquote" => array("group" => static::GROUP_BILLING, "name" => "CreateQuote", "default" => 0), "deactivatemodule" => array("group" => static::GROUP_MODULE, "name" => "DeactivateModule", "default" => 0), "decryptpassword" => array("group" => static::GROUP_SYSTEM, "name" => "DecryptPassword", "default" => 0), "deleteannouncement" => array("group" => static::GROUP_SUPPORT, "name" => "DeleteAnnouncement", "default" => 0), "deleteclient" => array("group" => static::GROUP_CLIENT, "name" => "DeleteClient", "default" => 0), "deletecontact" => array("group" => static::GROUP_CLIENT, "name" => "DeleteContact", "default" => 0), "deleteoauthcredential" => array("group" => static::GROUP_AUTHENTICATION, "name" => "DeleteOAuthCredential", "default" => 0), "deleteorder" => array("group" => static::GROUP_ORDERS, "name" => "DeleteOrder", "default" => 0), "deletepaymethod" => array("group" => static::GROUP_BILLING, "name" => "DeletePayMethod", "default" => 0), "deleteprojecttask" => array("group" => static::GROUP_PMA, "name" => "DeleteProjectTask", "default" => 0), "deletequote" => array("group" => static::GROUP_BILLING, "name" => "DeleteQuote", "default" => 0), "deleteticket" => array("group" => static::GROUP_TICKETS, "name" => "DeleteTicket", "default" => 0), "deleteticketnote" => array("group" => static::GROUP_TICKETS, "name" => "DeleteTicketNote", "default" => 0), "domaingetlockingstatus" => array("group" => static::GROUP_DOMAINS, "name" => "DomainGetLockingStatus", "default" => 0), "domaingetnameservers" => array("group" => static::GROUP_DOMAINS, "name" => "DomainGetNameservers", "default" => 0), "domaingetwhoisinfo" => array("group" => static::GROUP_DOMAINS, "name" => "DomainGetWhoisInfo", "default" => 0), "domainregister" => array("group" => static::GROUP_DOMAINS, "name" => "DomainRegister", "default" => 0), "domainrelease" => array("group" => static::GROUP_DOMAINS, "name" => "DomainRelease", "default" => 0), "domainrenew" => array("group" => static::GROUP_DOMAINS, "name" => "DomainRenew", "default" => 0), "domainrequestepp" => array("group" => static::GROUP_DOMAINS, "name" => "DomainRequestEPP", "default" => 0), "domaintoggleidprotect" => array("group" => static::GROUP_DOMAINS, "name" => "DomainToggleIdProtect", "default" => 0), "domaintransfer" => array("group" => static::GROUP_DOMAINS, "name" => "DomainTransfer", "default" => 0), "domainupdatelockingstatus" => array("group" => static::GROUP_DOMAINS, "name" => "DomainUpdateLockingStatus", "default" => 0), "domainupdatenameservers" => array("group" => static::GROUP_DOMAINS, "name" => "DomainUpdateNameservers", "default" => 0), "domainupdatewhoisinfo" => array("group" => static::GROUP_DOMAINS, "name" => "DomainUpdateWhoisInfo", "default" => 0), "domainwhois" => array("group" => static::GROUP_DOMAINS, "name" => "DomainWhois", "default" => 0), "encryptpassword" => array("group" => static::GROUP_SYSTEM, "name" => "EncryptPassword", "default" => 0), "endtasktimer" => array("group" => static::GROUP_PMA, "name" => "EndTaskTimer", "default" => 0), "fraudorder" => array("group" => static::GROUP_ORDERS, "name" => "FraudOrder", "default" => 0), "geninvoices" => array("group" => static::GROUP_BILLING, "name" => "GenInvoices", "default" => 0), "getactivitylog" => array("group" => static::GROUP_SYSTEM, "name" => "GetActivityLog", "default" => 0), "getadmindetails" => array("group" => static::GROUP_SYSTEM, "name" => "GetAdminDetails", "default" => 0), "getaffiliates" => array("group" => static::GROUP_AFFILIATES, "name" => "GetAffiliates", "default" => 0), "getannouncements" => array("group" => static::GROUP_SUPPORT, "name" => "GetAnnouncements", "default" => 0), "getautomationlog" => array("group" => static::GROUP_SYSTEM, "name" => "GetAutomationLog", "default" => 0), "getcancelledpackages" => array("group" => static::GROUP_SUPPORT, "name" => "GetCancelledPackages", "default" => 0), "getclientgroups" => array("group" => static::GROUP_CLIENT, "name" => "GetClientGroups", "default" => 0), "getclientpassword" => array("group" => static::GROUP_CLIENT, "name" => "GetClientPassword", "default" => 0), "getclients" => array("group" => static::GROUP_CLIENT, "name" => "GetClients", "default" => 0), "getclientsaddons" => array("group" => static::GROUP_CLIENT, "name" => "GetClientsAddons", "default" => 0), "getclientsdetails" => array("group" => static::GROUP_CLIENT, "name" => "GetClientsDetails", "default" => 0), "getclientsdomains" => array("group" => static::GROUP_CLIENT, "name" => "GetClientsDomains", "default" => 0), "getclientsproducts" => array("group" => static::GROUP_CLIENT, "name" => "GetClientsProducts", "default" => 0), "getconfigurationvalue" => array("group" => static::GROUP_SYSTEM, "name" => "GetConfigurationValue", "default" => 0), "getcontacts" => array("group" => static::GROUP_CLIENT, "name" => "GetContacts", "default" => 0), "getcredits" => array("group" => static::GROUP_BILLING, "name" => "GetCredits", "default" => 0), "getcurrencies" => array("group" => static::GROUP_SYSTEM, "name" => "GetCurrencies", "default" => 0), "getemails" => array("group" => static::GROUP_CLIENT, "name" => "GetEmails", "default" => 0), "getemailtemplates" => array("group" => static::GROUP_SYSTEM, "name" => "GetEmailTemplates", "default" => 0), "gethealthstatus" => array("group" => static::GROUP_SERVERS, "name" => "GetHealthStatus", "default" => 0), "getinvoice" => array("group" => static::GROUP_BILLING, "name" => "GetInvoice", "default" => 0), "getinvoices" => array("group" => static::GROUP_BILLING, "name" => "GetInvoices", "default" => 0), "getmoduleconfigurationparameters" => array("group" => static::GROUP_MODULE, "name" => "GetModuleConfigurationParameters", "default" => 0), "getmodulequeue" => array("group" => static::GROUP_MODULE, "name" => "GetModuleQueue", "default" => 0), "getorders" => array("group" => static::GROUP_ORDERS, "name" => "GetOrders", "default" => 0), "getorderstatuses" => array("group" => static::GROUP_ORDERS, "name" => "GetOrderStatuses", "default" => 0), "getpaymentmethods" => array("group" => static::GROUP_SYSTEM, "name" => "GetPaymentMethods", "default" => 0), "getpaymethods" => array("group" => static::GROUP_BILLING, "name" => "GetPayMethods", "default" => 0), "getproducts" => array("group" => static::GROUP_PRODUCTS, "name" => "GetProducts", "default" => 0), "getproject" => array("group" => static::GROUP_PMA, "name" => "GetProject", "default" => 0), "getprojects" => array("group" => static::GROUP_PMA, "name" => "GetProjects", "default" => 0), "getpromotions" => array("group" => static::GROUP_ORDERS, "name" => "GetPromotions", "default" => 0), "getquotes" => array("group" => static::GROUP_BILLING, "name" => "GetQuotes", "default" => 0), "getservers" => array("group" => static::GROUP_SERVERS, "name" => "GetServers", "default" => 0), "getstaffonline" => array("group" => static::GROUP_SYSTEM, "name" => "GetStaffOnline", "default" => 0), "getstats" => array("group" => static::GROUP_SYSTEM, "name" => "GetStats", "default" => 0), "getsupportdepartments" => array("group" => static::GROUP_SUPPORT, "name" => "GetSupportDepartments", "default" => 0), "getsupportstatuses" => array("group" => static::GROUP_SUPPORT, "name" => "GetSupportStatuses", "default" => 0), "getticket" => array("group" => static::GROUP_TICKETS, "name" => "GetTicket", "default" => 0), "getticketcounts" => array("group" => static::GROUP_TICKETS, "name" => "GetTicketCounts", "default" => 0), "getticketnotes" => array("group" => static::GROUP_TICKETS, "name" => "GetTicketNotes", "default" => 0), "getticketpredefinedcats" => array("group" => static::GROUP_TICKETS, "name" => "GetTicketPredefinedCats", "default" => 0), "getticketpredefinedreplies" => array("group" => static::GROUP_TICKETS, "name" => "GetTicketPredefinedReplies", "default" => 0), "gettickets" => array("group" => static::GROUP_TICKETS, "name" => "GetTickets", "default" => 0), "gettldpricing" => array("group" => static::GROUP_DOMAINS, "name" => "GettldPricing", "default" => 0), "gettodoitems" => array("group" => static::GROUP_SYSTEM, "name" => "GetToDoItems", "default" => 0), "gettodoitemstatuses" => array("group" => static::GROUP_SYSTEM, "name" => "GetToDoItemStatuses", "default" => 0), "gettransactions" => array("group" => static::GROUP_BILLING, "name" => "Gettransactions", "default" => 0), "listoauthcredentials" => array("group" => static::GROUP_AUTHENTICATION, "name" => "ListOAuthCredentials", "default" => 0), "logactivity" => array("group" => static::GROUP_SYSTEM, "name" => "LogActivity", "default" => 0), "modulechangepackage" => array("group" => static::GROUP_SERVICE, "name" => "ModuleChangePackage", "default" => 0), "modulechangepw" => array("group" => static::GROUP_SERVICE, "name" => "ModuleChangePw", "default" => 0), "modulecreate" => array("group" => static::GROUP_SERVICE, "name" => "ModuleCreate", "default" => 0), "modulecustom" => array("group" => static::GROUP_SERVICE, "name" => "ModuleCustom", "default" => 0), "modulesuspend" => array("group" => static::GROUP_SERVICE, "name" => "ModuleSuspend", "default" => 0), "moduleterminate" => array("group" => static::GROUP_SERVICE, "name" => "ModuleTerminate", "default" => 0), "moduleunsuspend" => array("group" => static::GROUP_SERVICE, "name" => "ModuleUnsuspend", "default" => 0), "openticket" => array("group" => static::GROUP_TICKETS, "name" => "OpenTicket", "default" => 0), "orderfraudcheck" => array("group" => static::GROUP_ORDERS, "name" => "Orderfraudcheck", "default" => 0), "pendingorder" => array("group" => static::GROUP_ORDERS, "name" => "PendingOrder", "default" => 0), "resetpassword" => array("group" => static::GROUP_CLIENT, "name" => "ResetPassword", "default" => 0), "sendadminemail" => array("group" => static::GROUP_SYSTEM, "name" => "SendAdminEmail", "default" => 0), "sendemail" => array("group" => static::GROUP_SYSTEM, "name" => "SendEmail", "default" => 0), "sendquote" => array("group" => static::GROUP_BILLING, "name" => "SendQuote", "default" => 0), "setconfigurationvalue" => array("group" => static::GROUP_SYSTEM, "name" => "SetConfigurationValue", "default" => 0), "starttasktimer" => array("group" => static::GROUP_PMA, "name" => "StartTaskTimer", "default" => 0), "triggernotificationevent" => array("group" => static::GROUP_SYSTEM, "name" => "TriggerNotificationEvent", "default" => 0), "updateadminnotes" => array("group" => static::GROUP_SYSTEM, "name" => "UpdateAdminNotes", "default" => 0), "updateannouncement" => array("group" => static::GROUP_SUPPORT, "name" => "UpdateAnnouncement", "default" => 0), "updateclient" => array("group" => static::GROUP_CLIENT, "name" => "UpdateClient", "default" => 0), "updateclientaddon" => array("group" => static::GROUP_ADDONS, "name" => "UpdateClientAddon", "default" => 0), "updateclientdomain" => array("group" => static::GROUP_DOMAINS, "name" => "UpdateClientDomain", "default" => 0), "updateclientproduct" => array("group" => static::GROUP_PRODUCTS, "name" => "UpdateClientProduct", "default" => 0), "updatecontact" => array("group" => static::GROUP_CLIENT, "name" => "UpdateContact", "default" => 0), "updateinvoice" => array("group" => static::GROUP_BILLING, "name" => "UpdateInvoice", "default" => 0), "updatemoduleconfiguration" => array("group" => static::GROUP_SYSTEM, "name" => "UpdateModuleConfiguration", "default" => 0), "updateoauthcredential" => array("group" => static::GROUP_AUTHENTICATION, "name" => "UpdateOAuthCredential", "default" => 0), "updatepaymethod" => array("group" => static::GROUP_BILLING, "name" => "UpdatePayMethod", "default" => 0), "updateproject" => array("group" => static::GROUP_PMA, "name" => "UpdateProject", "default" => 0), "updateprojecttask" => array("group" => static::GROUP_PMA, "name" => "UpdateProjectTask", "default" => 0), "updatequote" => array("group" => static::GROUP_BILLING, "name" => "UpdateQuote", "default" => 0), "updateticket" => array("group" => static::GROUP_TICKETS, "name" => "UpdateTicket", "default" => 0), "updateticketreply" => array("group" => static::GROUP_TICKETS, "name" => "UpdateTicketReply", "default" => 0), "updatetodoitem" => array("group" => static::GROUP_SYSTEM, "name" => "UpdateToDoItem", "default" => 0), "updatetransaction" => array("group" => static::GROUP_BILLING, "name" => "UpdateTransaction", "default" => 0), "upgradeproduct" => array("group" => static::GROUP_SERVICE, "name" => "UpgradeProduct", "default" => 0), "validatelogin" => array("group" => static::GROUP_AUTHENTICATION, "name" => "ValidateLogin", "default" => 0)));
        return new static($defaults);
    }
}

?>