<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Client\Menu;

class PrimaryNavbarFactory extends \WHMCS\View\Menu\MenuFactory
{
    protected $rootItemName = "Primary Navbar";
    public function navbar($firstName = "", array $conditionalLinks = array())
    {
        $menuStructure = \WHMCS\Session::get("uid") ? $this->getLoggedInNavBarStructure($firstName, $conditionalLinks) : $this->getLoggedOutNavBarStructure($conditionalLinks);
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    protected function getLoggedOutNavBarStructure(array $conditionalLinks = array())
    {
        $menuItems = array(array("name" => "Home", "label" => \Lang::trans("clientareanavhome"), "uri" => "index.php", "order" => 10), array("name" => "Announcements", "label" => \Lang::trans("announcementstitle"), "uri" => routePath("announcement-index"), "order" => 20), array("name" => "Knowledgebase", "label" => \Lang::trans("knowledgebasetitle"), "uri" => routePath("knowledgebase-index"), "order" => 30), array("name" => "Network Status", "label" => \Lang::trans("networkstatustitle"), "uri" => "serverstatus.php", "order" => 40));
        if (!empty($conditionalLinks["affiliates"])) {
            $menuItems[] = array("name" => "Affiliates", "label" => \Lang::trans("affiliatestitle"), "uri" => "affiliates.php", "order" => 50);
        }
        $menuItems[] = array("name" => "Contact Us", "label" => \Lang::trans("contactus"), "uri" => "contact.php", "order" => 60);
        $menuItems[] = array("name" => "Store", "label" => \Lang::trans("navStore"), "uri" => "cart.php", "order" => 15, "children" => $this->buildStoreChildren($conditionalLinks));
        return $menuItems;
    }
    protected function getLoggedInNavBarStructure($firstName = "", array $conditionalLinks = array())
    {
        $menuItems = array(array("name" => "Home", "label" => \Lang::trans("clientareanavhome"), "uri" => "clientarea.php", "order" => 10), array("name" => "Services", "label" => \Lang::trans("navservices"), "uri" => "services.php", "order" => 20, "children" => array(array("name" => "My Services", "label" => \Lang::trans("clientareanavservices"), "uri" => "clientarea.php?action=services", "order" => 10), array("name" => "Services Divider", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 20), array("name" => "Order New Services", "label" => \Lang::trans("navservicesorder"), "uri" => "cart.php", "order" => 30), array("name" => "View Available Addons", "label" => \Lang::trans("clientareaviewaddons"), "uri" => "cart.php?gid=addons", "order" => 40))), array("name" => "Billing", "label" => \Lang::trans("navbilling"), "uri" => "billing.php", "order" => 40, "children" => $this->buildBillingChildren($conditionalLinks)), array("name" => "Support", "label" => \Lang::trans("navsupport"), "uri" => "support.php", "order" => 50, "children" => array(array("name" => "Tickets", "label" => \Lang::trans("navtickets"), "uri" => "supporttickets.php", "order" => 10), array("name" => "Announcements", "label" => \Lang::trans("announcementstitle"), "uri" => routePath("announcement-index"), "order" => 20), array("name" => "Knowledgebase", "label" => \Lang::trans("knowledgebasetitle"), "uri" => routePath("knowledgebase-index"), "order" => 30), array("name" => "Downloads", "label" => \Lang::trans("downloadstitle"), "uri" => routePath("download-index"), "order" => 40), array("name" => "Network Status", "label" => \Lang::trans("networkstatustitle"), "uri" => "serverstatus.php", "order" => 50))), array("name" => "Open Ticket", "label" => \Lang::trans("navopenticket"), "uri" => "submitticket.php", "order" => 60));
        if (!empty($conditionalLinks["affiliates"])) {
            $menuItems[] = array("name" => "Affiliates", "label" => \Lang::trans("affiliatestitle"), "uri" => "affiliates.php", "order" => 70);
        }
        if (\WHMCS\Config\Setting::getValue("AllowRegister") || \WHMCS\Config\Setting::getValue("AllowTransfer")) {
            $menuItems[] = array("name" => "Domains", "label" => \Lang::trans("navdomains"), "uri" => "domains.php", "order" => 30, "children" => $this->buildDomainsChildren($conditionalLinks));
        }
        if (\WHMCS\MarketConnect\MarketConnect::hasActiveServices()) {
            $marketConnectItems = \WHMCS\MarketConnect\MarketConnect::getMenuItems(true);
            if (!empty($marketConnectItems)) {
                $menuItems[] = array("name" => "Website Security", "label" => \Lang::trans("navWebsiteSecurity"), "uri" => "#", "order" => 35, "children" => $marketConnectItems);
            }
        }
        return $menuItems;
    }
    protected function buildDomainsChildren(array $conditionalLinks = array())
    {
        $domainsChildren = array(array("name" => "My Domains", "label" => \Lang::trans("clientareanavdomains"), "uri" => "clientarea.php?action=domains", "order" => 10), array("name" => "Domains Divider", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 20));
        if ((bool) \WHMCS\Config\Setting::getValue("EnableDomainRenewalOrders")) {
            $domainsChildren[] = array("name" => "Renew Domains", "label" => \Lang::trans("navrenewdomains"), "uri" => routePath("cart-domain-renewals"), "order" => 30);
        }
        if (!empty($conditionalLinks["domainreg"])) {
            $domainsChildren[] = array("name" => "Register a New Domain", "label" => \Lang::trans("navregisterdomain"), "uri" => "cart.php?a=add&domain=register", "order" => 40);
        }
        if (!empty($conditionalLinks["domaintrans"])) {
            $domainsChildren[] = array("name" => "Transfer a Domain to Us", "label" => \Lang::trans("navtransferdomain"), "uri" => "cart.php?a=add&domain=transfer", "order" => 50);
        }
        if (!empty($conditionalLinks["domainreg"])) {
            $domainsChildren[] = array("name" => "Domains Divider 2", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 60);
            $domainsChildren[] = array("name" => "Domain Search", "label" => \Lang::trans("navdomainsearch"), "uri" => "domainchecker.php", "order" => 70);
        }
        return $domainsChildren;
    }
    protected function buildBillingChildren($conditionalLinks)
    {
        $billingChildren = array(array("name" => "My Invoices", "label" => \Lang::trans("invoices"), "uri" => "clientarea.php?action=invoices", "order" => 10), array("name" => "My Quotes", "label" => \Lang::trans("quotestitle"), "uri" => "clientarea.php?action=quotes", "order" => 20));
        if (!empty($conditionalLinks["addfunds"]) || !empty($conditionalLinks["masspay"]) || !empty($conditionalLinks["updatecc"])) {
            $billingChildren[] = array("name" => "Billing Divider", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 30);
        }
        if (!empty($conditionalLinks["masspay"])) {
            $billingChildren[] = array("name" => "Mass Payment", "label" => \Lang::trans("masspaytitle"), "uri" => "clientarea.php?action=masspay&all=true", "order" => 40);
        }
        if (!empty($conditionalLinks["updatecc"])) {
            $billingChildren[] = array("name" => "Payment Methods", "label" => \Lang::trans("paymentMethods.title"), "uri" => routePath("account-paymentmethods"), "order" => 50);
        }
        if (!empty($conditionalLinks["addfunds"])) {
            $billingChildren[] = array("name" => "Add Funds", "label" => \Lang::trans("addfunds"), "uri" => "clientarea.php?action=addfunds", "order" => 60);
        }
        return $billingChildren;
    }
    protected function buildStoreChildren(array $conditionalLinks = array())
    {
        $children = array(array("name" => "Browse Products Services", "label" => \Lang::trans("navBrowseProductsServices"), "uri" => "cart.php", "order" => 10));
        $children[] = array("name" => "Shop Divider 1", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 20);
        $i = 0;
        foreach (\WHMCS\Product\Group::notHidden()->orderBy("order")->get() as $group) {
            $children[] = array("name" => $group->name, "label" => $group->name, "uri" => "cart.php?gid=" . $group->id, "order" => 30 + $i * 10);
            $i++;
        }
        if (\WHMCS\MarketConnect\MarketConnect::hasActiveServices()) {
            $children = array_merge($children, \WHMCS\MarketConnect\MarketConnect::getMenuItems(false));
            if (!empty($conditionalLinks["domainreg"]) || !empty($conditionalLinks["domaintrans"])) {
                $children[] = array("name" => "Shop Divider 2", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 2000);
            }
        }
        if (!empty($conditionalLinks["domainreg"])) {
            $children[] = array("name" => "Register a New Domain", "label" => \Lang::trans("navregisterdomain"), "uri" => "cart.php?a=add&domain=register", "order" => 2500);
        }
        if (!empty($conditionalLinks["domaintrans"])) {
            $children[] = array("name" => "Transfer a Domain to Us", "label" => \Lang::trans("navtransferdomain"), "uri" => "cart.php?a=add&domain=transfer", "order" => 2510);
        }
        if (count($children) == 2) {
            return array();
        }
        return $children;
    }
}

?>