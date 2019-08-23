<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Client\Menu;

class PrimarySidebarFactory extends \WHMCS\View\Menu\MenuFactory
{
    protected $rootItemName = "Primary Sidebar";
    public function clientView()
    {
        $conditionalLinks = \WHMCS\ClientArea::getConditionalLinks();
        $action = \App::get_req_var("action");
        $viewNamespace = \Menu::context("routeNamespace");
        $menuItems = array(array("name" => "My Details", "label" => \Lang::trans("clientareanavdetails"), "uri" => "clientarea.php?action=details", "current" => $action == "details", "order" => 10), array("name" => "Contacts/Sub-Accounts", "label" => \Lang::trans("clientareanavcontacts"), "uri" => "clientarea.php?action=contacts", "current" => $action == "contacts" || $action == "addcontact", "order" => 30), array("name" => "Change Password", "label" => \Lang::trans("clientareanavchangepw"), "uri" => "clientarea.php?action=changepw", "current" => $action == "changepw", "order" => 40), array("name" => "Email History", "label" => \Lang::trans("navemailssent"), "uri" => "clientarea.php?action=emails", "current" => $action == "emails", "order" => 60));
        if (!empty($conditionalLinks["updatecc"])) {
            $menuItems[] = array("name" => "Payment Methods", "label" => \Lang::trans("paymentMethods.title"), "uri" => routePath("account-paymentmethods"), "current" => $this->isOnRoutePath("account-paymentmethods", true), "order" => 20);
        }
        if (!empty($conditionalLinks["security"])) {
            $menuItems[] = array("name" => "Security Settings", "label" => \Lang::trans("clientareanavsecurity"), "uri" => "clientarea.php?action=security", "current" => $action == "security", "order" => 50);
        }
        $menuStructure = array(array("name" => "My Account", "label" => \Lang::trans("myaccount"), "order" => 10, "icon" => "fa-user", "attributes" => array("class" => "panel-default panel-actions"), "children" => $menuItems));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function serviceList()
    {
        $serviceStatusCounts = array("Active" => 0, "Completed" => 0, "Pending" => 0, "Suspended" => 0, "Terminated" => 0, "Cancelled" => 0, "Fraud" => 0);
        $filterByModule = \App::get_req_var("module");
        $filterByDomain = preg_replace("/[^a-z0-9-.]/", "", strtolower(\App::get_req_var("q")));
        $client = \Menu::context("client");
        if (is_null($client)) {
            $services = new \Illuminate\Support\Collection(array());
        } else {
            if ($filterByModule) {
                $services = $client->services()->with(array("product" => function ($query) {
                    $query->where("servertype", "=", \App::get_req_var("module"));
                }))->where("domain", "like", "%" . $filterByDomain . "%")->get();
            } else {
                $services = $client->services()->where("domain", "like", "%" . $filterByDomain . "%")->get();
            }
        }
        foreach ($services as $service) {
            if ($filterByModule == "" || !is_null($service->product)) {
                $serviceStatusCounts[$service->domainStatus]++;
            }
        }
        if ($serviceStatusCounts["Fraud"] == 0) {
            unset($serviceStatusCounts["Fraud"]);
        }
        if ($serviceStatusCounts["Completed"] == 0) {
            unset($serviceStatusCounts["Completed"]);
        }
        $menuItems = array();
        $i = 1;
        foreach ($serviceStatusCounts as $status => $count) {
            $menuItems[] = array("name" => $status, "icon" => "far fa-circle", "label" => "<span>" . \Lang::trans("clientarea" . str_replace(" ", "", strtolower($status))) . "</span>", "badge" => $count, "uri" => "clientarea.php?action=services" . ($filterByModule ? "&module=" . $filterByModule : "") . "#", "order" => $i * 10);
            $i++;
        }
        $menuStructure = array(array("name" => "My Services Status Filter", "label" => \Lang::trans("clientareahostingaddonsview"), "order" => 10, "icon" => "fa-filter", "attributes" => array("class" => "panel-default panel-actions view-filter-btns"), "children" => $menuItems));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function serviceView()
    {
        $service = \Menu::context("service");
        $serviceOverviewChildren = array();
        $actionItemChildren = array();
        if (!is_null($service)) {
            $legacyService = new \WHMCS\Service($service->id);
            $legacyService->getAddons();
            $action = \App::get_req_var("action");
            $moduleOperation = \App::get_req_var("modop");
            $moduleAction = \App::get_req_var("a");
            $inProductDetails = \App::getCurrentFilename() == "clientarea" && $action == "productdetails";
            $linkPrefix = "clientarea.php?action=productdetails&id=" . $service->id;
            $serviceOverviewChildren[] = array("name" => "Information", "label" => \Lang::trans("information"), "uri" => $linkPrefix . "#tabOverview", "attributes" => array("dataToggleTab" => $inProductDetails), "current" => $action == "productdetails" && !$moduleOperation, "order" => 10);
            if (0 < count($legacyService->getAssociatedDownloads())) {
                $serviceOverviewChildren[] = array("name" => "Downloads", "label" => \Lang::trans("downloadstitle"), "uri" => $linkPrefix . "#tabDownloads", "attributes" => array("dataToggleTab" => $inProductDetails), "order" => 20);
            }
            if ($service->addons()->count()) {
                $serviceOverviewChildren[] = array("name" => "Addons", "label" => \Lang::trans("clientareahostingaddons"), "uri" => $linkPrefix . "#tabAddons", "attributes" => array("dataToggleTab" => $inProductDetails), "order" => 30);
            }
            if ($legacyService->hasFunction("ChangePassword")) {
                $actionItemChildren[] = array("name" => "Change Password", "label" => \Lang::trans("serverchangepassword"), "uri" => $linkPrefix . "#tabChangepw", "attributes" => array("dataToggleTab" => $inProductDetails), "disabled" => !$legacyService->getAllowChangePassword(), "order" => 10);
            }
            if ($service->hasAvailableUpgrades()) {
                $actionItemChildren[] = array("name" => "Upgrade/Downgrade", "label" => \Lang::trans("upgradedowngradepackage"), "uri" => "upgrade.php?type=package&amp;id=" . $service->id, "disabled" => $service->status != "Active", "order" => 80);
            }
            if ($service->product->allowConfigOptionUpgradeDowngrade) {
                $actionItemChildren[] = array("name" => "Upgrade/Downgrade Options", "label" => \Lang::trans("upgradedowngradeconfigoptions"), "uri" => "upgrade.php?type=configoptions&amp;id=" . $service->id, "disabled" => $service->status != "Active", "order" => 90);
            }
            if ($legacyService->getAllowCancellation()) {
                if (0 < $service->cancellationRequests->count()) {
                    $langIndex = "cancellationrequested";
                    $disabled = true;
                } else {
                    $langIndex = "clientareacancelrequestbutton";
                    $disabled = $service->status != "Active" && $service->status != "Suspended";
                }
                $actionItemChildren[] = array("name" => "Cancel", "label" => \Lang::trans($langIndex), "uri" => "clientarea.php?action=cancel&amp;id=" . $service->id, "order" => 100, "current" => $action == "cancel", "disabled" => $disabled);
            }
            $success = $legacyService->moduleCall("ClientAreaCustomButtonArray");
            if ($success) {
                $moduleCustomButtons = $legacyService->getModuleReturn("data");
                if (is_array($moduleCustomButtons)) {
                    $i = 1;
                    foreach ($moduleCustomButtons as $buttonLabel => $functionName) {
                        if (is_string($functionName)) {
                            $actionItemChildren[] = array("name" => "Custom Module Button " . $buttonLabel, "label" => $buttonLabel, "uri" => "clientarea.php?action=productdetails&id=" . $service->id . "&modop=custom&a=" . $functionName, "current" => $action == "productdetails" && $moduleOperation == "custom" && $moduleAction == $functionName, "disabled" => $service->status != "Active", "order" => 19 + $i);
                            $i++;
                        }
                    }
                }
            }
        }
        $menuStructure = array(array("name" => "Service Details Overview", "label" => \Lang::trans("overview"), "order" => 10, "icon" => "fa-star", "attributes" => array("class" => "panel-default panel-actions"), "childrenAttributes" => array("class" => "list-group-tab-nav"), "children" => $serviceOverviewChildren), array("name" => "Service Details Actions", "label" => \Lang::trans("actions"), "order" => 20, "icon" => "fa-wrench", "attributes" => array("class" => "panel-default panel-actions"), "childrenAttributes" => array("class" => "list-group-tab-nav"), "children" => $actionItemChildren));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function serviceUpgrade()
    {
        $service = \Menu::context("service");
        $childItems = array();
        $productBackButton = "";
        if (!is_null($service)) {
            $childItems[] = array("name" => "Product-Service", "label" => \Lang::trans("orderproduct") . ":<br/><strong>" . $service->product->productGroup->name . " - " . $service->product->name . "</strong>", "order" => 10);
            if ($service->domain != "") {
                $childItems[] = array("name" => "Domain", "label" => \Lang::trans("clientareahostingdomain") . ":<br/>" . $service->domain . "</span>", "order" => 20);
            }
            $productBackButton = "<form method=\"post\" action=\"clientarea.php?action=productdetails\">" . "<input type=\"hidden\" name=\"id\" value=\"" . $service->id . "\" />" . "<button type=\"submit\" class=\"btn btn-block btn-primary\">" . "<i class=\"fas fa-arrow-circle-left\"></i> " . \Lang::trans("backtoservicedetails") . "</button>" . "</form>";
        }
        $menuStructure = array(array("name" => "Upgrade Downgrade", "label" => \Lang::trans("upgradedowngradeshort"), "order" => 10, "icon" => "fa-expand", "children" => $childItems, "footerHtml" => $productBackButton));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function sslCertificateOrderView()
    {
        $service = \Menu::context("service");
        $addon = \Menu::context("addon");
        $additionalData = \Menu::context("displayData");
        $certificateStatus = \Menu::context("orderStatus");
        $stepNumber = \Menu::context("step");
        $stepNumber = in_array($stepNumber, array(2, 3)) ? $stepNumber : 1;
        $childItems = array();
        $productBackButton = "";
        if (!is_null($addon) || !is_null($service)) {
            $i = 6;
            foreach ($additionalData as $label => $value) {
                $childItems[] = array("name" => $label, "label" => "<strong>" . $label . "</strong><br />" . $value, "order" => $i * 10);
                $i++;
            }
            if ($service->domain != "") {
                $childItems[] = array("name" => "Domain Name", "label" => "<strong>" . \Lang::trans("domainname") . "</strong><br />" . $service->domain, "order" => 30);
            }
            $productBackButton = "<a href=\"" . routePath("store-ssl-certificates-manage") . "\" class=\"btn btn-block btn-primary\">" . "<i class=\"fas fa-arrow-circle-left\"></i> " . \Lang::trans("navManageSsl") . "</a>";
        }
        if (!is_null($addon)) {
            $childItems[] = array("name" => "Certificate Type", "label" => "<strong>" . \Lang::trans("sslcerttype") . "</strong><br />" . ($addon->name ?: $addon->productAddon->name), "order" => 10);
            $childItems[] = array("name" => "Order Date", "label" => "<strong>" . \Lang::trans("sslorderdate") . "</strong><br />" . fromMySQLDate($addon->registrationDate, false, true), "order" => 20);
            $childItems[] = array("name" => "Order Price", "label" => "<strong>" . \Lang::trans("orderprice") . "</strong><br /> " . formatCurrency($addon->recurringFee), "order" => 40);
            $childItems[] = array("name" => "Certificate Status", "label" => "<strong>" . \Lang::trans("sslstatus") . "</strong><br />" . $certificateStatus, "order" => 50);
        } else {
            if (!is_null($service)) {
                $childItems[] = array("name" => "Certificate Type", "label" => "<strong>" . \Lang::trans("sslcerttype") . "</strong><br />" . $service->product->name, "order" => 10);
                $childItems[] = array("name" => "Order Date", "label" => "<strong>" . \Lang::trans("sslorderdate") . "</strong><br />" . fromMySQLDate($service->registrationDate, false, true), "order" => 20);
                $childItems[] = array("name" => "Order Price", "label" => "<strong>" . \Lang::trans("orderprice") . "</strong><br /> " . formatCurrency($service->firstPaymentAmount), "order" => 40);
                $childItems[] = array("name" => "Certificate Status", "label" => "<strong>" . \Lang::trans("sslstatus") . "</strong><br />" . $certificateStatus, "order" => 50);
                $productBackButton = "<form method=\"post\" action=\"clientarea.php?action=productdetails\">" . "<input type=\"hidden\" name=\"id\" value=\"" . $service->id . "\" />" . "<button type=\"submit\" class=\"btn btn-block btn-primary\">" . "<i class=\"fas fa-arrow-circle-left\"></i> " . \Lang::trans("backtoservicedetails") . "</button>" . "</form>";
            }
        }
        $menuStructure = array(array("name" => "Configure SSL Certificate Progress", "label" => sprintf(\Lang::trans("step"), $stepNumber) . " <span class=\"pull-right\">" . "<i class=\"far fa-dot-circle\">&nbsp;</i>" . "<i class=\"far fa-" . (2 <= $stepnumber ? "dot-" : "") . "circle\">&nbsp;</i>" . "<i class=\"far fa-" . (2 <= $stepnumber ? "dot-" : "") . "circle\">&nbsp;</i>" . "</span>", "attributes" => array("class" => "panel-info"), "order" => 10, "icon" => "fa-certificate", "children" => $childItems, "footerHtml" => $productBackButton));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function domainList()
    {
        $domainStatusCounts = array("Active" => 0, "Expired" => 0, "Grace" => 0, "Redemption" => 0, "Transferred Away" => 0, "Cancelled" => 0, "Fraud" => 0, "Pending" => 0, "Pending Registration" => 0, "Pending Transfer" => 0, "Expiring Soon" => 0);
        $client = \Menu::context("client");
        $domains = is_null($client) ? new \Illuminate\Support\Collection(array()) : $client->domains;
        $q = preg_replace("/[^a-z0-9-.]/", "", strtolower(\App::get_req_var("q")));
        foreach ($domains as $domain) {
            if ($q == "" || strpos($domain->domain, $q) !== false) {
                $domainStatusCounts[$domain->status]++;
                $daysUntilExpiry = $domain->expiryDate->diffInDays(\WHMCS\Carbon::now());
                if ($daysUntilExpiry <= 45 && $domain->status != "Expired") {
                    $domainStatusCounts["Expiring Soon"]++;
                }
            }
        }
        $nonKeyStatuses = array("Grace", "Redemption", "Cancelled", "Fraud", "Pending", "Pending Registration", "Pending Transfer", "Transferred Away", "Expiring Soon");
        foreach ($nonKeyStatuses as $status) {
            if ($domainStatusCounts[$status] == 0) {
                unset($domainStatusCounts[$status]);
            }
        }
        $menuItems = array();
        $i = 1;
        foreach ($domainStatusCounts as $status => $count) {
            if (stripos($status, "Expiring") !== false) {
                $status = "domains" . str_replace(" ", "", $status);
            } else {
                $status = "clientarea" . str_replace(" ", "", strtolower($status));
            }
            $translatedStatus = \Lang::trans($status);
            $menuItems[] = array("name" => $status, "icon" => "far fa-circle", "label" => "<span>" . $translatedStatus . "</span>", "badge" => $count, "uri" => "clientarea.php?action=domains#", "order" => $i * 10);
            $i++;
        }
        $menuStructure = array(array("name" => "My Domains Status Filter", "label" => \Lang::trans("clientareahostingaddonsview"), "order" => 10, "icon" => "fa-filter", "attributes" => array("class" => "panel-default panel-actions view-filter-btns"), "children" => $menuItems));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function domainView()
    {
        $domain = \Menu::context("domain");
        $childItems = array();
        if (!is_null($domain)) {
            $legacyDomainService = new \WHMCS\Domains();
            $legacyDomainService->getDomainsDatabyID($domain->id);
            $managementOptions = $legacyDomainService->getManagementOptions();
            $action = \App::get_req_var("action");
            $modop = \App::get_req_var("modop");
            $customAction = \App::get_req_var("a");
            $inDomainDetails = \App::getCurrentFilename() == "clientarea" && $action == "domaindetails" && !$customAction && !$modop;
            $inDomainAddons = \App::getCurrentFilename() == "clientarea" && $action == "domainaddons";
            $linkPrefix = $inDomainDetails ? "" : "clientarea.php?action=domaindetails&id=" . $domain->id;
            $domainIsNotActive = !$legacyDomainService->isActive();
            $childItems = array(array("name" => "Overview", "label" => \Lang::trans("overview"), "uri" => $linkPrefix . "#tabOverview", "attributes" => array("dataToggleTab" => $inDomainDetails), "current" => $inDomainDetails, "order" => 10), array("name" => "Auto Renew Settings", "label" => \Lang::trans("domainsautorenew"), "uri" => $linkPrefix . "#tabAutorenew", "attributes" => array("dataToggleTab" => $inDomainDetails), "disabled" => $domainIsNotActive, "order" => 20));
            if ($managementOptions["nameservers"]) {
                $childItems[] = array("name" => "Modify Nameservers", "label" => \Lang::trans("domainnameservers"), "uri" => $linkPrefix . "#tabNameservers", "attributes" => array("dataToggleTab" => $inDomainDetails), "disabled" => $domainIsNotActive, "order" => 30);
            }
            if ($managementOptions["locking"]) {
                $childItems[] = array("name" => "Registrar Lock Status", "label" => \Lang::trans("domainregistrarlock"), "uri" => $linkPrefix . "#tabReglock", "attributes" => array("dataToggleTab" => $inDomainDetails), "disabled" => $domainIsNotActive, "order" => 40);
            }
            if ($managementOptions["release"]) {
                $childItems[] = array("name" => "Release Domain", "label" => \Lang::trans("domainrelease"), "uri" => $linkPrefix . "#tabRelease", "attributes" => array("dataToggleTab" => $inDomainDetails), "disabled" => $domainIsNotActive, "order" => 60);
            }
            if ($managementOptions["addons"]) {
                $childItems[] = array("name" => "Domain Addons", "label" => \Lang::trans("clientareahostingaddons"), "uri" => $linkPrefix . "#tabAddons", "attributes" => array("dataToggleTab" => $inDomainDetails, "class" => $inDomainAddons ? "active" : ""), "disabled" => $domainIsNotActive, "order" => 70);
            }
            if ($managementOptions["contacts"]) {
                $childItems[] = array("name" => "Domain Contacts", "label" => \Lang::trans("domaincontactinfo"), "uri" => "clientarea.php?action=domaincontacts&domainid=" . $domain->id, "current" => $action == "domaincontacts", "disabled" => $domainIsNotActive, "order" => 80);
            }
            if ($managementOptions["privatens"]) {
                $childItems[] = array("name" => "Manage Private Nameservers", "label" => \Lang::trans("domainprivatenameservers"), "uri" => "clientarea.php?action=domainregisterns&domainid=" . $domain->id, "current" => $action == "domainregisterns", "disabled" => $domainIsNotActive, "order" => 90);
            }
            if ($managementOptions["dnsmanagement"]) {
                $childItems[] = array("name" => "Manage DNS Host Records", "label" => \Lang::trans("domaindnsmanagement"), "uri" => "clientarea.php?action=domaindns&domainid=" . $domain->id, "current" => $action == "domaindns", "disabled" => $domainIsNotActive, "order" => 100);
            }
            if ($managementOptions["emailforwarding"]) {
                $childItems[] = array("name" => "Manage Email Forwarding", "label" => \Lang::trans("domainemailforwarding"), "uri" => "clientarea.php?action=domainemailforwarding&domainid=" . $domain->id, "current" => $action == "domainemailforwarding", "disabled" => $domainIsNotActive, "order" => 110);
            }
            if ($managementOptions["eppcode"]) {
                $childItems[] = array("name" => "Get EPP Code", "label" => \Lang::trans("domaingeteppcode"), "uri" => "clientarea.php?action=domaingetepp&domainid=" . $domain->id, "current" => $action == "domaingetepp", "disabled" => $domainIsNotActive, "order" => 120);
            }
            $registrarCustomButtons = array();
            if ($legacyDomainService->hasFunction("ClientAreaCustomButtonArray")) {
                $success = $legacyDomainService->moduleCall("ClientAreaCustomButtonArray");
                if ($success) {
                    $functions = $legacyDomainService->getModuleReturn();
                    if (is_array($functions)) {
                        $registrarCustomButtons = array_merge($registrarCustomButtons, $functions);
                    }
                }
            }
            if ($legacyDomainService->hasFunction("ClientAreaAllowedFunctions")) {
                $success = $legacyDomainService->moduleCall("ClientAreaAllowedFunctions");
                if ($success) {
                    $functions = $legacyDomainService->getModuleReturn();
                    if (is_array($functions)) {
                        $registrarCustomButtons = array_merge($registrarCustomButtons, $functions);
                    }
                }
            }
            if ($registrarCustomButtons) {
                $count = 0;
                foreach ($registrarCustomButtons as $k => $v) {
                    $childItems[] = array("name" => $k, "label" => $k, "uri" => "clientarea.php?action=domaindetails&id=" . $domain->id . "&modop=custom&a=" . $v, "current" => $modop == "custom" && $customAction == $v, "disabled" => $domainIsNotActive, "order" => 130 + $count);
                    $count += 10;
                }
            }
        }
        $menuStructure = array(array("name" => "Domain Details Management", "label" => \Lang::trans("manage"), "order" => 10, "icon" => "fa-cog", "attributes" => array("class" => "panel-default panel-actions"), "childrenAttributes" => array("class" => "list-group-tab-nav"), "children" => $childItems));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function invoiceList()
    {
        $client = \Menu::context("client");
        $clientId = is_null($client) ? 0 : $client->id;
        $conditionalLinks = \WHMCS\ClientArea::getConditionalLinks();
        $invoicesDueMessage = \Lang::trans("noinvoicesduemsg");
        $invoiceActionButtons = array();
        $invoiceActionButtonsString = "";
        $invoiceFilterChildren = array();
        $i = 1;
        $invoiceStatusCounts = array("Paid" => 0, "Unpaid" => 0, "Cancelled" => 0, "Refunded" => 0);
        $invoiceTypeItemInvoiceIds = \WHMCS\Database\Capsule::table("tblinvoiceitems")->where("userid", $clientId)->where("type", "Invoice")->pluck("invoiceid");
        $invoices = \WHMCS\Database\Capsule::table("tblinvoices")->where("userid", $clientId)->whereNotIn("id", $invoiceTypeItemInvoiceIds)->groupBy("status")->get(array("status", \WHMCS\Database\Capsule::raw("COUNT(tblinvoices.id) as invoice_count")));
        foreach ($invoices as $invoiceStatus) {
            $status = $invoiceStatus->status;
            if (isset($invoiceStatusCounts[$status])) {
                $invoiceStatusCounts[$status] = $invoiceStatus->invoice_count;
            }
        }
        if (0 < $invoiceStatusCounts["Unpaid"]) {
            global $currency;
            $currency = getCurrency($clientId);
            $invoices = \WHMCS\Database\Capsule::table("tblinvoices")->where("tblinvoices.userid", $clientId)->where("status", "Unpaid")->leftJoin("tblaccounts", "tblaccounts.invoiceid", "=", "tblinvoices.id")->first(array(\WHMCS\Database\Capsule::raw("IFNULL(SUM(total), 0) as total"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(amountin), 0) as amount_in"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(amountout), 0) as amount_out")));
            $invoicesDueMessage = sprintf(\Lang::trans("invoicesduemsg"), $invoiceStatusCounts["Unpaid"], formatCurrency($invoices->total - $invoices->amount_in + $invoices->amount_out));
            if (!empty($conditionalLinks["masspay"])) {
                $massPayButtonLabel = \Lang::trans("masspayall");
                $invoiceActionButtons[] = "\n    <a href=\"clientarea.php?action=masspay&all=true\" class=\"btn btn-success btn-sm btn-block\"{\$massPayDisabled}>\n        <i class=\"fas fa-check-circle\"></i>\n        " . $massPayButtonLabel . "\n    </a>";
            }
            if (!empty($conditionalLinks["addfunds"])) {
                $addFundsButtonLabel = \Lang::trans("addfunds");
                $invoiceActionButtons[] = "\n    <a href=\"clientarea.php?action=addfunds\" class=\"btn btn-default btn-sm btn-block\">\n        <i class=\"far fa-money-bill-alt\"></i>\n        " . $addFundsButtonLabel . "\n    </a>";
            }
            if (1 < count($invoiceActionButtons)) {
                $col = floor(12 / count($invoiceActionButtons));
            }
            foreach ($invoiceActionButtons as $num => $button) {
                if ($num % 2 == 0) {
                    $side = "left";
                } else {
                    $side = "right";
                }
                $invoiceActionButtonsString .= "<div class='col-xs-" . $col . " col-button-" . $side . "'>" . $button . "</div>";
            }
        }
        foreach ($invoiceStatusCounts as $status => $count) {
            $invoiceFilterChildren[] = array("name" => $status, "icon" => "far fa-circle", "label" => "<span>" . \Lang::trans("invoices" . strtolower($status)) . "</span>", "badge" => $count, "uri" => "clientarea.php?action=invoices#", "order" => $i * 10);
            $i++;
        }
        $menuStructure = array(array("name" => "My Invoices Summary", "label" => $invoiceStatusCounts["Unpaid"] . " " . \Lang::trans("invoicesdue"), "order" => 10, "icon" => "fa-credit-card", "attributes" => array("class" => $invoiceStatusCounts["Unpaid"] == 0 ? "panel-success" : "panel-danger"), "bodyHtml" => $invoicesDueMessage, "footerHtml" => $invoiceActionButtonsString), array("name" => "My Invoices Status Filter", "label" => \Lang::trans("invoicesstatus"), "order" => 20, "icon" => "fa-filter", "attributes" => array("class" => "panel-default panel-actions view-filter-btns"), "children" => $invoiceFilterChildren));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientQuoteList()
    {
        $client = \Menu::context("client");
        $childItems = array();
        $i = 1;
        $quoteStatusCounts = array("Delivered" => "0", "Accepted" => "0");
        if (!is_null($client)) {
            foreach ($client->quotes as $quote) {
                if ($quote->status != "Draft") {
                    $quoteStatusCounts[$quote->status]++;
                }
            }
        }
        foreach ($quoteStatusCounts as $status => $count) {
            $childItems[] = array("name" => $status, "icon" => "far fa-circle", "label" => "<span>" . \Lang::trans("quotestage" . strtolower($status)) . "</span>", "badge" => $count, "uri" => "clientarea.php?action=quotes#", "order" => $i * 10);
            $i++;
        }
        $menuStructure = array(array("name" => "My Quotes Status Filter", "label" => \Lang::trans("quotestage"), "order" => 10, "icon" => "fa-filter", "attributes" => array("class" => "panel-default panel-actions view-filter-btns"), "children" => $childItems));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientAddFunds()
    {
        $menuStructure = array(array("name" => "Add Funds", "label" => \Lang::trans("addfunds"), "bodyHtml" => \Lang::trans("addfundsdescription"), "attributes" => array("class" => "panel panel-info")));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function affiliateView()
    {
        return $this->emptySidebar();
    }
    public function announcementList()
    {
        $monthsWithAnnouncements = \Menu::context("monthsWithAnnouncements");
        $view = \Menu::context("announcementView");
        $menuChildren = array();
        $i = 1;
        if (!is_null($monthsWithAnnouncements)) {
            foreach ($monthsWithAnnouncements as $month) {
                $slug = $month->format("Y-m");
                $menuChildren[] = array("name" => $month->format("M Y"), "uri" => $view == $slug ? routePath("announcement-index") : routePath("announcement-index", $slug), "order" => $i * 10, "current" => $view == $slug);
                $i++;
            }
        }
        $menuChildren[] = array("name" => "Older", "label" => \Lang::trans("announcementsolder") . "...", "uri" => routePath("announcement-index", "older"), "order" => $i * 10, "current" => $view == "older");
        $i++;
        $menuChildren[] = array("name" => "RSS Feed", "label" => \Lang::trans("announcementsrss"), "icon" => "fa-rss icon-rss", "uri" => routePath("announcement-rss"), "order" => $i * 10);
        $menuStructure = array(array("name" => "Announcements Months", "label" => \Lang::trans("announcementsbymonth"), "order" => 10, "icon" => "fa-calendar-alt", "children" => $menuChildren, "extras" => array("mobileSelect" => true)));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function downloadList()
    {
        return $this->emptySidebar();
    }
    public function supportKnowledgeBase()
    {
        $menuStructure = array(array("name" => "Support Knowledgebase Categories", "label" => \Lang::trans("knowledgebasecategories"), "order" => 10, "icon" => "fa-info", "children" => $this->buildSupportKnowledgeBaseCategories(), "extras" => array("mobileSelect" => true)));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function support()
    {
        return $this->emptySidebar();
    }
    public function networkIssueList()
    {
        $issueStatusCounts = \Menu::context("networkIssueStatusCounts");
        $view = \App::get_req_var("view");
        $menuStructure = array(array("name" => "Network Status", "label" => \Lang::trans("view"), "icon" => "fa-filter", "order" => 10, "attributes" => array("class" => "panel-default panel-actions view-filter-btns"), "children" => array(array("name" => "Open", "label" => \Lang::trans("networkissuesstatusopen"), "uri" => "serverstatus.php" . ($view == "open" ? "" : "?view=open"), "order" => 10, "current" => $view == "open", "badge" => $issueStatusCounts["open"]), array("name" => "Scheduled", "label" => \Lang::trans("networkissuesstatusscheduled"), "uri" => "serverstatus.php" . ($view == "scheduled" ? "" : "?view=scheduled"), "order" => 20, "current" => $view == "scheduled", "badge" => $issueStatusCounts["scheduled"]), array("name" => "Resolved", "label" => \Lang::trans("networkissuesstatusresolved"), "uri" => "serverstatus.php" . ($view == "resolved" ? "" : "?view=resolved"), "order" => 30, "current" => $view == "resolved", "badge" => $issueStatusCounts["resolved"]), array("name" => "View RSS Feed", "label" => \Lang::trans("announcementsrss"), "uri" => "networkissuesrss.php", "icon" => "fa-rss icon-rss", "order" => 40))));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function ticketList()
    {
        $ticketStatusCounts = \Menu::context("ticketStatusCounts");
        $childItems = array();
        $i = 1;
        if (is_null($ticketStatusCounts)) {
            $ticketStatusCounts = array();
        }
        foreach ($ticketStatusCounts as $status => $count) {
            $langKey = "supportticketsstatus" . str_replace(array(" ", "-"), "", strtolower($status));
            $translated = \Lang::trans($langKey);
            $langValue = $status;
            if ($translated != $langKey) {
                $langValue = \Lang::trans($langKey);
            }
            $childItems[] = array("name" => $status, "icon" => "far fa-circle", "label" => "<span>" . $langValue . "</span>", "badge" => $count, "uri" => "supporttickets.php#", "order" => $i * 10);
            $i++;
        }
        $menuStructure = array(array("name" => "Ticket List Status Filter", "label" => \Lang::trans("view"), "icon" => "fa-filter", "order" => 10, "attributes" => array("class" => "panel-default panel-actions view-filter-btns"), "children" => $childItems));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function ticketSubmit()
    {
        return $this->support();
    }
    public function ticketFeedback()
    {
        return $this->support();
    }
    public function ticketView()
    {
        $ticketId = \Menu::context("ticketId");
        $carbon = \Menu::context("carbon");
        $data = \Menu::context("ticket");
        if (!$ticketId) {
            return $this->support();
        }
        $ticketId = $data["id"];
        $c = $data["c"];
        $cc = $data["cc"];
        $departmentId = $data["did"];
        $dateOpened = $data["date"];
        $ticketRef = $data["tid"];
        $subject = $data["title"];
        $priority = $data["urgency"];
        $status = $data["status"];
        $lastReply = $data["lastreply"];
        $dateOpened = fromMySQLDate($dateOpened, 1, 1);
        $departmentName = getDepartmentName($departmentId);
        $priority = \Lang::trans("supportticketsticketurgency" . strtolower($priority));
        $showCloseButton = false;
        $closedTicketStatuses = array();
        $ticketStatuses = \WHMCS\Database\Capsule::table("tblticketstatuses")->get();
        $ticketStatusColor = "";
        foreach ($ticketStatuses as $ticketStatus) {
            if ($ticketStatus->title == $status) {
                $ticketStatusColor = $ticketStatus->color;
            }
            if (!$ticketStatus->showactive && !$ticketStatus->showawaiting) {
                $closedTicketStatuses[] = $ticketStatus->title;
            }
        }
        if (!in_array($status, $closedTicketStatuses)) {
            $showCloseButton = true;
        }
        $statusPlain = preg_replace("/[^a-z]/i", "", strtolower($status));
        $displayStatus = \Lang::trans("supportticketsstatus" . $statusPlain);
        if ($displayStatus == "supportticketsstatus" . $statusPlain) {
            $displayStatus = $status;
        }
        $detailsChildren = array(array("name" => "Subject", "label" => "<div class=\"truncate\">#" . $ticketRef . " - " . $subject . "</div>" . " <span class=\"label\" style=\"background-color:" . $ticketStatusColor . ";\">" . $displayStatus . "</span>", "attributes" => array("class" => "ticket-details-children"), "order" => 10), array("name" => "Department", "label" => "<span class=\"title\">" . \Lang::trans("supportticketsdepartment") . "</span><br />" . $departmentName, "attributes" => array("class" => "ticket-details-children"), "order" => 20), array("name" => "Date Opened", "label" => "<span class=\"title\">" . \Lang::trans("supportticketsubmitted") . "</span><br />" . $dateOpened, "attributes" => array("class" => "ticket-details-children"), "order" => 30), array("name" => "Last Updated", "label" => "<span class=\"title\">" . \Lang::trans("supportticketsticketlastupdated") . "</span><br />" . $carbon->parse($lastReply)->diffForHumans(), "attributes" => array("class" => "ticket-details-children"), "order" => 40), array("name" => "Priority", "label" => "<span class=\"title\">" . \Lang::trans("supportticketspriority") . "</span><br />" . $priority, "attributes" => array("class" => "ticket-details-children"), "order" => 50));
        $replyText = \Lang::trans("supportticketsreply");
        $ticketDetailsFooter = "<div class=\"col-xs-6 col-button-left\">\n    <button class=\"btn btn-success btn-sm btn-block\" onclick=\"jQuery('#ticketReply').click()\">\n        <i class=\"fas fa-pencil-alt\"></i> " . $replyText . "\n    </button>\n</div>\n<div class=\"col-xs-6 col-button-right\">\n    <button class=\"btn btn-danger btn-sm btn-block\"";
        if (!$showCloseButton) {
            $ticketDetailsFooter .= "disabled=\"disabled\"";
        }
        $ticketDetailsFooter .= " onclick=\"window.location='?tid=" . $ticketRef . "&amp;c=" . $c . "&amp;closeticket=true'\"><i class=\"fas fa-times\"></i> ";
        $ticketDetailsFooter .= $showCloseButton ? \Lang::trans("supportticketsclose") : \Lang::trans("supportticketsstatusclosed");
        $ticketDetailsFooter .= "</button></div>";
        $menuStructure = array(array("name" => "Ticket Information", "label" => \Lang::trans("ticketinfo"), "order" => 10, "icon" => "fa-ticket-alt", "children" => $detailsChildren, "footerHtml" => $ticketDetailsFooter));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientRegistration()
    {
        $menuStructure = array(array("name" => "Already Registered", "label" => \Lang::trans("alreadyregistered"), "order" => 20, "icon" => "glyphicon-user", "children" => array(array("name" => "Already Registered Heading", "label" => \Lang::trans("clientareahomelogin"), "order" => 5), array("name" => "Login", "label" => \Lang::trans("login"), "icon" => "fa-user", "uri" => "login.php", "order" => 10), array("name" => "Lost Password Reset", "label" => \Lang::trans("pwreset"), "icon" => "fa-asterisk", "uri" => routePath("password-reset-begin"), "order" => 20))));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientHome()
    {
        $client = \Menu::context("client");
        if (is_null($client)) {
            return $this->emptySidebar();
        }
        $details = "";
        if ($client->companyName) {
            $details .= "<strong>" . $client->companyName . "</strong><br><em>" . $client->fullName . "</em><br>";
        } else {
            $details .= "<strong>" . $client->fullName . "</strong><br>";
        }
        $details .= $client->address1 . "<br>";
        if ($client->address2) {
            $details .= $client->address2 . "<br>";
        }
        $address = array();
        if ($client->city) {
            $address[] = $client->city;
        }
        if ($client->state) {
            $address[] = $client->state;
        }
        if ($client->postcode) {
            $address[] = $client->postcode;
        }
        $details .= implode(", ", $address) . "<br>" . $client->countryName;
        if ($client->taxId) {
            $details .= "<br>" . $client->taxId;
        }
        $updateText = \Lang::trans("update");
        $clientDetailsFooter = "    <a href=\"clientarea.php?action=details\" class=\"btn btn-success btn-sm btn-block\">\n        <i class=\"fas fa-pencil-alt\"></i> " . $updateText . "\n    </a>";
        $menuStructure = array(array("name" => "Client Details", "label" => \Lang::trans("yourinfo"), "order" => 10, "icon" => "fa-user", "bodyHtml" => $details, "footerHtml" => $clientDetailsFooter));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    protected function buildSupportKnowledgeBaseCategories()
    {
        $currentCategoryId = \Menu::context("kbCategoryParentId") ? (int) \Menu::context("kbCategoryParentId") : (int) \Menu::context("kbCategoryId");
        $kbRootCategories = \Menu::context("kbRootCategories");
        if (is_null($kbRootCategories)) {
            return array();
        }
        $menuChildren = array();
        foreach ($kbRootCategories as $i => $category) {
            $uri = routePath("knowledgebase-category-view", $category["id"], $category["urlfriendlyname"]);
            $menuChildren[] = array("name" => "Support Knowledgebase Category " . $category["id"], "label" => "<div class=\"truncate\">" . $category["name"] . "</div>", "order" => $i * 10, "badge" => $category["numarticles"], "uri" => $uri, "current" => $currentCategoryId == $category["id"]);
        }
        if (empty($menuChildren)) {
            $menuChildren[] = array("name" => "No Support Knowledgebase Categories", "label" => \Lang::trans("nokbcategories"), "order" => 0, "icon" => "", "badge" => "", "uri" => "", "current" => true);
        }
        return $menuChildren;
    }
    public function orderFormView()
    {
        return $this->emptySidebar();
    }
}

?>