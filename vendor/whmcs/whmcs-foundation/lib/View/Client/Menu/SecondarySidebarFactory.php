<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Client\Menu;

class SecondarySidebarFactory extends PrimarySidebarFactory
{
    protected $rootItemName = "Secondary Sidebar";
    public function clientView()
    {
        $menuStructure = array();
        if (\App::get_req_var("action") == "creditcard") {
            $menuStructure = array(array("name" => "Billing", "label" => \Lang::trans("navbilling"), "order" => 10, "icon" => "fa-plus", "attributes" => array("class" => "panel-default"), "children" => $this->buildBillingChildItems()));
        }
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function serviceList()
    {
        $menuStructure = array(array("name" => "My Services Actions", "label" => \Lang::trans("actions"), "order" => 10, "icon" => "fa-plus", "attributes" => array("class" => "panel-default"), "children" => array(array("name" => "Place a New Order", "label" => \Lang::trans("navservicesplaceorder"), "icon" => "fa-shopping-cart fa-fw", "uri" => "cart.php", "order" => 10), array("name" => "View Available Addons", "label" => \Lang::trans("clientareaviewaddons"), "icon" => "fa-cubes fa-fw", "uri" => "cart.php?gid=addons", "order" => 20))));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function serviceView()
    {
        return $this->emptySidebar();
    }
    public function serviceUpgrade()
    {
        $service = \Menu::context("service");
        if (is_null($service)) {
            return $this->emptySidebar();
        }
        $result = select_query("tblinvoiceitems", "invoiceid", array("type" => "Hosting", "relid" => $service->id, "status" => "Unpaid", "tblinvoices.userid" => $_SESSION["uid"]), "", "", "", "tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid");
        $overdueInvoice = 0 < mysql_num_rows($result);
        if ($overdueInvoice || upgradeAlreadyInProgress($service->id)) {
            return parent::support();
        }
        return $this->emptySidebar();
    }
    public function sslCertificateOrderView()
    {
        return $this->emptySidebar();
    }
    public function domainList()
    {
        $menuStructure = array(array("name" => "My Domains Actions", "label" => \Lang::trans("actions"), "order" => 10, "icon" => "fa-plus", "attributes" => array("class" => "panel-default"), "children" => $this->buildDomainActionsChildren()));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function domainView()
    {
        $menuStructure = array(array("name" => "Domain Details Actions", "label" => \Lang::trans("actions"), "order" => 20, "icon" => "fa-plus", "childrenAttributes" => array("class" => "list-group-tab-nav"), "children" => $this->buildDomainActionsChildren()));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function invoiceList()
    {
        $menuStructure = array(array("name" => "Billing", "label" => \Lang::trans("navbilling"), "order" => 20, "icon" => "fas fa-university", "children" => $this->buildBillingChildItems()));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientQuoteList()
    {
        $menuStructure = array(array("name" => "Billing", "label" => \Lang::trans("navbilling"), "order" => 20, "icon" => "fas fa-university", "children" => $this->buildBillingChildItems()));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientAddFunds()
    {
        $menuStructure = array(array("name" => "Billing", "label" => \Lang::trans("navbilling"), "order" => 10, "icon" => "fas fa-university", "children" => $this->buildBillingChildItems()));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function affiliateView()
    {
        return $this->emptySidebar();
    }
    public function announcementList()
    {
        return $this->support();
    }
    public function downloadList()
    {
        $popularDownloads = \Menu::context("topFiveDownloads");
        if (is_null($popularDownloads) || $popularDownloads->isEmpty()) {
            return $this->support();
        }
        $downloadLinks = array();
        $i = 1;
        if (!is_null($popularDownloads)) {
            foreach ($popularDownloads as $download) {
                $downloadLinks[] = array("name" => $download->title, "uri" => $download->asLink(), "icon" => "far fa-file", "order" => $i * 10);
                $i++;
            }
        }
        $menuStructure = $this->buildBaseSupportItems();
        $menuStructure[] = array("name" => "Popular Downloads", "label" => \Lang::trans("downloadspopular"), "order" => 10, "icon" => "fa-star", "children" => $downloadLinks);
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function supportKnowledgeBase()
    {
        $tags = \Menu::context("knowledgeBaseTags");
        $menuStructure = $this->buildBaseSupportItems();
        $menuStructure[0]["order"] = 20;
        if (0 < count($tags)) {
            $menuStructure[] = array("name" => "Support Knowledgebase Tag Cloud", "label" => \Lang::trans("kbtagcloud"), "order" => 10, "icon" => "fa-cloud", "bodyHtml" => \WHMCS\View\Helper::buildTagCloud($tags));
        }
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function support()
    {
        return $this->loader->load($this->buildMenuStructure($this->buildBaseSupportItems()));
    }
    public function networkIssueList()
    {
        return $this->support();
    }
    public function ticketList()
    {
        return $this->support();
    }
    public function ticketFeedback()
    {
        return $this->support();
    }
    public function ticketSubmit()
    {
        $client = \Menu::context("client");
        $carbon = \Menu::context("carbon");
        if (is_null($client)) {
            return $this->emptySidebar();
        }
        $childItems = array();
        $i = 0;
        $tickets = \WHMCS\Database\Capsule::table("tbltickets")->join("tblticketdepartments", "tblticketdepartments.id", "=", "tbltickets.did")->where("userid", "=", $client->id)->where("merged_ticket_id", "=", 0)->orderBy("id", "DESC")->limit(5)->get(array("tbltickets.*", "tblticketdepartments.name AS deptname"));
        foreach ($tickets as $data) {
            $childItems[] = array("name" => "Ticket #" . $data->tid, "label" => "<div class=\"recent-ticket\">\n                    <div class=\"truncate\">#" . $data->tid . " - " . $data->title . "</div>" . "<small><span class=\"pull-right\">" . $carbon->parse($data->lastreply)->diffForHumans() . "</span>" . getStatusColour($data->status) . "</small></div>", "uri" => "viewticket.php?tid=" . $data->tid . "&amp;c=" . $data->c, "order" => ($i + 1) * 10);
            $i++;
        }
        if (count($childItems) == 0) {
            return $this->emptySidebar();
        }
        $menuStructure = array(array("name" => "Recent Tickets", "label" => \Lang::trans("yourrecenttickets"), "order" => 10, "icon" => "fa-comments", "children" => $childItems));
        $menuStructure = array_merge($menuStructure, $this->buildBaseSupportItems());
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function ticketView()
    {
        $ticketId = \Menu::context("ticketId");
        if (!$ticketId) {
            return $this->emptySidebar();
        }
        $ticketData = \Menu::context("ticket");
        $ticketCcs = $ticketData["cc"];
        $ticketNumericId = $ticketData["id"];
        $customFields = getCustomFields("support", $ticketData["did"], $ticketData["id"], "", "", "", true);
        $menuStructure = $this->buildBaseSupportItems();
        $attachments = array();
        if ($ticketData["attachment"]) {
            $attachment = explode("|", $ticketData["attachment"]);
            $attachmentCount = 0;
            foreach ($attachment as $filename) {
                $attachments[] = array("replyid" => 0, "i" => $attachmentCount, "filename" => substr($filename, 7), "removed" => (bool) (int) $data["attachments_removed"]);
                $attachmentCount++;
            }
        }
        $result = select_query("tblticketreplies", "", array("tid" => $ticketId), "date", "ASC");
        while ($data = mysql_fetch_array($result)) {
            if ($data["attachment"]) {
                $attachment = explode("|", $data["attachment"]);
                $attachmentCount = 0;
                foreach ($attachment as $filename) {
                    $attachments[] = array("replyid" => $data["id"], "i" => $attachmentCount, "filename" => substr($filename, 7), "removed" => (bool) (int) $data["attachments_removed"]);
                    $attachmentCount++;
                }
            }
        }
        if (0 < count($customFields)) {
            $customFieldChildren = array();
            $order = 10;
            $blankField = \Lang::trans("blankCustomField");
            foreach ($customFields as $customField) {
                if (!is_null($customField["rawvalue"])) {
                    $valueDisplay = $customField["value"];
                } else {
                    $valueDisplay = "<span class='text-muted'>" . $blankField . "</span>";
                }
                $customFieldChildren[] = array("name" => $customField["name"], "label" => "<div class=\"truncate\"><strong>" . $customField["name"] . "</strong></div>" . "<div class=\"truncate\">" . $valueDisplay . "</div>", "order" => $order++);
            }
            $menuStructure[] = array("name" => "Custom Fields", "label" => \Lang::trans("customfield"), "icon" => "fa-database", "order" => 10, "children" => $customFieldChildren);
        }
        if (is_array($attachments) && !empty($attachments)) {
            $attachmentsChildren = array();
            $count = 10;
            foreach ($attachments as $attachment) {
                if ($attachment["removed"]) {
                    continue;
                }
                $uri = "dl.php?type=a&id=" . $ticketNumericId;
                if (0 < $attachment["replyid"]) {
                    $uri = "dl.php?type=ar&id=" . $attachment["replyid"];
                }
                $uri .= "&i=" . $attachment["i"];
                $attachmentsChildren[] = array("name" => $attachment["filename"], "order" => $count, "uri" => $uri);
                $count = $count + 10;
            }
            $menuStructure[] = array("name" => "Attachments", "label" => \Lang::trans("supportticketsticketattachments"), "icon" => "far fa-file", "order" => 30, "children" => $attachmentsChildren);
        }
        $ticketCcs = array_filter(explode(",", $ticketCcs));
        $ticketRows = array();
        $remove = \Lang::trans("support.removeRecipient");
        foreach ($ticketCcs as $index => $ticketCc) {
            $name = "recipient" . str_replace(array("@", "."), "", $ticketCc);
            $order = $index + 1;
            $label = "<div class=\"ticket-cc-email\">\n    <span class=\"email truncate\">" . $ticketCc . "</span>\n    <div class=\"pull-right\">\n        <a href=\"#\" onclick=\"return false;\" class=\"delete-cc-email\" data-email=\"" . $ticketCc . "\">\n            <i class=\"far fa-do-not-enter fa-lg text-danger no-transform\" aria-hidden=\"true\" title=\"" . $remove . "\">\n                <span class=\"sr-only\">" . $remove . "</span>\n            </i>\n        </a>\n    </div>\n</div>";
            $ticketRows[] = array("name" => $name, "attributes" => array("class" => "ticket-cc-item"), "order" => $order, "label" => $label);
        }
        $addText = \Lang::trans("orderForm.add");
        $addMore = \Lang::trans("support.addCcRecipients");
        $systemUrl = \App::getSystemURL();
        $token = generate_token();
        $addHtmlFooter = "<div class=\"list-group-item hidden\" id=\"ccCloneRow\">\n    <div class=\"ticket-cc-email\">\n        <span class=\"email truncate\"></span>\n        <div class=\"pull-right\">\n            <a href=\"#\" class=\"delete-cc-email\" onclick=\"return false;\" data-email=\"\">\n                <i class=\"far fa-do-not-enter fa-lg text-danger no-transform\" aria-hidden=\"true\" title=\"" . $remove . "\">\n                    <span class=\"sr-only\">" . $remove . "</span>\n                </i>\n            </a>\n        </div>\n    </div>\n</div>\n<form id=\"frmAddCcEmail\" action=\"" . $systemUrl . "viewticket.php\">\n    " . $token . "\n    <input type=\"hidden\" name=\"action\" value=\"add\">\n    <input type=\"hidden\" name=\"tid\" value=\"" . $ticketData["tid"] . "\">\n    <input type=\"hidden\" name=\"c\" value=\"" . $ticketData["c"] . "\">\n    <div class=\"input-group margin-bottom-5\" id=\"containerAddCcEmail\">\n        <input id=\"inputAddCcEmail\" type=\"text\" class=\"form-control input-email\" placeholder=\"" . $addMore . "\">\n        <span class=\"input-group-btn\">\n            <button class=\"btn btn-default\" id=\"btnAddCcEmail\" type=\"submit\">" . $addText . "</button>\n        </span>\n    </div>\n</form>\n<div class=\"alert alert-danger hidden small-font\" id=\"divCcEmailFeedback\"></div>";
        $menuStructure[] = array("name" => "CC Recipients", "attributes" => array("id" => "sidebarTicketCc"), "label" => \Lang::trans("support.ccRecipients"), "icon" => "far fa-closed-captioning", "order" => 40, "children" => $ticketRows, "footerHtml" => $addHtmlFooter);
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    protected function buildDomainActionsChildren()
    {
        $childItems = array();
        $inDomainList = \App::getCurrentFilename() == "clientarea" && \App::get_req_var("action") == "domains";
        if (\WHMCS\Config\Setting::getValue("EnableDomainRenewalOrders")) {
            if ($inDomainList) {
                $uri = routePath("cart-domain-renewals");
            } else {
                $domain = \Menu::context("domain");
                $uri = routePath("domain-renewal", $domain->domain);
            }
            $childItems[] = array("name" => "Renew Domain", "label" => \Lang::trans("domainsrenew"), "icon" => "fa-sync fa-fw", "uri" => $uri, "order" => 10);
        }
        if (\WHMCS\Config\Setting::getValue("AllowRegister")) {
            $childItems[] = array("name" => "Register a New Domain", "label" => \Lang::trans("orderregisterdomain"), "icon" => "fa-globe fa-fw", "uri" => "cart.php?a=add&domain=register", "order" => 20);
        }
        if (\WHMCS\Config\Setting::getValue("AllowTransfer")) {
            $childItems[] = array("name" => "Transfer in a Domain", "label" => \Lang::trans("transferinadomain"), "icon" => "fa-share fa-fw", "uri" => "cart.php?a=add&domain=transfer", "order" => 30);
        }
        return $childItems;
    }
    protected function buildBillingChildItems()
    {
        $conditionalLinks = \WHMCS\ClientArea::getConditionalLinks();
        $action = \App::get_req_var("action");
        $billingChildren = array(array("name" => "Invoices", "label" => \Lang::trans("invoices"), "uri" => "clientarea.php?action=invoices", "current" => $action == "invoices", "order" => 10), array("name" => "Quotes", "label" => \Lang::trans("quotestitle"), "uri" => "clientarea.php?action=quotes", "current" => $action == "quotes", "order" => 20));
        if (!empty($conditionalLinks["masspay"])) {
            $billingChildren[] = array("name" => "Mass Payment", "label" => \Lang::trans("masspaytitle"), "uri" => "clientarea.php?action=masspay&all=true", "current" => $action == "masspay", "order" => 30);
        }
        if (!empty($conditionalLinks["addfunds"])) {
            $billingChildren[] = array("name" => "Add Funds", "label" => \Lang::trans("addfunds"), "uri" => "clientarea.php?action=addfunds", "current" => $action == "addfunds", "order" => 50);
        }
        return $billingChildren;
    }
    protected function buildBaseSupportItems()
    {
        $currentFilename = \App::getCurrentFilename();
        $viewNamespace = \Menu::context("routeNamespace");
        return array(array("name" => "Support", "label" => \Lang::trans("navsupport"), "order" => 50, "icon" => "far fa-life-ring", "children" => array(array("name" => "Support Tickets", "label" => \Lang::trans("clientareanavsupporttickets"), "icon" => "fa-ticket-alt fa-fw", "uri" => "supporttickets.php", "current" => $currentFilename == "supporttickets", "order" => 10), array("name" => "Announcements", "label" => \Lang::trans("announcementstitle"), "icon" => "fa-list fa-fw", "uri" => routePath("announcement-index"), "current" => in_array("announcement", array($currentFilename, $viewNamespace)), "order" => 20), array("name" => "Knowledgebase", "label" => \Lang::trans("knowledgebasetitle"), "icon" => "fa-info-circle fa-fw", "uri" => routePath("knowledgebase-index"), "current" => in_array("knowledgebase", array($currentFilename, $viewNamespace)), "order" => 30), array("name" => "Downloads", "label" => \Lang::trans("downloadstitle"), "icon" => "fa-download fa-fw", "uri" => routePath("download-index"), "current" => $currentFilename == "dl" || in_array("download", array($currentFilename, $viewNamespace)), "order" => 40), array("name" => "Network Status", "label" => \Lang::trans("networkstatustitle"), "icon" => "fa-rocket fa-fw", "uri" => "serverstatus.php", "current" => $currentFilename == "serverstatus", "order" => 50), array("name" => "Open Ticket", "label" => \Lang::trans("navopenticket"), "icon" => "fa-comments fa-fw", "uri" => "submitticket.php", "current" => $currentFilename == "submitticket", "order" => 60))));
    }
    public function clientRegistration()
    {
        $securityQuestions = \Menu::context("securityQuestions");
        $allowClientRegister = \WHMCS\Config\Setting::getValue("AllowClientRegister");
        if (is_null($securityQuestions) || $securityQuestions->isEmpty() || !$allowClientRegister) {
            return $this->emptySidebar();
        }
        $menuStructure = array(array("name" => "Why Security Questions", "label" => \Lang::trans("aboutsecurityquestions"), "order" => 10, "icon" => "fa-question-circle", "attributes" => array("class" => "panel-warning"), "bodyHtml" => \Lang::trans("registersecurityquestionblurb")));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function clientHome()
    {
        $shortcutsChildren = array(array("name" => "Order New Services", "label" => \Lang::trans("navservicesorder"), "icon" => "fa-shopping-cart fa-fw", "uri" => "cart.php", "order" => 10), array("name" => "Logout", "label" => \Lang::trans("clientareanavlogout"), "icon" => "fa-arrow-left fa-fw", "uri" => "logout.php", "order" => 30));
        if (\WHMCS\Config\Setting::getValue("AllowRegister") || \WHMCS\Config\Setting::getValue("AllowTransfer")) {
            $shortcutsChildren[] = array("name" => "Register New Domain", "label" => \Lang::trans("orderregisterdomain"), "icon" => "fa-globe fa-fw", "uri" => "domainchecker.php", "order" => 20);
        }
        $client = \Menu::context("client");
        if (is_null($client)) {
            $contactsChildren = array();
        } else {
            if ($client->contacts->isEmpty()) {
                $contactsChildren = array(array("name" => "No Contacts", "label" => \Lang::trans("clientareanocontacts"), "order" => 10));
            } else {
                $contactsChildren = array();
                $order = 10;
                foreach ($client->contacts()->orderBy("firstname", "ASC")->orderBy("lastname", "ASC")->get() as $contact) {
                    $contactsChildren[] = array("name" => (string) $contact->fullName . " " . $contact->id, "label" => $contact->fullName, "uri" => "clientarea.php?action=contacts&id=" . $contact->id, "order" => $order++);
                    if (20 < $order) {
                        break;
                    }
                }
            }
        }
        $newContactText = \Lang::trans("createnewcontact");
        $clientDetailsFooter = "    <a href=\"clientarea.php?action=addcontact\" class=\"btn btn-default btn-sm btn-block\">\n        <i class=\"fas fa-plus\"></i> " . $newContactText . "\n    </a>";
        $menuStructure = array(array("name" => "Client Shortcuts", "label" => \Lang::trans("shortcuts"), "order" => 20, "icon" => "fa-bookmark", "children" => $shortcutsChildren), array("name" => "Client Contacts", "label" => \Lang::trans("contacts"), "order" => 10, "icon" => "far fa-folder", "children" => $contactsChildren, "footerHtml" => $clientDetailsFooter));
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    public function orderFormView()
    {
        $categoryChildren = array();
        $actionsChildren = array();
        $action = \Menu::context("action");
        $productInfoKey = \Menu::context("productInfoKey");
        $productId = \Menu::context("productId");
        $domainAction = \Menu::context("domainAction");
        $currencies = \Menu::context("currencies");
        $currency = \Menu::context("currency");
        $productGroupId = \Menu::context("productGroupId");
        $domainRenewalEnabled = \Menu::context("domainRenewalEnabled");
        $domainRegistrationEnabled = \Menu::context("domainRegistrationEnabled");
        $domainTransferEnabled = \Menu::context("domainTransferEnabled");
        $domain = \Menu::context("domain");
        $client = \Menu::context("client");
        $productGroups = \Menu::context("productGroups");
        $allowRemoteAuth = \Menu::context("allowRemoteAuth");
        $i = 1;
        if ($productGroups && !$productGroups->isEmpty()) {
            foreach ($productGroups as $productGroup) {
                $categoryChildren[] = array("name" => $productGroup->name, "label" => $productGroup->name, "uri" => "cart.php?gid=" . $productGroup->id, "order" => $productGroup->displayOrder * 10, "current" => $productGroup->id == $productGroupId);
                $i = $productGroup->displayOrder + 1;
            }
        }
        $categoryChildren = array_merge($categoryChildren, \WHMCS\MarketConnect\MarketConnect::getSidebarMenuItems($i));
        if (!is_null($client)) {
            $categoryChildren[] = array("name" => "Addons", "label" => \Lang::trans("cartproductaddons"), "uri" => "cart.php?gid=addons", "order" => $i * 10, "current" => $productGroupId == "addons");
        }
        $i = 1;
        if (!is_null($client) && $domainRenewalEnabled) {
            $actionsChildren[] = array("name" => "Domain Renewals", "label" => \Lang::trans("domainrenewals"), "uri" => routePath("cart-domain-renewals"), "order" => $i * 10, "icon" => "fa-sync fa-fw", "current" => $productGroupId == "renewals");
            $i++;
        }
        if ($domainRegistrationEnabled) {
            $actionsChildren[] = array("name" => "Domain Registration", "label" => \Lang::trans("navregisterdomain"), "uri" => "cart.php?a=add&domain=register", "order" => $i * 10, "icon" => "fa-globe fa-fw", "current" => $domain == "register");
            $i++;
        }
        if ($domainTransferEnabled) {
            $actionsChildren[] = array("name" => "Domain Transfer", "label" => \Lang::trans("transferinadomain"), "uri" => "cart.php?a=add&domain=transfer", "order" => $i * 10, "icon" => "fa-share fa-fw", "current" => $domain == "transfer");
            $i++;
        }
        $actionsChildren[] = array("name" => "View Cart", "label" => \Lang::trans("viewcart"), "uri" => "cart.php?a=view", "order" => $i * 10, "icon" => "fa-shopping-cart fa-fw", "current" => $action == "view");
        $menuStructure = array(array("name" => "Actions", "label" => \Lang::trans("actions"), "order" => 20, "icon" => "fa-plus", "children" => $actionsChildren));
        if ($productGroups && !$productGroups->isEmpty()) {
            $menuStructure[] = array("name" => "Categories", "label" => \Lang::trans("ordercategories"), "order" => 10, "icon" => "fa-shopping-cart", "children" => $categoryChildren);
        }
        if (is_null($client) && $currencies) {
            $actionQueryString = "";
            if ($action) {
                $actionQueryString .= "?a=" . $action;
                if (!is_null($productInfoKey)) {
                    $actionQueryString .= "&i=" . $productInfoKey;
                } else {
                    if ($productId) {
                        $actionQueryString .= "&pid=" . $productId;
                    }
                }
                if ($domainAction) {
                    $actionQueryString .= "&domain=" . $domainAction;
                }
            } else {
                if ($productGroupId) {
                    $actionQueryString .= "?gid=" . $productGroupId;
                }
            }
            $body = "<form method=\"post\" action=\"cart.php" . $actionQueryString . "\">\n    <select name=\"currency\" onchange=\"submit()\" class=\"form-control\">";
            foreach ($currencies as $availableCurrency) {
                $body .= "<option value=\"" . $availableCurrency["id"] . "\"";
                if ($availableCurrency["id"] == $currency["id"]) {
                    $body .= " selected";
                }
                $body .= ">" . $availableCurrency["code"] . "</option>";
            }
            $body .= "    </select>\n</form>";
            $menuStructure[] = array("name" => "Choose Currency", "label" => \Lang::trans("choosecurrency"), "order" => 30, "icon" => "fa-plus", "bodyHtml" => $body);
        }
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
}

?>