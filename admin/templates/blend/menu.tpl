<div class="navigation">
<ul id="menu">
<li><a id="Menu-Clients" {if in_array("List Clients",$admin_perms)}href="clients.php"{/if} title="Clients"><span class="hidden-xs">{$_ADMINLANG.clients.title}</span><span class="visible-xs"><i class="fas fa-user"></i></span></a>
  <ul>
    {if in_array("List Clients",$admin_perms)}<li><a id="Menu-Clients-View_Search_Clients" href="clients.php">{$_ADMINLANG.clients.viewsearch}</a></li>{/if}
    {if in_array("Add New Client",$admin_perms)}<li><a id="Menu-Clients-Add_New_Client" href="clientsadd.php">{$_ADMINLANG.clients.addnew}</a></li>{/if}
    {if in_array("List Services",$admin_perms)}
    <li class="expand"><a id="Menu-Clients-Products_Services" href="{routePath('admin-services-index')}">{$_ADMINLANG.services.title}</a>
        <ul>
        <li><a id="Menu-Clients-Products_Services-Shared_Hosting" href="{routePath('admin-services-shared')}">- {$_ADMINLANG.services.listhosting}</a></li>
        <li><a id="Menu-Clients-Products_Services-Reseller_Accounts" href="{routePath('admin-services-reseller')}">- {$_ADMINLANG.services.listreseller}</a></li>
        <li><a id="Menu-Clients-Products_Services-VPS_Servers" href="{routePath('admin-services-server')}">- {$_ADMINLANG.services.listservers}</a></li>
        <li><a id="Menu-Clients-Products_Services-Other_Services" href="{routePath('admin-services-other')}">- {$_ADMINLANG.services.listother}</a></li>
        </ul>
    </li>
    {/if}
    {if in_array("List Addons",$admin_perms)}<li><a id="Menu-Clients-Service_Addons" href="{routePath('admin-addons-index')}">{$_ADMINLANG.services.listaddons}</a></li>{/if}
    {if in_array("List Domains",$admin_perms)}<li><a id="Menu-Clients-Domain_Registration" href="{routePath('admin-domains-index')}">{$_ADMINLANG.services.listdomains}</a></li>{/if}
    {if in_array("View Cancellation Requests",$admin_perms)}<li><a id="Menu-Clients-Cancelation_Requests" href="cancelrequests.php">{$_ADMINLANG.clients.cancelrequests}</a></li>{/if}
    {if in_array("Manage Affiliates",$admin_perms)}<li><a id="Menu-Clients-Manage_Affiliates" href="affiliates.php">{$_ADMINLANG.affiliates.manage}</a></li>{/if}
    {if in_array("Mass Mail",$admin_perms)}<li><a id="Menu-Clients-Mass_Mail_Tool" href="massmail.php">{$_ADMINLANG.clients.massmail}</a></li>{/if}
  </ul>
</li>
<li><a id="Menu-Orders" {if in_array("View Orders",$admin_perms)}href="orders.php"{/if} title="Orders"><span class="hidden-xs">{$_ADMINLANG.orders.title}</span><span class="visible-xs"><i class="fas fa-shopping-cart"></i></span></a>
  <ul>
    {if in_array("View Orders",$admin_perms)}<li><a id="Menu-Orders-List_All_Orders" href="orders.php">{$_ADMINLANG.orders.listall}</a></li>
    <li><a id="Menu-Orders-Pending_Orders" href="orders.php?status=Pending">- {$_ADMINLANG.orders.listpending}</a></li>
    <li><a id="Menu-Orders-Active_Orders" href="orders.php?status=Active">- {$_ADMINLANG.orders.listactive}</a></li>
    <li><a id="Menu-Orders-Fraud_Orders" href="orders.php?status=Fraud">- {$_ADMINLANG.orders.listfraud}</a></li>
    <li><a id="Menu-Orders-Cancelled_Orders" href="orders.php?status=Cancelled">- {$_ADMINLANG.orders.listcancelled}</a></li>{/if}
    {if in_array("Add New Order",$admin_perms)}<li><a id="Menu-Orders-Add_New_Order" href="ordersadd.php">{$_ADMINLANG.orders.addnew}</a></li>{/if}
  </ul>
</li>
<li><a id="Menu-Billing" {if in_array("List Transactions",$admin_perms)}href="transactions.php"{/if} title="Billing"><span class="hidden-xs">{$_ADMINLANG.billing.title}</span><span class="visible-xs"><i class="fas fa-calculator"></i></span></a>
  <ul>
    {if in_array("List Transactions",$admin_perms)}<li><a id="Menu-Billing-Transactions_List" href="transactions.php">{$_ADMINLANG.billing.transactionslist}</a></li>{/if}
    {if in_array("List Invoices",$admin_perms)}
    <li class="expand"><a id="Menu-Billing-Invoices" href="invoices.php">{$_ADMINLANG.invoices.title}</a>
        <ul>
        <li><a id="Menu-Billing-Invoices-Paid" href="invoices.php?status=Paid">- {$_ADMINLANG.status.paid}</a></li>
        <li><a id="Menu-Billing-Invoices-Draft" href="invoices.php?status=Draft">- {$_ADMINLANG.status.draft}</a></li>
        <li><a id="Menu-Billing-Invoices-Unpaid" href="invoices.php?status=Unpaid">- {$_ADMINLANG.status.unpaid}</a></li>
        <li><a id="Menu-Billing-Invoices-Overdue" href="invoices.php?status=Overdue">- {$_ADMINLANG.status.overdue}</a></li>
        <li><a id="Menu-Billing-Invoices-Cancelled" href="invoices.php?status=Cancelled">- {$_ADMINLANG.status.cancelled}</a></li>
        <li><a id="Menu-Billing-Invoices-Refunded" href="invoices.php?status=Refunded">- {$_ADMINLANG.status.refunded}</a></li>
        <li><a id="Menu-Billing-Invoices-Collections" href="invoices.php?status=Collections">- {$_ADMINLANG.status.collections}</a></li>
        <li><a id="Menu-Billing-Invoices-Payment_Pending" href="invoices.php?status=Payment%20Pending">- {$_ADMINLANG.status.paymentpending}</a></li>
        </ul>
    </li>{/if}
    {if in_array("View Billable Items",$admin_perms)}<li class="expand"><a id="Menu-Billing-Billable_Items" href="billableitems.php">{$_ADMINLANG.billableitems.title}</a>
        <ul>
        <li><a id="Menu-Billing-Billable_Items-Uninvoiced_Items" href="billableitems.php?status=Uninvoiced">- {$_ADMINLANG.billableitems.uninvoiced}</a></li>
        <li><a id="Menu-Billing-Billable_Items-Recurring_Items" href="billableitems.php?status=Recurring">- {$_ADMINLANG.billableitems.recurring}</a></li>
        {if in_array("Manage Billable Items",$admin_perms)}<li><a id="Menu-Billing-Billable_Items-Add_New" href="billableitems.php?action=manage">- {$_ADMINLANG.billableitems.addnew}</a></li>{/if}
        </ul>
    </li>{/if}
    {if in_array("Manage Quotes",$admin_perms)}<li class="expand"><a id="Menu-Billing-Quotes" href="quotes.php">{$_ADMINLANG.quotes.title}</a>
        <ul>
        <li><a id="Menu-Billing-Quotes-Valid" href="quotes.php?validity=Valid">- {$_ADMINLANG.status.valid}</a></li>
        <li><a id="Menu-Billing-Quotes-Expired" href="quotes.php?validity=Expired">- {$_ADMINLANG.status.expired}</a></li>
        <li><a id="Menu-Billing-Quotes-Create_New_Quote" href="quotes.php?action=manage">- {$_ADMINLANG.quotes.createnew}</a></li>
        </ul>
    </li>{/if}
    {if in_array("Offline Credit Card Processing",$admin_perms)}<li><a id="Menu-Billing-Offline_CC_Processing" href="offlineccprocessing.php">{$_ADMINLANG.billing.offlinecc}</a></li>{/if}
    {if in_array("View Gateway Log",$admin_perms)}<li><a id="Menu-Billing-Gateway_Log" href="gatewaylog.php">{$_ADMINLANG.billing.gatewaylog}</a></li>{/if}
  </ul>
</li>
<li><a id="Menu-Support" {if in_array("Support Center Overview",$admin_perms)}href="supportcenter.php"{/if} title="Support"><span class="hidden-xs">{$_ADMINLANG.support.title}</span><span class="visible-xs"><i class="fas fa-comments"></i></span></a>
  <ul>
    {if in_array("Support Center Overview",$admin_perms)}<li><a id="Menu-Support-Support_Overview" href="supportcenter.php">{$_ADMINLANG.support.supportoverview}</a></li>{/if}
    {if in_array("Manage Announcements",$admin_perms)}<li><a id="Menu-Support-Announcements" href="supportannouncements.php">{$_ADMINLANG.support.announcements}</a></li>{/if}
    {if in_array("Manage Downloads",$admin_perms)}<li><a id="Menu-Support-Downloads" href="supportdownloads.php">{$_ADMINLANG.support.downloads}</a></li>{/if}
    {if in_array("Manage Knowledgebase",$admin_perms)}<li><a id="Menu-Support-Knowledgebase" href="supportkb.php">{$_ADMINLANG.support.knowledgebase}</a></li>{/if}
    {if in_array("List Support Tickets",$admin_perms)}<li class="expand"><a id="Menu-Support-Support_Tickets" href="supporttickets.php">{$_ADMINLANG.support.supporttickets}</a>
        <ul>
        <li><a id="Menu-Support-Support_Tickets-Flagged_Tickets" href="supporttickets.php?view=flagged">- {$_ADMINLANG.support.flagged}</a></li>
        <li><a id="Menu-Support-Support_Tickets-All_Active_Tickets" href="supporttickets.php?view=active">- {$_ADMINLANG.support.allactive}</a></li>
        {foreach $menuticketstatuses as $status}
            <li><a id="Menu-Support-Support_Tickets-{$status|replace:' ':'_'}" href="supporttickets.php?view={$status}">- {$status}</a></li>
        {/foreach}
        </ul>
    </li>{/if}
    {if in_array("Open New Ticket",$admin_perms)}<li><a id="Menu-Support-Open_New_Ticket" href="supporttickets.php?action=open">{$_ADMINLANG.support.opennewticket}</a></li>{/if}
    {if in_array("Manage Predefined Replies",$admin_perms)}<li><a id="Menu-Support-Predefined_Replies" href="supportticketpredefinedreplies.php">{$_ADMINLANG.support.predefreplies}</a></li>{/if}
    {if in_array("Manage Network Issues",$admin_perms)}<li class="expand"><a id="Menu-Support-Network_Issues" href="networkissues.php">{$_ADMINLANG.networkissues.title}</a>
        <ul>
        <li><a id="Menu-Support-Network_Issues-Open" href="networkissues.php">- {$_ADMINLANG.networkissues.open}</a></li>
        <li><a id="Menu-Support-Network_Issues-Scheduled" href="networkissues.php?view=scheduled">- {$_ADMINLANG.networkissues.scheduled}</a></li>
        <li><a id="Menu-Support-Network_Issues-Resolved" href="networkissues.php?view=resolved">- {$_ADMINLANG.networkissues.resolved}</a></li>
        <li><a id="Menu-Support-Network_Issues-Create_New" href="networkissues.php?action=manage">- {$_ADMINLANG.networkissues.addnew}</a></li>
        </ul>
    </li>{/if}
  </ul>
</li>
{if in_array("View Reports",$admin_perms)}<li><a id="Menu-Reports" title="Reports" href="reports.php"><span class="hidden-xs">{$_ADMINLANG.reports.title}</span><span class="visible-xs"><i class="far fa-chart-bar"></i></span></a>
  <ul>
    <li><a id="Menu-Reports-Daily_Performance" href="reports.php?report=daily_performance">Daily Performance</a></li>
    <li><a id="Menu-Reports-Income_Forecast" href="reports.php?report=income_forecast">Income Forecast</a></li>
    <li><a id="Menu-Reports-Annual_Income_Report" href="reports.php?report=annual_income_report">Annual Income Report</a></li>
    <li><a id="Menu-Reports-New_Customers" href="reports.php?report=new_customers">New Customers</a></li>
    <li><a id="Menu-Reports-Ticket_Feedback_Scores" href="reports.php?report=ticket_feedback_scores">Ticket Feedback Scores</a></li>
    <li><a id="Menu-Reports-Batch_Invoice_PDF_Export" href="reports.php?report=pdf_batch">Batch Invoice PDF Export</a></li>
    <li><a id="Menu-Reports-More..." href="reports.php">More...</a></li>
  </ul>
</li>{/if}
<li><a id="Menu-Utilities" title="Utilities" href=""><span class="hidden-xs">{$_ADMINLANG.utilities.title}</span><span class="visible-xs"><i class="far fa-file-alt"></i></span></a>
  <ul>
    {if in_array("Update WHMCS",$admin_perms)}<li><a id="Menu-Utilities-Update" href="update.php">{$_ADMINLANG.update.title}</a></li>{/if}
    {if in_array("WHMCSConnect",$admin_perms)}<li><a id="Menu-Utilities-WHMCS_Connect" href="whmcsconnect.php">{$_ADMINLANG.whmcsConnect.whmcsConnectName}</a></li>{/if}
    {if in_array("View Module Queue", $admin_perms)}<li><a id="Menu-Utilities-Module_Queue" href="modulequeue.php">{$_ADMINLANG.utilities.moduleQueue}</a></li>{/if}
    {if in_array("Email Marketer",$admin_perms)}<li><a id="Menu-Utilities-Email_Marketer" href="utilitiesemailmarketer.php">{$_ADMINLANG.utilities.emailmarketer}</a></li>{/if}
    {if in_array("Link Tracking",$admin_perms)}<li><a id="Menu-Utilities-Link_Tracking" href="utilitieslinktracking.php">{$_ADMINLANG.utilities.linktracking}</a></li>{/if}
    {if in_array("Calendar",$admin_perms)}<li><a id="Menu-Utilities-Calendar" href="calendar.php">{$_ADMINLANG.utilities.calendar}</a></li>{/if}
    {if in_array("To-Do List",$admin_perms)}<li><a id="Menu-Utilities-To-Do_List" href="todolist.php">{$_ADMINLANG.utilities.todolist}</a></li>{/if}
    {if in_array("WHOIS Lookups",$admin_perms)}<li><a id="Menu-Utilities-WHOIS_Lookups" href="whois.php">{$_ADMINLANG.utilities.whois}</a></li>{/if}
    {if in_array("Domain Resolver Checker",$admin_perms)}<li><a id="Menu-Utilities-Domain_Resolver" href="utilitiesresolvercheck.php">{$_ADMINLANG.utilities.domainresolver}</a></li>{/if}
    {if in_array("View Integration Code",$admin_perms)}<li><a id="Menu-Utilities-Integration_Code" href="systemintegrationcode.php">{$_ADMINLANG.utilities.integrationcode}</a></li>{/if}
    {if in_array("Automation Status", $admin_perms) || in_array("Database Status", $admin_perms) || in_array("System Cleanup Operations", $admin_perms) || in_array("View PHP Info", $admin_perms)}<li class="expand"><a id="Menu-Utilities-System" href="#">{$_ADMINLANG.utilities.system}</a>
        <ul>
        {if in_array("Automation Status", $admin_perms)}<li><a id="Menu-Utilities-System-Automation_Status" href="automationstatus.php">{$_ADMINLANG.utilities.automationStatus}</a></li>{/if}
        {if in_array("Database Status",$admin_perms)}<li><a id="Menu-Utilities-System-Database_Status" href="systemdatabase.php">{$_ADMINLANG.utilities.dbstatus}</a></li>{/if}
        {if in_array("System Cleanup Operations",$admin_perms)}<li><a id="Menu-Utilities-System-System_Cleanup" href="systemcleanup.php">{$_ADMINLANG.utilities.syscleanup}</a></li>{/if}
        {if in_array("View PHP Info",$admin_perms)}<li><a id="Menu-Utilities-System-PHP_Info" href="systemphpinfo.php">{$_ADMINLANG.utilities.phpinfo}</a></li>{/if}
        {if in_array("View PHP Info",$admin_perms)}<li><a id="Menu-Utilities-System-PhpCompat" href="{routePath('admin-utilities-system-phpcompat')}">{$_ADMINLANG.utilities.phpcompat}</a></li>{/if}
        </ul>
    </li>{/if}
    {if in_array("View Activity Log",$admin_perms) || in_array("View Admin Log",$admin_perms) || in_array("View Module Debug Log",$admin_perms) || in_array("View Email Message Log",$admin_perms) || in_array("View Ticket Mail Import Log",$admin_perms) || in_array("View WHOIS Lookup Log",$admin_perms)}<li class="expand"><a id="Menu-Utilities-Logs" href="#">{$_ADMINLANG.utilities.logs}</a>
        <ul>
        {if in_array("View Activity Log",$admin_perms)}<li><a id="Menu-Utilities-Logs-Activity_Log" href="systemactivitylog.php">{$_ADMINLANG.utilities.activitylog}</a></li>{/if}
        {if in_array("View Admin Log",$admin_perms)}<li><a id="Menu-Utilities-Logs-Admin_Log" href="systemadminlog.php">{$_ADMINLANG.utilities.adminlog}</a></li>{/if}
        {if in_array("View Module Debug Log",$admin_perms)}<li><a id="Menu-Utilities-Logs-Module_Log" href="systemmodulelog.php">{$_ADMINLANG.utilities.modulelog}</a></li>{/if}
        {if in_array("View Email Message Log",$admin_perms)}<li><a id="Menu-Utilities-Logs-Email_Message_Log" href="systememaillog.php">{$_ADMINLANG.utilities.emaillog}</a></li>{/if}
        {if in_array("View Ticket Mail Import Log",$admin_perms)}<li><a id="Menu-Utilities-Logs-Ticket_Email_Import_Log" href="systemmailimportlog.php">{$_ADMINLANG.utilities.ticketmaillog}</a></li>{/if}
        {if in_array("View WHOIS Lookup Log",$admin_perms)}<li><a id="Menu-Utilities-Logs-WHOIS_Lookup_Log" href="systemwhoislog.php">{$_ADMINLANG.utilities.whoislog}</a></li>{/if}
        </ul>
    </li>{/if}
  </ul>
</li>
<li><a id="Menu-Addons" title="Addons" href="addonmodules.php"><span class="hidden-xs">{$_ADMINLANG.utilities.addonmodules}</span><span class="visible-xs"><i class="fas fa-cube"></i></span></a>
    <ul>
        <li><a id="Menu-Addons-AppsAndIntegrations" href="{routePath('admin-apps-index')}">{$_ADMINLANG.setup.appsAndIntegrations}</a></li>
        {foreach from=$addon_modules key=module item=displayname}
            <li><a id="Menu-Addons-{$displayname}" href="addonmodules.php?module={$module}">{$displayname}</a></li>
        {foreachelse}
            <li><a id="Menu-Addons-Marketplace-Link" class="autoLinked" href="https://marketplace.whmcs.com">Visit WHMCS Marketplace</a></li>
        {/foreach}
    </ul>
</li>
<li><a id="Menu-Setup" title="Setup" href="{routePath('admin-setup-index')}"><span class="hidden-xs">{$_ADMINLANG.setup.title}</span><span class="visible-xs"><i class="fas fa-cog"></i></span></a>
  <ul>
    {if in_array("Configure General Settings",$admin_perms)}<li><a id="Menu-Setup-General_Settings" href="configgeneral.php">{$_ADMINLANG.setup.general}</a></li>{/if}
    {if in_array("Apps and Integrations",$admin_perms)}<li><a id="Menu-Setup-AppsAndIntegrations" href="{routePath('admin-apps-index')}">{$_ADMINLANG.setup.appsAndIntegrations}</a></li>{/if}
    {if in_array("Configure Sign-In Integration",$admin_perms)}<li><a id="Menu-Setup-Sign-In_Integrations" href="{routePath('admin-setup-authn-view')}">{$_ADMINLANG.setup.signInIntegrations}</a></li>{/if}
    {if in_array("Configure Automation Settings",$admin_perms)}<li><a id="Menu-Setup-Automation_Settings" href="configauto.php">{$_ADMINLANG.setup.automation}</a></li>{/if}
    {if in_array("Manage MarketConnect",$admin_perms)}<li><a id="Menu-Setup-Manage_MarketConnect" href="marketconnect.php">{$_ADMINLANG.setup.marketconnect}</a></li>{/if}
    {if in_array("Manage Notifications",$admin_perms)}<li><a id="Menu-Setup-Notifications" href="{routePath('admin-setup-notifications-overview')}">{$_ADMINLANG.notifications.title}</a></li>{/if}
    {if in_array("Manage Storage Settings",$admin_perms)}<li><a id="Menu-Setup-Storage" href="{routePath('admin-setup-storage-index')}">{$_ADMINLANG.setup.storage}</a></li>{/if}
{if in_array("Configure Administrators",$admin_perms) || in_array("Configure Admin Roles",$admin_perms) || in_array("Configure Two-Factor Authentication",$admin_perms) || in_array("Manage API Credentials",$admin_perms)}
    <li class="expand"><a id="Menu-Setup-Staff_Management" href="#">{$_ADMINLANG.setup.staff}</a>
        <ul>
        {if in_array("Configure Administrators",$admin_perms)}<li><a id="Menu-Setup-Staff_Management-Administrator_Users" href="configadmins.php">{$_ADMINLANG.setup.admins}</a></li>{/if}
        {if in_array("Configure Admin Roles",$admin_perms)}<li><a id="Menu-Setup-Staff_Management-Administrator_Roles" href="configadminroles.php">{$_ADMINLANG.setup.adminroles}</a></li>{/if}
        {if in_array("Configure Two-Factor Authentication",$admin_perms)}<li><a id="Menu-Setup-Staff_Management-Two-Factor_Authentication" href="configtwofa.php">{$_ADMINLANG.setup.twofa}</a></li>{/if}
        {if in_array("Manage API Credentials",$admin_perms)}<li><a id="Menu-Setup-Staff_Management-API_Credentials" href="configapicredentials.php">{$_ADMINLANG.setup.apicredentials}</a></li>{/if}
        </ul>
    </li>{else}
    <li><a id="Menu-Setup-Staff_Management-My_Account" href="myaccount.php">{$_ADMINLANG.global.myaccount}</a></li>{/if}
{if in_array("Configure Currencies",$admin_perms) || in_array("Configure Payment Gateways",$admin_perms) || in_array("Tax Configuration",$admin_perms) || in_array("View Promotions",$admin_perms)}
    <li class="expand"><a id="Menu-Setup-Payments" href="#">{$_ADMINLANG.setup.payments}</a>
        <ul>
        {if in_array("Configure Currencies",$admin_perms)}<li><a id="Menu-Setup-Payments-Currencies" href="configcurrencies.php">{$_ADMINLANG.setup.currencies}</a></li>{/if}
        {if in_array("Configure Payment Gateways",$admin_perms)}<li><a id="Menu-Setup-Payments-Payment_Gateways" href="configgateways.php">{$_ADMINLANG.setup.gateways}</a></li>{/if}
        {if in_array("Tax Configuration",$admin_perms)}
            <li>
                <a id="Menu-Setup-Payments-Tax_Configuration" href="{routePath('admin-setup-payments-tax-index')}">
                    {$_ADMINLANG.setup.tax}
                </a>
            </li>
        {/if}
        {if in_array("View Promotions",$admin_perms)}<li><a id="Menu-Setup-Payments-Promotions" href="configpromotions.php">{$_ADMINLANG.setup.promos}</a></li>{/if}
        </ul>
    </li>{/if}
{if in_array("View Products/Services",$admin_perms) || in_array("Configure Product Addons",$admin_perms) || in_array("Configure Product Bundles",$admin_perms) || in_array("Configure Domain Pricing",$admin_perms) || in_array("Configure Domain Registrars",$admin_perms) || in_array("Configure Servers",$admin_perms)}
    <li class="expand"><a id="Menu-Setup-Products_Services" href="#">{$_ADMINLANG.setup.products}</a>
        <ul>
        {if in_array("View Products/Services",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Products_Services" href="configproducts.php">{$_ADMINLANG.setup.products}</a></li>{/if}
        {if in_array("View Products/Services",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Configurable_Options" href="configproductoptions.php">{$_ADMINLANG.setup.configoptions}</a></li>{/if}
        {if in_array("Configure Product Addons",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Product_Addons" href="configaddons.php">{$_ADMINLANG.setup.addons}</a></li>{/if}
        {if in_array("Configure Product Bundles",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Product_Bundles" href="configbundles.php">{$_ADMINLANG.setup.bundles}</a></li>{/if}
        {if in_array("Configure Domain Pricing",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Domain_Pricing" href="configdomains.php">{$_ADMINLANG.setup.domainpricing}</a></li>{/if}
        {if in_array("Configure Domain Registrars",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Domain_Registrars" href="configregistrars.php">{$_ADMINLANG.setup.registrars}</a></li>{/if}
        {if in_array("Configure Servers",$admin_perms)}<li><a id="Menu-Setup-Products_Services-Servers" href="configservers.php">{$_ADMINLANG.setup.servers}</a></li>{/if}
        </ul>
    </li>{/if}
{if in_array("Configure Support Departments",$admin_perms) || in_array("Configure Ticket Statuses",$admin_perms) || in_array("Configure Support Departments",$admin_perms) || in_array("Configure Spam Control",$admin_perms)}
    <li class="expand"><a id="Menu-Setup-Support" href="#">{$_ADMINLANG.support.title}</a>
        <ul>
        {if in_array("Configure Support Departments",$admin_perms)}<li><a id="Menu-Setup-Support-Support_Departments" href="configticketdepartments.php">{$_ADMINLANG.setup.supportdepartments}</a></li>{/if}
        {if in_array("Configure Ticket Statuses",$admin_perms)}<li><a id="Menu-Setup-Support-Ticket_Statuses" href="configticketstatuses.php">{$_ADMINLANG.setup.ticketstatuses}</a></li>{/if}
        {if in_array("Configure Support Departments",$admin_perms)}<li><a id="Menu-Setup-Support-Escalation_Rules" href="configticketescalations.php">{$_ADMINLANG.setup.escalationrules}</a></li>{/if}
        {if in_array("Configure Spam Control",$admin_perms)}<li><a id="Menu-Setup-Support-Spam_Control" href="configticketspamcontrol.php">{$_ADMINLANG.setup.spam}</a></li>{/if}
        </ul>
    </li>{/if}
    {if in_array("Configure Application Links",$admin_perms)}<li><a id="Menu-Setup-Application_Links" href="configapplinks.php">{$_ADMINLANG.setup.applicationLinks}</a></li>{/if}
    {if in_array("Configure OpenID Connect",$admin_perms)}<li><a id="Menu-Setup-OpenID_Connect" href="configopenid.php">{$_ADMINLANG.setup.openIdConnect}</a></li>{/if}
    {if in_array("View Email Templates",$admin_perms)}<li><a id="Menu-Setup-Email_Templates" href="configemailtemplates.php">{$_ADMINLANG.setup.emailtpls}</a></li>{/if}
    {if in_array("Configure Addon Modules",$admin_perms)}<li><a id="Menu-Setup-Addons_Modules" href="configaddonmods.php">{$_ADMINLANG.setup.addonmodules}</a></li>{/if}
    {if in_array("Configure Client Groups",$admin_perms)}<li><a id="Menu-Setup-Client_Groups" href="configclientgroups.php">{$_ADMINLANG.setup.clientgroups}</a></li>{/if}
    {if in_array("Configure Custom Client Fields",$admin_perms)}<li><a id="Menu-Setup-Custom_Client_Fields" href="configcustomfields.php">{$_ADMINLANG.setup.customclientfields}</a></li>{/if}
    {if in_array("Configure Fraud Protection",$admin_perms)}<li><a id="Menu-Setup-Fraud_Protection" href="configfraud.php">{$_ADMINLANG.setup.fraud}</a></li>{/if}
{if in_array("Configure Order Statuses",$admin_perms) || in_array("Configure Security Questions",$admin_perms) || in_array("View Banned IPs",$admin_perms) || in_array("Configure Banned Emails",$admin_perms) || in_array("Configure Database Backups",$admin_perms)}
    <li class="expand"><a id="Menu-Setup-Other" href="#">{$_ADMINLANG.setup.other}</a>
        <ul>
        {if in_array("Configure Order Statuses",$admin_perms)}<li><a id="Menu-Setup-Other-Order_Statuses" href="configorderstatuses.php">{$_ADMINLANG.setup.orderstatuses}</a></li>{/if}
        {if in_array("Configure Security Questions",$admin_perms)}<li><a id="Menu-Setup-Other-Security_Questions" href="configsecurityqs.php">{$_ADMINLANG.setup.securityqs}</a></li>{/if}
        {if in_array("View Banned IPs",$admin_perms)}<li><a id="Menu-Setup-Other-Banned_IPs" href="configbannedips.php">{$_ADMINLANG.setup.bannedips}</a></li>{/if}
        {if in_array("Configure Banned Emails",$admin_perms)}<li><a id="Menu-Setup-Other-Banned_Emails" href="configbannedemails.php">{$_ADMINLANG.setup.bannedemails}</a></li>{/if}
        {if in_array("Configure Database Backups",$admin_perms)}<li><a id="Menu-Setup-Other-Database_Backups" href="configbackups.php">{$_ADMINLANG.setup.backups}</a></li>{/if}
        </ul>
    </li>{/if}
  </ul>
</li>
<li><a id="Menu-Help" title="Help" href=""><span class="hidden-xs">{$_ADMINLANG.help.title}</span><span class="visible-xs"><i class="far fa-life-ring"></i></span></a>
  <ul>
    <li><a id="Menu-Help-Documentation" href="http://docs.whmcs.com/" target="_blank">{$_ADMINLANG.help.docs}</a></li>
    {if in_array("Main Homepage",$admin_perms)}<li><a id="Menu-Help-License_Information" href="{routePath('admin-help-license')}">{$_ADMINLANG.help.licenseinfo}</a></li>{/if}
    {if in_array("Configure Administrators",$admin_perms)}<li><a id="Menu-Help-Change_License_Key" href="licenseerror.php?licenseerror=change">{$_ADMINLANG.help.changelicense}</a></li>{/if}
    {if in_array("Health and Updates", $admin_perms)}
        <li>
            <a id="Menu-Help-Check_Health_Updates" href="systemhealthandupdates.php">
                {$_ADMINLANG.healthCheck.menuTitle}
            </a>
        </li>
    {/if}
    {if in_array("View What's New",$admin_perms)}
        <li>
            <a id="Menu-Help-Whats_New" href="javascript:openFeatureHighlights()">
                {$_ADMINLANG.whatsNew.menuTitle}
            </a>
        </li>
    {/if}
    {if in_array("Configure General Settings",$admin_perms)}
        <li><a id="Menu-Help-Setup_Wizard" href="#" onclick="openSetupWizard();return false;">{$_ADMINLANG.help.setupWizard}</a></li>
        <li><a id="Menu-Help-Get_Help" href="systemsupportrequest.php">{$_ADMINLANG.help.support}</a></li>
    {/if}
    <li><a id="Menu-Help-Community_Forums" href="https://whmcs.community/?utm_source=InApp&utm_medium=Help_Menu" target="_blank">{$_ADMINLANG.help.forums}</a></li>
  </ul>
</li>
</ul>
</div>
