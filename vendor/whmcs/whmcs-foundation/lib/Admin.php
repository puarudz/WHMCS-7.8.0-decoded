<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Admin
{
    use Admin\ApplicationSupport\View\Traits\AdminUserContextTrait;
    use Admin\ApplicationSupport\View\Traits\NotificationTrait;
    public $loginRequired = true;
    public $requiredPermission = "";
    public $title = "";
    public $sidebar = "";
    public $icon = "";
    public $helplink = "";
    public $jscode = "";
    public $internaljquerycode = array();
    public $jquerycode = "";
    public $template = "";
    public $content = "";
    public $templatevars = array();
    public $filename = "";
    public $rowLimit = 50;
    public $tablePagination = true;
    public $adminTemplate = self::DEFAULT_ADMIN_TEMPLATE;
    public $exitmsg = "";
    public $language = "english";
    public $extrajscode = array();
    public $headOutput = array();
    public $chartFunctions = array();
    public $sortableTableCount = 0;
    protected $tabPrefix = "";
    public $smarty = "";
    protected $notificationContent = "";
    protected $bodyContent = "";
    protected $headerContent = "";
    protected $footerContent = "";
    protected $responseType = self::RESPONSE_HTML;
    protected $translateJqueryDefined = false;
    protected $standardVariablesLoaded = false;
    protected $tabCount = 1;
    protected $defaultTabOpen = false;
    private $adminRoleId = NULL;
    protected $topBarNotifications = array();
    const DEFAULT_ADMIN_TEMPLATE = "blend";
    const RESPONSE_JSON_MODAL_MESSAGE = "JSON_MODAL_MESSAGE";
    const RESPONSE_JSON_MESSAGE = Http\Message\ResponseFactory::RESPONSE_TYPE_JSON;
    const RESPONSE_JSON = "JSON";
    const RESPONSE_HTML_MESSAGE = Http\Message\ResponseFactory::RESPONSE_TYPE_HTML;
    const RESPONSE_HTML = "HTML";
    public function __construct($reqpermission, $releaseSession = true)
    {
        global $jquerycode;
        global $jscode;
        global $infobox;
        $jquerycode = $jscode = $infobox = "";
        if (defined("PERFORMANCE_DEBUG")) {
            define("PERFORMANCE_STARTTIME", microtime());
        }
        $whmcs = \App::self();
        $whmcsAppConfig = $whmcs->getApplicationConfig();
        $licensing = \DI::make("license");
        if ($licensing->isUnlicensed()) {
            $whmcs->redirectToRoutePath("admin-license-required");
        }
        try {
            $licensing->validate();
            if ($licensing->getStatus() != "Active") {
                redir("status=" . $licensing->getStatus(), Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
            }
        } catch (Exception\Http\ConnectionError $e) {
            redir("status=noconnection", Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
        } catch (Exception $e) {
            Session::setAndRelease("licenseCheckError", $e->getMessage());
            redir("", Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
        }
        if ($whmcs->isSSLAvailable() && !$whmcs->in_ssl()) {
            $whmcs->redirectSystemURL($whmcs->get_admin_folder_name() . "/" . $whmcs->getCurrentFilename(false), $_REQUEST);
        }
        if ($reqpermission == "loginonly") {
            $this->loginRequired = true;
        } else {
            if ($reqpermission) {
                $this->requiredPermission = $reqpermission;
            } else {
                $this->loginRequired = false;
            }
        }
        if ($this->loginRequired) {
            $auth = new Auth();
            if (!$auth->isLoggedIn()) {
                $auth->redirectToLogin();
            }
            $auth->getInfobyID($_SESSION["adminid"]);
            if ($auth->isSessionPWHashValid()) {
                $auth->updateAdminLog();
                $this->adminTemplate = $auth->getAdminTemplate();
                if ($auth->getAdminLanguage()) {
                    $this->language = $auth->getAdminLanguage();
                }
                $this->setAdminRoleId($auth->getAdminRoleId());
            } else {
                $auth->destroySession();
                redir("", "login.php");
            }
        }
        if ($releaseSession) {
            Session::release();
        }
        if ($this->requiredPermission) {
            $permissionId = $this->getPermissionIdFromName($this->requiredPermission);
            if (!$this->hasPermissionId($permissionId, $this->getAdminRoleID())) {
                redir("permid=" . $permissionId, "accessdenied.php");
            }
        }
        $filename = $_SERVER["PHP_SELF"];
        $filename = substr($filename, strrpos($filename, "/"));
        $filename = str_replace(array("/", ".php"), "", $filename);
        if ($this->loginRequired && isset($_SESSION["adminid"])) {
            $twofa = new TwoFactorAuthentication();
            $twofa->setAdminID($_SESSION["adminid"]);
            if ($filename != "myaccount" && $twofa->isForced() && !$twofa->isEnabled() && $twofa->isActiveAdmins()) {
                redir("2faenforce=1", "myaccount.php");
            }
        }
        if (\App::getFromRequest("clientlimitdismiss")) {
            $this->dismissClientLimitNotification(\App::getFromRequest("name"));
            throw new Exception\ProgramExit();
        }
        if (\App::getFromRequest("clientlimitdontshowagain")) {
            $this->dismissClientLimitNotificationPermanently(\App::getFromRequest("name"));
            throw new Exception\ProgramExit();
        }
        $this->filename = $filename;
        $this->rowLimit = $whmcs->get_config("NumRecordstoDisplay");
        if (isset($_SESSION["adminlang"]) && $_SESSION["adminlang"]) {
            $this->language = $_SESSION["adminlang"];
        }
        try {
            if (\AdminLang::getName() != $this->language) {
                \DI::forgetInstance("adminlang");
                $adminLang = \DI::make("adminlang", array($this->language));
                \AdminLang::swap($adminLang);
            } else {
                \DI::make("adminlang");
            }
        } catch (\Exception $e) {
            throw new Exception\Fatal(View\Helper::applicationError("Error Preparing Admin Language", $e->getMessage(), $e));
        }
        if (in_array($this->requiredPermission, array("Add/Edit Client Notes", "Add New Order", "Edit Clients Details", "Edit Transaction", "List Invoices", "List Support Tickets", "List Transactions", "Manage Billable Items", "Manage Quotes", "Open New Ticket", "View Activity Log", "View Billable Items", "View Clients Domains", "View Clients Notes", "View Clients Products/Services", "View Clients Summary", "View Email Message Log", "View Orders", "View Reports", "View Support Ticket"))) {
            $this->addHeadOutput((new Admin\ApplicationSupport\View\Html\Helper\ClientSearchDropdown(""))->getFormattedHtmlHeadContent());
            if ($whmcs->isInRequest("dropdownsearchq")) {
                $search = new Admin\Search\Controller\ClientController();
                $response = $search->searchRequest(Http\Message\ServerRequest::fromGlobals());
                (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
                throw new Exception\ProgramExit();
            }
        }
    }
    public function getResponseType()
    {
        return $this->responseType;
    }
    public function setResponseType($responseType)
    {
        $this->responseType = $responseType;
        return $this;
    }
    public function isModalResponseType()
    {
        return $this->responseType == static::RESPONSE_JSON_MODAL_MESSAGE;
    }
    public function isHtmlMessageResponseType()
    {
        return $this->responseType == static::RESPONSE_HTML_MESSAGE;
    }
    public function isJsonMessageResponseType()
    {
        return $this->responseType == static::RESPONSE_JSON_MESSAGE;
    }
    public function isJsonResponseType()
    {
        return strtoupper($this->responseType) == static::RESPONSE_JSON;
    }
    public function isHtmlResponseType()
    {
        return strtoupper($this->responseType) == static::RESPONSE_HTML;
    }
    public static function getID()
    {
        return (int) Session::get("adminid");
    }
    public function getAdminID()
    {
        return self::getID();
    }
    public function getAdminRoleID()
    {
        return $this->adminRoleId;
    }
    protected function setAdminRoleId($roleId)
    {
        $this->adminRoleId = (int) $roleId;
    }
    public function hasPermission($permissionName)
    {
        if (!$permissionName) {
            throw new Exception("Permission name cannot be empty");
        }
        if (!($adminId = Admin::getID())) {
            throw new Exception("Could not determine admin user id");
        }
        if (!($roleId = $this->getAdminRoleID())) {
            throw new Exception("Could not determine admin role");
        }
        if (!($permissionId = $this->getPermissionIdFromName($permissionName))) {
            throw new Exception(sprintf("Unknown ACL \"%s\"", $permissionName));
        }
        return $this->hasPermissionId($permissionId, $roleId);
    }
    protected function hasPermissionId($permissionId, $roleId = 0)
    {
        static $permissions = NULL;
        if (!is_array($permissions)) {
            $permissions = Database\Capsule::table("tbladminperms")->where("roleid", $roleId)->pluck("permid");
        }
        if (in_array($permissionId, $permissions)) {
            return true;
        }
        return false;
    }
    protected function getPermissionIdFromName($permissionName)
    {
        $adminPerms = getAdminPermsArray();
        $permissionId = array_search($permissionName, $adminPerms);
        return (int) $permissionId;
    }
    public function requiredFiles($reqfiles)
    {
        if (is_array($reqfiles)) {
            foreach ($reqfiles as $filename) {
                require ROOTDIR . "/includes/" . $filename . ".php";
            }
        }
    }
    public function setTemplate($tplname)
    {
        $this->template = $tplname;
    }
    public function assign($tplvar, $value = NULL)
    {
        $this->templatevars[$tplvar] = $value;
    }
    public function clientsDropDown($selectedValue, $autoSubmit = false, $fieldName = "userid", $anyOption = false, $tabOrder = 0)
    {
        $viewPartial = new Admin\ApplicationSupport\View\Html\Helper\ClientSelectedDropDown($fieldName, $selectedValue);
        $js = $viewPartial->getFormattedHtmlHeadContent();
        if (strpos($this->headOutput, $js) === false) {
            $this->addHeadOutput($js);
        }
        return $viewPartial->getFormattedBodyContent();
    }
    public function productStatusDropDown($status, $anyop = false, $name = "status", $id = "")
    {
        $statuses = array("Pending", "Active", "Completed", "Suspended", "Terminated", "Cancelled", "Fraud");
        $code = "<select name=\"" . $name . "\" class=\"form-control select-inline\"" . ($id ? " id=\"" . $id . "\"" : "") . ">";
        if ($anyop) {
            $code .= "<option value=\"\">" . $this->lang("global", "any") . "</option>";
        }
        foreach ($statuses as $stat) {
            $code .= "<option value=\"" . $stat . "\"";
            if ($status == $stat) {
                $code .= " selected";
            }
            $code .= ">" . $this->lang("status", strtolower($stat)) . "</option>";
        }
        $code .= "</select>";
        return $code;
    }
    public function getTemplate($template, $runAdminAreaPageHook = true)
    {
        global $_ADMINLANG;
        $smarty = $this->factoryAdminSmarty();
        $smarty->assign("_ADMINLANG", $_ADMINLANG);
        foreach ($this->templatevars as $key => $value) {
            $smarty->assign($key, $value);
        }
        if ($runAdminAreaPageHook) {
            $hookvars = $this->templatevars;
            unset($hookvars["_ADMINLANG"]);
            $hookres = run_hook("AdminAreaPage", $hookvars);
            foreach ($hookres as $arr) {
                foreach ($arr as $k => $v) {
                    $hookvars[$k] = $v;
                    $smarty->assign($k, $v);
                }
            }
        }
        $template_output = $smarty->fetch($this->adminTemplate . "/" . $template . ".tpl");
        return $template_output;
    }
    public function getTemplatePath()
    {
        $whmcs = Application::getInstance();
        return ROOTDIR . "/" . $whmcs->get_admin_folder_name() . "/templates/";
    }
    protected function factoryAdminSmarty()
    {
        return new Smarty(true);
    }
    public function display()
    {
        if ($this->getResponseType() != static::RESPONSE_HTML && $this->getResponseType() != static::RESPONSE_HTML_MESSAGE) {
            return $this->output();
        }
        global $_ADMINLANG;
        $this->smarty = $this->factoryAdminSmarty();
        if (count($this->chartFunctions)) {
            $chartredrawjs = "function redrawCharts() { ";
            foreach ($this->chartFunctions as $chartfunc) {
                $chartredrawjs .= $chartfunc . "(); ";
            }
            $chartredrawjs .= "}";
            $this->extrajscode[] = $chartredrawjs;
            $this->extrajscode[] = "\$(window).bind(\"resize\", function(event) { redrawCharts(); });";
        }
        $jquerycode = count($this->internaljquerycode) ? implode("\n", $this->internaljquerycode) : "";
        if ($this->jquerycode) {
            $jquerycode .= "\n" . $this->jquerycode;
        }
        $this->assign("jquerycode", $jquerycode);
        $mentionsFormat = "@\${username}";
        $data = Config\Setting::getValue("AdminUserNamesWithSpaces");
        if ($data && $data === "1") {
            $mentionsFormat .= "#";
        }
        $mentionsJs = "var mentionsFormat = '" . $mentionsFormat . "';" . PHP_EOL;
        $this->assign("jscode", $mentionsJs . $this->jscode . implode("\n", $this->extrajscode));
        $this->assign("_ADMINLANG", $_ADMINLANG);
        if (!$this->standardVariablesLoaded) {
            $this->populateStandardAdminSmartyVariables();
        }
        $this->assignToSmarty();
        return $this->output();
    }
    public function populateStandardAdminSmartyVariables()
    {
        $whmcs = \App::self();
        $whmcsConfiguration = $whmcs->getApplicationConfig();
        $disableAdminTicketPageCounts = $whmcsConfiguration["disable_admin_ticket_page_counts"];
        $this->assign("charset", $whmcs->get_config("Charset"));
        $this->assign("template", $this->adminTemplate);
        $this->assign("pagetemplate", $this->template);
        if (isset($_SESSION["adminid"])) {
            $this->assign("adminid", $_SESSION["adminid"]);
        }
        $this->assign("filename", $this->filename);
        $this->assign("pagetitle", $this->title);
        $this->assign("helplink", str_replace(" ", "_", $this->helplink));
        $this->assign("sidebar", $this->sidebar);
        $this->assign("minsidebar", isset($_COOKIE["WHMCSMinSidebar"]) ? true : false);
        $this->assign("pageicon", $this->icon);
        if (!isset($this->templatevars["ticketfilterdata"])) {
            $this->assign("ticketfilterdata", array("view" => "", "deptid" => "", "subject" => "", "email" => ""));
        }
        if (!isset($this->templatevars["inticket"])) {
            $this->assign("inticket", false);
        }
        if (!isset($this->templatevars["inticketlist"])) {
            $this->assign("inticketlist", false);
        }
        $this->assign("phoneNumberInputStyle", (int) Config\Setting::getValue("PhoneNumberDropdown"));
        $this->assign("csrfToken", generate_token("plain"));
        $this->assign("topBarNotification", "");
        $this->assign("versionHash", View\Helper::getAssetVersionHash());
        $assetHelper = \DI::make("asset");
        $this->assign("WEB_ROOT", $assetHelper->getWebRoot());
        $this->assign("BASE_PATH_CSS", $assetHelper->getCssPath());
        $this->assign("BASE_PATH_JS", $assetHelper->getJsPath());
        $this->assign("BASE_PATH_FONTS", $assetHelper->getFontsPath());
        $this->assign("BASE_PATH_IMG", $assetHelper->getImgPath());
        $this->assign("datepickerformat", str_replace(array("DD", "MM", "YYYY"), array("dd", "mm", "yy"), $whmcs->get_config("DateFormat")));
        if (Session::get("adminid")) {
            try {
                $admin = User\Admin::findOrFail(Session::get("adminid"));
            } catch (\Exception $e) {
                $this->standardVariablesLoaded = true;
                $this->gracefulExit($e->getMessage());
            }
            $adminID = $admin->id;
            $adminUsername = (string) $admin->firstName . " " . $admin->lastName;
            $adminNotes = $admin->notes;
            $adminRoleID = $admin->roleId;
            $this->assign("admin_username", ucfirst($adminUsername));
            $this->assign("adminFullName", $adminUsername);
            $this->assign("admin_notes", $adminNotes);
            $adminSupportDepartments = $admin->supportdepts;
            $adminPermissions = array();
            $adminPermissionsArray = getAdminPermsArray();
            $rolePermissions = Database\Capsule::table("tbladminperms")->where("roleid", "=", $adminRoleID)->get();
            foreach ($rolePermissions as $rolePermission) {
                if (isset($adminPermissionsArray[$rolePermission->permid])) {
                    $adminPermissions[] = $adminPermissionsArray[$rolePermission->permid];
                }
            }
            $addonModulesPermissions = safe_unserialize($whmcs->get_config("AddonModulesPerms"));
            if ($addonModulesPermissions === false) {
                $addonModulesPermissions = array();
            }
            $this->assign("admin_perms", $adminPermissions);
            $this->assign("addon_modules", array_key_exists($adminRoleID, $addonModulesPermissions) ? $addonModulesPermissions[$adminRoleID] : array());
            $this->assign("adminLanguage", $admin->language);
        }
        $this->assign("intelligentSearch", array("autoSearchEnabled" => Search\IntelligentSearchAutoSearch::isEnabled()));
        $locales = \AdminLang::getLocales();
        $this->assign("locales", $locales);
        $activeLocale = null;
        foreach ($locales as $locale) {
            if ($locale["language"] == \AdminLang::getName()) {
                $activeLocale = $locale;
                break;
            }
        }
        $carbonObject = new Carbon();
        $carbonObject->setLocale($activeLocale["languageCode"]);
        $this->templatevars["carbon"] = $carbonObject;
        $admins = array();
        foreach (User\AdminLog::with("admin")->online()->get() as $adminOnline) {
            $admins[] = $adminOnline->adminusername;
        }
        $this->assign("adminsonline", implode(", ", $admins));
        $this->assign("menuticketstatuses", Database\Capsule::table("tblticketstatuses")->orderBy("sortorder")->pluck("title"));
        $ticketStats = null;
        if ($this->sidebar == "support") {
            $params = array("includeCountsByStatus" => !$disableAdminTicketPageCounts);
            $ticketStats = localApi("GetTicketCounts", $params);
            $ticketCounts = array();
            $ticketStatuses = Database\Capsule::table("tblticketstatuses")->orderBy("sortorder")->pluck("title");
            foreach ($ticketStatuses as $status) {
                $normalisedStatus = preg_replace("/[^a-z0-9]/", "", strtolower($status));
                $ticketCounts[] = array("title" => $status, "count" => isset($ticketStats["status"][$normalisedStatus]["count"]) ? $ticketStats["status"][$normalisedStatus]["count"] : 0);
            }
            $allActive = $ticketStats["allActive"];
            $awaitingReply = $ticketStats["awaitingReply"];
            $flaggedTickets = $ticketStats["flaggedTickets"];
            $flaggedTicketsChecked = true;
            $this->assign("ticketsallactive", $allActive);
            $this->assign("ticketsawaitingreply", $awaitingReply);
            $this->assign("ticketsflagged", $flaggedTickets);
            $this->assign("ticketcounts", $ticketCounts);
            $this->assign("ticketstatuses", $ticketCounts);
            $departments = array();
            $departmentsData = Support\Department::whereIn("id", $ticketStats["filteredDepartments"])->orderBy("order")->get(array("id", "name"));
            foreach ($departmentsData as $department) {
                $departments[] = array("id" => $department->id, "name" => $department->name);
            }
            $this->assign("ticketdepts", $departments);
        }
        if (checkPermission("Sidebar Statistics", true)) {
            $templateVariables = array("orders" => array(), "clients" => array(), "services" => array(), "domains" => array(), "invoices" => array(), "tickets" => array());
            $pendingOrderStatuses = array();
            $dbPendingOrderStatuses = Database\Capsule::table("tblorderstatuses")->where("showpending", "=", 1)->get(array("title"));
            foreach ($dbPendingOrderStatuses as $pendingOrderStatus) {
                $pendingOrderStatuses[] = $pendingOrderStatus->title;
            }
            if (0 < count($pendingOrderStatuses)) {
                $pendingOrderCounts = Database\Capsule::table("tblorders")->join("tblclients", "tblclients.id", "=", "tblorders.userid")->whereIn("tblorders.status", $pendingOrderStatuses)->count();
                $templateVariables["orders"]["pending"] = $pendingOrderCounts;
            }
            $clients = User\Client::groupBy("status")->selectRaw("count(id) as count, status")->pluck("count", "status")->all();
            foreach (array("Active", "Inactive", "Closed") as $status) {
                $templateVariables["clients"][strtolower($status)] = array_key_exists($status, $clients) ? $clients[$status] : 0;
            }
            $services = Service\Service::groupBy("domainstatus")->selectRaw("count(id) as count, domainstatus")->pluck("count", "domainstatus")->all();
            foreach (array("Pending", "Active", "Suspended", "Completed", "Terminated", "Cancelled", "Fraud") as $status) {
                $templateVariables["services"][strtolower($status)] = array_key_exists($status, $services) ? $services[$status] : 0;
            }
            $domains = Domain\Domain::groupBy("status")->selectRaw("count(id) as count, status")->pluck("count", "status")->all();
            foreach ((new Domain\Status())->all() as $status) {
                $templateVariables["domains"][str_replace(" ", "", strtolower($status))] = array_key_exists($status, $domains) ? $domains[$status] : 0;
            }
            $templateVariables["invoices"]["unpaid"] = Billing\Invoice::unpaid()->count("id");
            $templateVariables["invoices"]["overdue"] = Billing\Invoice::overdue()->count("id");
            if (!$disableAdminTicketPageCounts) {
                if (is_null($ticketStats)) {
                    $params = array("includeCountsByStatus" => !$disableAdminTicketPageCounts);
                    $ticketStats = localApi("GetTicketCounts", $params);
                }
                $templateVariables["tickets"]["active"] = $ticketStats["allActive"];
                $templateVariables["tickets"]["awaitingreply"] = $ticketStats["awaitingReply"];
                $templateVariables["tickets"]["flagged"] = $ticketStats["flaggedTickets"];
                $ticketStatistics = array();
                if (!empty($ticketStats["status"])) {
                    foreach ($ticketStats["status"] as $status) {
                        $ticketStatistics[$status["title"]] = $status["count"];
                    }
                }
                $templateVariables["tickets"]["onhold"] = array_key_exists("On Hold", $ticketStatistics) ? $ticketStatistics["On Hold"] : "0";
                $templateVariables["tickets"]["inprogress"] = array_key_exists("In Progress", $ticketStatistics) ? $ticketStatistics["In Progress"] : "0";
            }
            $this->assign("sidebarstats", $templateVariables);
        }
        $this->standardVariablesLoaded = true;
        $this->assign("clientLimitNotification", $this->getGlobalClientLimitNotification($adminRoleID, \DI::make("license")));
        if (isset($this->templatevars["clientLimitNotification"]["attemptUpgrade"])) {
            $this->templatevars["jscode"] = $this->templatevars["jscode"] . "\nfunction licenseUpgradeFailed() {\n    \$(\".client-limit-notification-form\")\n        .find(\".panel-title i\").removeClass(\"fa-spinner\").removeClass(\"fa-spin\").addClass(\"fa-times\").end()\n        .find(\".panel-body p:first-child\").html(\"The automatic upgrade attempt has failed. Please click the Upgrade button below to complete your upgrade.\").end()\n        .find(\".panel-body .btn\").addClass(\"btn-link\").removeClass(\"btn-warning\");\n    \$(\"#btnClientLimitNotificationUpgrade\").addClass(\"btn-warning\").removeClass(\"btn-link\").removeClass(\"hidden\");\n}\n";
            $this->templatevars["jquerycode"] = $this->templatevars["jquerycode"] . "\n                WHMCS.http.jqClient.post(\"" . routePath("admin-help-license-upgrade-send") . "\", \$(\".client-limit-notification-form form\").serialize(),\n                function(data) {\n                    if (data.success) {\n                        \$(\".client-limit-notification-form\").addClass(\"panel-success\").removeClass(\"panel-warning\")\n                            .find(\".panel-title i\").removeClass(\"fa-spinner\").removeClass(\"fa-spin\").addClass(\"fa-check\").end()\n                            .find(\".panel-title small\").fadeOut(\"fast\").end()\n                            .find(\".panel-title span\").html(\"Client Limit Upgraded\").end()\n                            .find(\".panel-body p:first-child\").html(\"You have been automatically upgraded to the next license tier. The new price will take effect from your next renewal invoice.\").end()\n                            .find(\".panel-body .btn\").addClass(\"btn-success\").removeClass(\"btn-warning\");\n                    } else {\n                        licenseUpgradeFailed();\n                    }\n                }, \"json\")\n                .fail(function(data) {\n                    licenseUpgradeFailed();\n                });";
        }
    }
    public function getGlobalClientLimitNotification($adminRoleId, $licensing)
    {
        if ($adminRoleId != 1) {
            return null;
        }
        $clientLimitNotification = $licensing->getClientLimitNotificationAttributes();
        if ($this->isClientLimitNotificationDismissed($clientLimitNotification["title"])) {
            return null;
        }
        return $clientLimitNotification;
    }
    protected function dismissClientLimitNotification($title)
    {
        $titleParts = explode(" ", $title);
        Session::setAndRelease("ClientLimitNotificationDismissed" . implode($titleParts), true);
        return $this;
    }
    protected function dismissClientLimitNotificationPermanently($title)
    {
        $licensing = \DI::make("license");
        $dismisses = $this->getClientLimitNotificationDismisses();
        $dismisses[$title][$licensing->getClientLimit()][] = $this->getAdminID();
        Config\Setting::setValue("ClientLimitNotificationDismisses", json_encode($dismisses));
        return $this;
    }
    protected function isClientLimitNotificationDismissed($title)
    {
        $titleParts = explode(" ", $title);
        if (Session::get("ClientLimitNotificationDismissed" . implode($titleParts))) {
            return true;
        }
        $licensing = \DI::make("license");
        $dismisses = $this->getClientLimitNotificationDismisses();
        if (isset($dismisses[$title][$licensing->getClientLimit()]) && is_array($dismisses[$title][$licensing->getClientLimit()]) && in_array($this->getAdminID(), $dismisses[$title][$licensing->getClientLimit()])) {
            return true;
        }
        return false;
    }
    public function assignToSmarty()
    {
        foreach ($this->templatevars as $key => $value) {
            $this->smarty->assign($key, $value);
        }
    }
    public function output($jsonEncodingOptions = NULL)
    {
        if ($this->isJsonResponseType()) {
            $response = new Http\JsonResponse();
            header_remove("Content-Type");
            if (!is_null($jsonEncodingOptions)) {
                $response->setEncodingOptions($response->getEncodingOptions() | $jsonEncodingOptions);
            }
            $response->setData($this->getBodyContent());
            $response->send();
        } else {
            if ($this->isModalResponseType()) {
                $jQueryCode = "<script>\n" . "\$(document).ready(function(){" . $this->jquerycode . "});" . "\n</script>";
                return new Http\Message\JsonResponse(array("body" => $jQueryCode . $this->content));
            }
            if ($this->isJsonMessageResponseType()) {
                if ($jsonEncodingOptions) {
                    $jsonEncodingOptions = Http\Message\JsonResponse::DEFAULT_JSON_FLAGS | $jsonEncodingOptions;
                } else {
                    $jsonEncodingOptions = Http\Message\JsonResponse::DEFAULT_JSON_FLAGS;
                }
                $data = (array) $this->getBodyContent();
                return new Http\Message\JsonResponse($data, 200, array(), $jsonEncodingOptions);
            }
            ob_start();
            $whmcs = Application::getInstance();
            $hookvars = $this->templatevars;
            unset($hookvars["_ADMINLANG"]);
            $hookres = run_hook("AdminAreaPage", $hookvars);
            foreach ($hookres as $arr) {
                foreach ($arr as $k => $v) {
                    $hookvars[$k] = $v;
                    $this->smarty->assign($k, $v);
                }
            }
            $hookres = run_hook("AdminAreaHeadOutput", $hookvars);
            $headoutput = count($this->headOutput) ? implode("\n", $this->headOutput) : "";
            if (count($hookres)) {
                $headoutput .= "\n" . implode("\n", $hookres);
            }
            $this->smarty->assign("headoutput", $headoutput);
            $hookres = run_hook("AdminAreaHeaderOutput", $hookvars);
            $headeroutput = count($hookres) ? implode("\n", $hookres) : "";
            $this->smarty->assign("headeroutput", $headeroutput);
            $hookres = run_hook("AdminAreaFooterOutput", $hookvars);
            $footeroutput = count($hookres) ? implode("\n", $hookres) : "";
            $footeroutput .= "\n" . view("admin.utilities.date.footer");
            $this->smarty->assign("footeroutput", $footeroutput);
            if (\App::isUpdateAvailable() && $this->hasPermission("Update WHMCS")) {
                $this->addToTopBarNotifications("<a href=\"update.php\" class=\"update-now\">" . \AdminLang::trans("update.updateNow") . "</a>");
            }
            $this->smarty->assign("topBarNotification", $this->getTopBarNotifications());
            $this->smarty->assign("globalAdminWarningMsg", $this->getGlobalAdminWarningNotifications());
            $this->smarty->assign("globalAdminWarningDismissUrl", routePath("admin-dismiss-global-warning"));
            $header = $this->smarty->fetch($this->adminTemplate . "/header.tpl");
            $content = $header . $this->getPageContent();
            if ($this->template) {
                $content = $header . $this->smarty->fetch($this->adminTemplate . "/" . $this->template . ".tpl");
            }
            if ($whmcs->getCurrentFilename() != "systemintegrationcode") {
                $content = $this->autoAddTokensToForms($content);
            }
            echo $content;
            $footer_output = $this->smarty->fetch($this->adminTemplate . "/footer.tpl");
            $clientArea = new ClientArea();
            $licenseBannerMsg = $clientArea->getLicenseBannerMessage();
            if ($licenseBannerMsg) {
                $licenseBannerHtml = "<script type=\"text/javascript\">\n\$(function(){\n    \$(window).resize(function(e){\n        placeDevBanner();\n    });\n    \$.event.add(window, \"scroll\", function() {\n        placeDevBanner();\n    });\n    placeDevBanner();\n    \$(\"#whmcsdevbanner\").css(\"position\",\"absolute\");\n    \$(\"#whmcsdevbanner\").css(\"display\",\"inline\");\n    \$(\"body\").css(\"margin\",\"0 0 \"+\$(\"#whmcsdevbanner\").height()+\"px 0\");\n});\nfunction placeDevBanner() {\n    var docheight = \$(\"body\").height();\n    var newheight = \$(document).scrollTop() + parseInt(\$(window).height()) - parseInt(\$(\"#whmcsdevbanner\").height());\n    if (newheight>docheight) newheight = docheight;\n    \$(\"#whmcsdevbanner\").css(\"top\",newheight);\n    \$(\"body\").css(\"margin\",\"0 0 \"+\$(\"#whmcsdevbanner\").height()+\"px 0\");\n}\n</script>\n<div id=\"whmcsdevbanner\" style=\"display:block;margin:0;padding:0;width:100%;background-color:#ffffd2;\">\n    <div style=\"padding:10px 35px;font-size:16px;text-align:center;color:#555;\">" . $licenseBannerMsg . "</div>\n</div>";
                $bodypos = strpos($footer_output, "</body>");
                if ($bodypos === false) {
                    $footer_output = $footer_output . $licenseBannerHtml;
                } else {
                    $footer_output = substr($footer_output, 0, $bodypos) . $licenseBannerHtml . substr($footer_output, $bodypos);
                }
            }
            echo $footer_output;
            if (defined("PERFORMANCE_DEBUG")) {
                global $query_count;
                $exectime = microtime() - PERFORMANCE_STARTTIME;
                echo "<p>Performance Debug: " . $exectime . " Queries: " . $query_count . "</p>";
            }
            $html = ob_get_clean();
            if ($this->isHtmlMessageResponseType()) {
                $adminBaseUrl = Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"]);
                $adminDirectoryName = \App::get_admin_folder_name();
                $adminBaseUrl .= "/" . $adminDirectoryName;
                $adminBaseUrl = preg_replace("#([/]+)#", "/", $adminBaseUrl);
                if (substr($adminBaseUrl, -1) == "/") {
                    $adminBaseUrl = substr($adminBaseUrl, 0, -1);
                }
                if (substr($adminBaseUrl, 0, 1) != "/") {
                    $adminBaseUrl = "/" . $adminBaseUrl;
                }
                $html = preg_replace("#( src=\"| href=\"| action=\")((?!\\/|http|javascript)(?:[^\"]+)\")#i", "\\1" . $adminBaseUrl . "/\\2", $html);
                $html = View\Asset::conditionalFontawesomeCssInclude($html);
                return new \Zend\Diactoros\Response\HtmlResponse($html);
            }
            echo $html;
            return $html;
        }
    }
    public function displayPopUp()
    {
        $view = new Admin\ApplicationSupport\View\Html\PopUp($this->content);
        $view->setTitle($this->title);
        $view->setFavicon($this->icon);
        $view->setJquery(array($this->jquerycode));
        (new \Zend\Diactoros\Response\SapiEmitter())->emit($view);
        return $view;
    }
    public function sortableTableInit($defaultsort, $defaultorder = "ASC")
    {
        global $orderby;
        global $order;
        global $page;
        global $limit;
        global $tabledata;
        $sortpage = $this->filename;
        if ($defaultsort == "nopagination") {
            $this->tablePagination = false;
        } else {
            $this->tablePagination = true;
            $sortdata = isset($_COOKIE["sortdata"]) ? $_COOKIE["sortdata"] : "";
            $sortdata = json_decode(base64_decode($sortdata), true);
            if (!is_array($sortdata)) {
                $sortdata = array();
            }
            $xorderby = $sortdata[$sortpage . "orderby"];
            $xorder = $sortdata[$sortpage . "order"];
            if (!$xorderby) {
                $xorderby = $defaultsort;
            }
            if (!$xorder) {
                $xorder = $defaultorder;
            }
            if ($xorderby == $orderby) {
                if ($xorder == "ASC") {
                    $xorder = "DESC";
                } else {
                    $xorder = "ASC";
                }
            }
            if ($orderby) {
                $xorderby = $orderby;
            }
            $xorderby = trim(preg_replace("/[^a-z]/", "", strtolower($xorderby)));
            if (!in_array($xorder, array("ASC", "DESC"))) {
                $xorder = $defaultorder ? $defaultorder : "ASC";
            }
            $sortdata[$sortpage . "orderby"] = $xorderby;
            $sortdata[$sortpage . "order"] = $xorder;
            setcookie("sortdata", base64_encode(json_encode($sortdata)));
            $orderby = db_escape_string($xorderby);
            $order = db_escape_string($xorder);
        }
        if (!$page) {
            $page = 0;
        }
        $limit = $this->rowLimit;
        $this->sortableTableCount++;
        $tabledata = array();
    }
    public function sortableTable($columns, $tabledata, $formurl = "", $formbuttons = "", $topbuttons = "")
    {
        global $orderby;
        global $order;
        global $numrows;
        global $page;
        $pages = ceil($numrows / $this->rowLimit);
        if ($pages == 0) {
            $pages = 1;
        }
        $content = "";
        if ($this->tablePagination) {
            $varsrecall = "";
            foreach ($_REQUEST as $key => $value) {
                if (!in_array($key, array("orderby", "page", "PHPSESSID", "token")) && $value) {
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            if ($v) {
                                $varsrecall .= "<input type=\"hidden\" name=\"" . $key . "[" . $k . "]\" value=\"" . $v . "\" />" . "\n";
                            }
                        }
                    } else {
                        $varsrecall .= "<input type=\"hidden\" name=\"" . $key . "\" value=\"" . $value . "\" />" . "\n";
                    }
                }
            }
            if ($varsrecall) {
                $varsrecall = "\n" . $varsrecall;
            }
            $content .= "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">" . $varsrecall . "\n<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\"><tr>\n<td width=\"50%\" align=\"left\">" . $numrows . " " . $this->lang("global", "recordsfound") . ", " . $this->lang("global", "page") . " " . ($page + 1) . " " . $this->lang("global", "of") . " " . $pages . "</td>\n<td width=\"50%\" align=\"right\">" . $this->lang("global", "jumppage") . ": <select name=\"page\" onchange=\"submit()\">";
            for ($i = 1; $i <= $pages; $i++) {
                $newpage = $i - 1;
                $content .= "<option value=\"" . $newpage . "\"";
                if ($page == $newpage) {
                    $content .= " selected";
                }
                $content .= ">" . $i . "</option>";
            }
            $content .= "</select> <input type=\"submit\" value=\"" . $this->lang("global", "go") . "\" class=\"btn btn-xs btn-default\" /></td>\n</tr></table>\n</form>\n";
        }
        if ($formurl) {
            $content .= "<form method=\"post\" action=\"" . $formurl . "\">" . $varsrecall;
        }
        if ($topbuttons) {
            $content .= "<div style=\"padding-bottom:2px;\">" . $this->lang("global", "withselected") . ": " . $formbuttons . "</div>";
        }
        $content .= "\n<div class=\"tablebg\">\n<table id=\"sortabletbl" . $this->sortableTableCount . "\" class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr>";
        foreach ($columns as $column) {
            if (is_array($column)) {
                $sortableheader = true;
                list($columnid, $columnname, $width) = $column;
                if (!$columnid) {
                    $sortableheader = false;
                }
            } else {
                $sortableheader = false;
                $columnid = $width = "";
                $columnname = $column;
            }
            if (!$columnname) {
                $content .= "<th width=\"20\"></th>";
            } else {
                if ($columnname == "checkall") {
                    $this->internaljquerycode[] = "\$(\"#checkall" . $this->sortableTableCount . "\").click(function () {\n    \$(\"#sortabletbl" . $this->sortableTableCount . " .checkall\").prop(\"checked\",this.checked);\n});";
                    $content .= "<th width=\"20\"><input type=\"checkbox\" id=\"checkall" . $this->sortableTableCount . "\"></th>";
                } else {
                    $width = $width ? " width=\"" . $width . "\"" : "";
                    $content .= "<th" . $width . ">";
                    if ($sortableheader) {
                        $content .= "<a href=\"" . $_SERVER["PHP_SELF"] . "?";
                        foreach ($_REQUEST as $key => $value) {
                            if ($key != "orderby" && $key != "PHPSESSID" && $value) {
                                $content .= "" . $key . "=" . $value . "&";
                            }
                        }
                        $content .= "orderby=" . $columnid . "\">";
                    }
                    $content .= $columnname;
                    if ($sortableheader) {
                        $content .= "</a>";
                        if ($orderby == $columnid) {
                            $content .= " <img src=\"images/" . strtolower($order) . ".gif\" class=\"absmiddle\" />";
                        }
                    }
                    $content .= "</th>";
                }
            }
        }
        $content .= "</tr>\n";
        $totalcols = count($columns);
        if (is_array($tabledata) && count($tabledata)) {
            foreach ($tabledata as $tablevalues) {
                if ($tablevalues[0] == "dividingline") {
                    $content .= "<tr><td colspan=\"" . $totalcols . "\" style=\"background-color:#efefef;\"><div align=\"left\"><b>" . $tablevalues[1] . "</b></div></td></tr>\n";
                } else {
                    $content .= "<tr>";
                    foreach ($tablevalues as $tablevalue) {
                        $content .= "<td>" . $tablevalue . "</td>";
                    }
                    $content .= "</tr>\n";
                }
            }
        } else {
            $content .= "<tr><td colspan=\"" . $totalcols . "\">" . $this->lang("global", "norecordsfound") . "</td></tr>\n";
        }
        $content .= "</table>\n</div>\n";
        if ($formbuttons) {
            $content .= "" . $this->lang("global", "withselected") . ": " . $formbuttons;
        }
        if ($formurl) {
            $content .= "</form>";
        }
        if ($this->tablePagination) {
            $content .= "<ul class=\"pager\">";
            if (0 < $page) {
                $prevoffset = $page - 1;
                $content .= "<li class=\"previous\"><a href=\"" . $_SERVER["PHP_SELF"] . "?";
                foreach ($_REQUEST as $key => $value) {
                    if ($key != "orderby" && $key != "page" && $key != "PHPSESSID" && $value) {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                if ($v) {
                                    $content .= $key . "[" . $k . "]=" . $v . "&";
                                }
                            }
                        } else {
                            $content .= (string) $key . "=" . $value . "&";
                        }
                    }
                }
                $content .= "page=" . $prevoffset . "\">&laquo; " . $this->lang("global", "previouspage") . "</a></li>";
            } else {
                $content .= "<li class=\"previous disabled\"><a href=\"#\">&laquo; " . $this->lang("global", "previouspage") . "</a></li>";
            }
            if (($page * $this->rowLimit + $this->rowLimit) / $this->rowLimit == $pages) {
                $content .= "<li class=\"next disabled\"><a href=\"#\">" . $this->lang("global", "nextpage") . " &raquo;</a></li>";
            } else {
                $newoffset = $page + 1;
                $content .= "<li class=\"next\"><a href=\"" . $_SERVER["PHP_SELF"] . "?";
                foreach ($_REQUEST as $key => $value) {
                    if ($key != "orderby" && $key != "page" && $key != "PHPSESSID" && $value) {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                if ($v) {
                                    $content .= $key . "[" . $k . "]=" . $v . "&";
                                }
                            }
                        } else {
                            $content .= (string) $key . "=" . $value . "&";
                        }
                    }
                }
                $content .= "page=" . $newoffset . "\">" . $this->lang("global", "nextpage") . " &raquo;</a></li>";
            }
            $content .= "</ul>";
        }
        return $content;
    }
    public function setClientsProfilePresets($userId = 0)
    {
        $this->title = \AdminLang::trans("clientsummary.title");
        $this->sidebar = "clients";
        $this->icon = "clientsprofile";
        $this->setHeaderContent($this->getClientsProfileHeader($userId));
        $this->setFooterContent("</div></div>");
        return $this;
    }
    protected function getClientsProfileHeader($userId = 0)
    {
        $uid = $userId ?: (int) $GLOBALS["userid"];
        $tabarray = array();
        $tabarray["clientssummary"] = \AdminLang::trans("clientsummary.summary");
        $tabarray["clientsprofile"] = \AdminLang::trans("clientsummary.profile");
        $tabarray["clientscontacts"] = \AdminLang::trans("clientsummary.contacts");
        $tabarray["clientsservices"] = \AdminLang::trans("clientsummary.products");
        $tabarray["clientsdomains"] = \AdminLang::trans("clientsummary.domains");
        $tabarray["clientsbillableitems"] = \AdminLang::trans("clientsummary.billableitems");
        $tabarray["clientsinvoices"] = \AdminLang::trans("clientsummary.invoices");
        $tabarray["clientsquotes"] = \AdminLang::trans("clientsummary.quotes");
        $tabarray["clientstransactions"] = \AdminLang::trans("clientsummary.transactions");
        $tabarray["clients-tickets"] = array("label" => \AdminLang::trans("clientsummary.tickets"), "routePath" => routePath("admin-client-tickets", $uid));
        $tabarray["clientsemails"] = \AdminLang::trans("clientsummary.emails");
        $tabarray["clientsnotes"] = \AdminLang::trans("clientsummary.notes") . " (" . get_query_val("tblnotes", "COUNT(id)", array("userid" => $uid)) . ")";
        $tabarray["clientslog"] = \AdminLang::trans("clientsummary.log");
        $content = "<form action=\"" . $_SERVER["PHP_SELF"] . "\" method=\"get\">\n    <div class=\"row client-dropdown-container\">\n        <div class=\"col-md-5 col-sm-9\">\n            " . $this->clientsDropDown($uid) . "\n            <input type=\"submit\" id=\"goButton\" value=\"Go\" class=\"btn btn-primary hidden\" />\n        </div>\n    </div>\n</form>";
        $markup = new View\Markup\Markup();
        $clientnotes = array();
        $result = select_query("tblnotes", "tblnotes.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblnotes.adminid) AS adminuser", array("userid" => $uid, "sticky" => "1"), "modified", "DESC");
        while ($data = mysql_fetch_assoc($result)) {
            $markupFormat = $markup->determineMarkupEditor("client_note", "", $data["modified"]);
            $data["note"] = $markup->transform($data["note"], $markupFormat);
            $data["created"] = fromMySQLDate($data["created"], 1);
            $data["modified"] = fromMySQLDate($data["modified"], 1);
            $clientnotes[] = $data;
        }
        if ($clientnotes) {
            $content .= $this->formatImportantClientNotes($clientnotes);
        }
        $request = Http\Message\ServerRequest::fromGlobals();
        $content .= "\n<ul class=\"nav nav-tabs client-tabs\" role=\"tablist\">" . PHP_EOL;
        $count = 1;
        foreach ($tabarray as $link => $data) {
            $uri = $link . ".php?userid=" . $uid;
            $routePath = "";
            if (is_array($data)) {
                $name = $data["label"];
                if (isset($data["routePath"])) {
                    $uri = $data["routePath"];
                }
                $routePath = $request->getUri()->getPath();
                if ($request->getUri()->getQuery()) {
                    $routePath .= "?" . $request->getUri()->getQuery();
                }
            } else {
                $name = $data;
            }
            if ($link == $this->filename || is_array($data) && isset($data["routePath"]) && $uri == $routePath) {
                $class = "active";
            } else {
                $class = "tab";
            }
            $content .= "<li class=\"" . $class . "\"><a href=\"" . $uri . "\" id=\"clientTab-" . $count . "\">" . $name . "</a></li>" . PHP_EOL;
            $count++;
        }
        $content .= "</ul>\n<div class=\"tab-content client-tabs\">\n  <div class=\"tab-pane active\" id=\"profileContent\">";
        $this->addHeadOutput(View\Asset::jsInclude("bootstrap-tabdrop.js"));
        $this->addHeadOutput(View\Asset::cssInclude("tabdrop.css"));
        $this->addHeadJqueryCode("\$(\".client-tabs\").tabdrop(); \$(window).resize();");
        return $content;
    }
    public function gracefulExit($msg)
    {
        $this->exitmsg = "<div class=\"gracefulexit\">" . $msg . "</div>";
        $this->display();
        exit;
    }
    public function cyclesDropDown($billingcycle, $any = "", $freeop = "", $name = "billingcycle", $onchange = "", $id = "")
    {
        if (!$freeop) {
            $freeop = $this->lang("billingcycles", "free");
        }
        if ($onchange) {
            $onchange = "onchange=\"" . $onchange . "\"";
        }
        if ($id) {
            $id = "id=\"" . $id . "\"";
        }
        $code = "<select name=\"" . $name . "\" class=\"form-control select-inline\"" . $onchange . $id . ">";
        if ($any) {
            $code .= "<option value=\"\">" . $this->lang("global", "any") . "</option>";
        }
        $code .= "<option value=\"Free Account\"";
        if (strcasecmp($billingcycle, "Free Account") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $freeop . "</option>";
        $code .= "<option value=\"One Time\"";
        if (in_array(strtolower($billingcycle), array("one time", "onetime"))) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "onetime") . "</option>";
        $code .= "<option value=\"Monthly\"";
        if (strcasecmp($billingcycle, "Monthly") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "monthly") . "</option>";
        $code .= "<option value=\"Quarterly\"";
        if (strcasecmp($billingcycle, "Quarterly") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "quarterly") . "</option>";
        $code .= "<option value=\"Semi-Annually\"";
        if (strcasecmp(str_replace("-", "", $billingcycle), "SemiAnnually") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "semiannually") . "</option>";
        $code .= "<option value=\"Annually\"";
        if (strcasecmp($billingcycle, "Annually") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "annually") . "</option>";
        $code .= "<option value=\"Biennially\"";
        if (strcasecmp($billingcycle, "Biennially") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "biennially") . "</option>";
        $code .= "<option value=\"Triennially\"";
        if (strcasecmp($billingcycle, "Triennially") === 0) {
            $code .= " selected";
        }
        $code .= ">" . $this->lang("billingcycles", "triennially") . "</option>";
        $code .= "</select>";
        return $code;
    }
    public function jqueryDialog($name, $title, $message, $buttons = array(), $height = "", $width = "", $alerttype = "alert")
    {
        static $dialogjsdone = false;
        $jquerycode = "\$(\"#" . $name . "\").dialog({\n    autoOpen: false,\n    resizable: false,\n    ";
        if ($height) {
            $jquerycode .= "height: " . $height . ",\n    ";
        }
        if ($width) {
            $jquerycode .= "width: " . $width . ",\n    ";
        }
        $jquerycode .= "modal: true,\n    buttons: {";
        $buttoncode = "";
        foreach ($buttons as $k => $v) {
            if (!$v) {
                $v = "\$(this).dialog('close');";
            }
            $id = View\Helper::generateCssFriendlyId($name, $k);
            $buttoncode .= "'" . $k . "': {\n                text: \"" . $k . "\",\n                id: \"" . $id . "\",\n                click: function() {\n                " . $v . "\n            }\n        },";
        }
        $jquerycode .= substr($buttoncode, 0, -1) . "}\n});\n";
        $this->internaljquerycode[] = $jquerycode;
        $alerticon = "";
        if ($alerttype == "alert") {
            $alerticon = "<span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin:0 7px 40px 0;\"></span>";
        }
        $htmlcode = "<div id=\"" . $name . "\" title=\"" . $title . "\" style=\"display:none;\">\n    <p>" . $alerticon . $message . "</p>\n</div>\n";
        if (!$dialogjsdone) {
            $this->extrajscode[] = "function showDialog(name) {\n\$(\"#\"+name).dialog('open');\n}";
        }
        $dialogjsdone = true;
        return $htmlcode;
    }
    public function outputClientLink($userid, $firstname = "", $lastname = "", $companyname = "", $groupid = "", $newWindow = false)
    {
        static $clientgroups = "";
        static $ClientOutputData = array();
        static $ContactOutputData = array();
        $contactid = 0;
        if (is_array($userid)) {
            list($userid, $contactid) = $userid;
        }
        if (!is_array($clientgroups)) {
            $clientgroups = getClientGroups();
        }
        if (!$firstname && !$lastname && !$companyname) {
            if (isset($ClientOutputData[$userid])) {
                $data = $ClientOutputData[$userid];
            } else {
                $result = select_query("tblclients", "firstname,lastname,companyname,groupid", array("id" => $userid));
                $data = mysql_fetch_array($result);
                $ClientOutputData[$userid] = $data;
            }
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $groupid = $data["groupid"];
            if ($contactid) {
                if (isset($ContactOutputData[$contactid])) {
                    $contactdata = $ContactOutputData[$contactid];
                } else {
                    $contactdata = get_query_vals("tblcontacts", "firstname,lastname", array("id" => $contactid, "userid" => $userid));
                    $ContactOutputData[$contactid] = $contactdata;
                }
                $firstname = $contactdata["firstname"];
                $lastname = $contactdata["lastname"];
            }
        }
        $style = isset($clientgroups[$groupid]["colour"]) ? " style=\"background-color:" . $clientgroups[$groupid]["colour"] . "\"" : "";
        if ($newWindow) {
            $clientlink = "<a href=\"clientssummary.php?userid=" . $userid . "\"" . $style . " target=\"_blank\">";
        } else {
            $clientlink = "<a href=\"clientssummary.php?userid=" . $userid . "\"" . $style . ">";
        }
        switch (Config\Setting::getValue("ClientDisplayFormat")) {
            case 2:
                if ($companyname) {
                    $clientlink .= $companyname;
                } else {
                    $clientlink .= $firstname . " " . $lastname;
                }
                break;
            case 3:
                $clientlink .= $firstname . " " . $lastname;
                if ($companyname) {
                    $clientlink .= " (" . $companyname . ")";
                }
                break;
            default:
                $clientlink .= $firstname . " " . $lastname;
        }
        $clientlink .= "</a>";
        return $clientlink;
    }
    public function lang($section, $var, $escape = "")
    {
        $translated = \AdminLang::trans((string) $section . "." . $var);
        if ($escape) {
            return addslashes($translated);
        }
        if ($translated == (string) $section . "." . $var) {
            if (defined("DEVMODE")) {
                return "Missing Language Var \"" . $section . "." . $var . "\"";
            }
            return "";
        }
        return $translated;
    }
    public function deleteJSConfirm($name, $langtype, $langvar, $url)
    {
        $this->extrajscode[] = "function " . $name . "(id) {\nif (confirm(\"" . $this->lang($langtype, $langvar, 1) . "\")) {\nwindow.location='" . $url . "'+id+'" . generate_token("link") . "';\n}}";
    }
    public function popupWindow($link, $formId = "", $width = 600, $height = 400, $output = true)
    {
        if (!$this->popupwincount) {
            $this->popupwincount = 0;
        }
        $this->popupwincount++;
        $formSubmit = "";
        if (0 < strlen(trim($formId))) {
            $formSubmit = "\$(\"#" . $formId . "\").submit();";
        }
        $this->extrajscode[] = "function popupWin" . $this->popupwincount . "()\n{\n    var winl = (screen.width - " . $width . ") / 2;\n    var wint = (screen.height - " . $height . ") / 2;\n    " . $formSubmit . "\n    win = window.open('" . $link . "', 'popwin" . $this->popupwincount . "', 'height = " . $height . ", width = " . $width . ", top =' + wint + ', left =' + winl + ', scrollbars = yes');\n}";
        if ($output) {
            echo "popupWin" . $this->popupwincount . "(); return false;";
        }
    }
    public function valUserID($tempuid)
    {
        global $userid;
        global $clientsdetails;
        $userid = (int) $tempuid;
        if (!function_exists("getClientsDetails")) {
            require ROOTDIR . "/includes/clientfunctions.php";
        }
        $clientsdetails = getClientsDetails($userid);
        $_GET["userid"] = $clientsdetails["userid"];
        $_POST["userid"] = $_GET["userid"];
        $_REQUEST["userid"] = $_POST["userid"];
        $userid = $_REQUEST["userid"];
        if (!$userid) {
            $this->gracefulExit($this->lang("clients", "invalidclientid"));
        }
        return $userid;
    }
    public function richTextEditor()
    {
        echo View\Asset::jsInclude("tinymce/tinymce.min.js") . "<script type=\"text/javascript\">\n    var tinymceSettings = {\n            selector: \"textarea.tinymce\",\n            height: 500,\n            theme: \"modern\",\n            entity_encoding: \"raw\",\n            plugins: \"autosave print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template code codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists textcolor wordcount imagetools contextmenu colorpicker textpattern help\",\n            toolbar: [\n                \"formatselect,fontselect,fontsizeselect,|,bold,italic,strikethrough,underline,forecolor,backcolor,|,link,unlink,|,justifyleft,justifycenter,justifyright,justifyfull,|,search,replace,|,bullist,numlist,\",\n                \"outdent,indent,blockquote,|,undo,redo,|,cut,copy,paste,pastetext,pasteword,|,table,|,hr,|,sub,sup,|,charmap,media,|,print,|,ltr,rtl,|,fullscreen,|,help,code,removeformat\"\n            ],\n            image_advtab: true,\n            content_css: [\n                \"//fonts.googleapis.com/css?family=Lato:300,300i,400,400i\",\n                \"//www.tinymce.com/css/codepen.min.css\"\n            ],\n            browser_spellcheck: true,\n            convert_urls : false,\n            relative_urls : false,\n            forced_root_block : \"p\",\n            media_poster: false,\n            mobile: {\n                theme: \"mobile\",\n                plugins: [\"autosave\", \"lists\", \"autolink\"],\n                toolbar: [\"undo\", \"bold\", \"italic\", \"styleselect\"]\n            },\n            menu: {\n                file: {title: \"File\", items: \"preview | print\"},\n                edit: {title: \"Edit\", items: \"undo redo | cut copy paste pastetext | selectall | searchreplace\"},\n                view: {title: \"View\", items: \"visualaid visualchars visualblocks | preview fullscreen\"},\n                insert: {title: \"Insert\", items: \"image link media codesample | charmap hr\"},\n                format: {title: \"Format\", items: \"bold italic strikethrough underline superscript subscript codeformat | blockformats align | removeformat\"},\n                table: {title: \"Table\", items: \"inserttable tableprops deletetable | cell row column\"},\n                help: {title: \"Help\", items: \"help | code\"}\n            }\n        };\n\n    \$(document).ready(function() {\n        tinymce.init(tinymceSettings).then(function(editors){\n            editorLoaded = true;\n        });\n    });\n\n    var editorEnabled = true,\n        editorLoaded = false;\n\n    function toggleEditor() {\n        if (editorEnabled === true) {\n            tinymce.activeEditor.remove();\n            editorEnabled = false;\n        } else {\n            tinymce.init(tinymceSettings);\n            editorEnabled = true;\n        }\n    }\n\n    function insertMergeField(mfield) {\n        tinymce.activeEditor.insertContent('{\$' + mfield + '}');\n    }\n\n</script>\n";
    }
    public function productDropDown($pid = 0, $noneopt = "", $anyopt = "")
    {
        $code = "";
        if ($anyopt) {
            $code .= "<option value=\"\">" . \AdminLang::trans("global.any") . "</option>";
        }
        if ($noneopt) {
            $code .= "<option value=\"\">" . \AdminLang::trans("global.none") . "</option>";
        }
        $groupname = "";
        $products = new Product\Products();
        $productsList = $products->getProducts();
        foreach ($productsList as $data) {
            $packid = $data["id"];
            $gid = $data["gid"];
            $name = $data["name"];
            $packtype = $data["groupname"];
            if ($packtype != $groupname) {
                if (!$groupname) {
                    $code .= "</optgroup>";
                }
                $code .= "<optgroup label=\"" . $packtype . "\">";
                $groupname = $packtype;
            }
            if (!$data["retired"] || $pid == $packid) {
                $code .= "<option value=\"" . $packid . "\"";
                if ($pid == $packid) {
                    $code .= " selected";
                }
                $code .= ">" . $name . "</option>";
            }
        }
        $code .= "</optgroup>";
        return $code;
    }
    public function dialog($funccall = "", $content = "", $draggable = true)
    {
        if (!$content) {
            $content = "<div style=\"padding:70px;text-align:center;\"><img src=\"images/loader.gif\" /></div>";
        }
        if ($funccall) {
            $content .= "<form><input type=\"hidden\" name=\"" . $funccall . "\" value=\"1\" /></form>";
        }
        $this->extrajscode[] = "\n\nvar dialoginit = false;\n\n\$(window).resize(function() {\n  dialogCenter();\n});\n\nfunction dialogOpen() {\n\n    \$(\"body\").css(\"overflow\",\"hidden\");\n\n    if (!dialoginit) {\n\n    \$(\"body\").append(\"<div id=\\\"bgfilter\\\"></div>\");\n    \$(\"#bgfilter\").css(\"position\",\"absolute\").css(\"top\",\"0\").css(\"left\",\"0\").css(\"width\",\"100%\").css(\"height\",\$(\"body\").height()).css(\"background-color\",\"#ccc\").css(\"display\",\"block\").css(\"filter\",\"alpha(opacity=70)\").css(\"-moz-opacity\",\"0.7\").css(\"-khtml-opacity\",\"0.7\").css(\"opacity\",\"0.7\").css(\"z-index\",\"1000\");\n\n    \$(\"body\").append(\"<div class=\\\"admindialog\\\" id=\\\"dl1\\\"><a href=\\\"#\\\" onclick=\\\"dialogClose();return false\\\" class=\\\"close\\\">&times;</a><div id=\\\"admindialogcont\\\">" . addslashes($content) . "</div></div>\");\n    \$(\"#dl1\").css(\"position\",\"absolute\");\n    \$(\"#dl1\").css(\"z-index\",\"1001\");\n\n    dialoginit = true;\n\n    } else {\n\n    \$(\"#dl1\").html(\"<a href=\\\"#\\\" onclick=\\\"dialogClose();return false\\\" class=\\\"close\\\">x</a><div id=\\\"admindialogcont\\\">" . addslashes($content) . "</div>\");\n\n    }\n\n    dialogCenter();\n    \$(\"#dl1\").show();" . ($draggable ? "\$(\"#dl1\").draggable();" : "") . ($funccall ? "dialogSubmit();" : "") . "\n\n}\n\nfunction dialogCenter() {\n    \$(\"#dl1\").css(\"top\",Math.max(50, ((\$(window).height() - \$(\"#dl1\").outerHeight()) / 2) + \$(window).scrollTop() - 100 ) + \"px\");\n    \$(\"#dl1\").css(\"left\",Math.max(0, ((\$(window).width() - \$(\"#dl1\").outerWidth()) / 2) + \$(window).scrollLeft()) + \"px\");\n}\n\nfunction dialogSubmit() {\n    WHMCS.http.jqClient.post(\"" . $_SERVER["PHP_SELF"] . "\", \$(\"#admindialogcont\").find(\"form\").serialize(),\n    function(data){\n        jQuery(\"#admindialogcont\").html(data);\n        dialogCenter();\n    });\n}\n\nfunction dialogClose() {\n    \$(\"#dl1\").fadeOut(\"\",function() {\n        \$(\"#bgfilter\").fadeOut();\n        \$(\"body\").css(\"overflow\",\"inherit\");\n    });\n}\n\n\$(document).keydown(function(e) {\n    if (e.which == 27) {\n        dialogClose();\n    }\n});\n\nfunction dialogChangeTab(id) {\n    \$(\"#admindialogcont .content .boxy\").fadeOut();\n    \$(\"#admindialogcont .content .boxy\").promise().done(function() {\n        \$(\"#admindialogcont .content .boxy\").hide();\n        \$(\"#\"+id).fadeIn();\n    });\n}\n\n";
    }
    public function addHeadOutput($output)
    {
        $this->headOutput[] = $output;
    }
    public function addHeadJsCode($code)
    {
        $this->extrajscode[] = $code . PHP_EOL;
    }
    public function addHeadJqueryCode($code)
    {
        $this->internaljquerycode[] = $code;
    }
    public function addInternalJQueryCode($code)
    {
        $this->addHeadJQueryCode($code);
    }
    protected function setHeaderContent($text = "")
    {
        $this->headerContent = $text;
        return $this;
    }
    public function autoAddTokensToForms($content)
    {
        return preg_replace("/(<form\\W[^>]*\\bmethod=('|\"|)POST('|\"|)\\b[^>]*>)/i", "\\1" . "\n" . generate_token(), $content);
    }
    public function assertClientBoundary($userId, $errorMessage = NULL)
    {
        $licensing = \DI::make("license");
        if ($licensing->isClientLimitsEnabled()) {
            $limitClientId = $licensing->getClientBoundaryId();
            if (0 < $limitClientId && $limitClientId <= $userId) {
                if (is_null($errorMessage)) {
                    $errorMessage = "Sorry, your request cannot be completed." . "<br /><br />" . "The maximum number of clients allowed by your license has been reached." . "<br />The associated client is beyond your license" . htmlentities("'") . "s limit." . "<br /><br />" . "Please <a href=\"" . routePath("admin-help-license") . "\">upgrade</a> in order to access and manage this user.";
                }
                $this->gracefulExit($errorMessage);
            }
        }
        return $this;
    }
    protected function getHeaderContent()
    {
        return $this->headerContent;
    }
    public function setNotificationContent($text = "")
    {
        $this->notificationContent = $text;
        return $this;
    }
    public function getNotificationContent()
    {
        return $this->notificationContent;
    }
    public function setBodyContent($data = "")
    {
        if (is_array($data)) {
            $this->responseType = "JSON";
        }
        $this->bodyContent = $data;
        return $this;
    }
    public function getBodyContent()
    {
        return $this->bodyContent;
    }
    public function setFooterContent($text = "")
    {
        $this->footerContent = $text;
        return $this;
    }
    public function getFooterContent()
    {
        return $this->footerContent;
    }
    public function getPageContent()
    {
        if ($this->exitmsg) {
            $this->content = "";
            $this->setBodyContent($this->exitmsg);
        }
        return $this->getHeaderContent() . $this->getNotificationContent() . $this->getBodyContent() . $this->content . $this->getFooterContent();
    }
    public function beginAdminTabs(array $tabs = array(), $defaultTabOpen = false, $tabPrefix = "")
    {
        $this->tabPrefix = $tabPrefix;
        $this->defaultTabOpen = $defaultTabOpen;
        $whmcs = \App::self();
        $selectedTab = (int) $whmcs->get_req_var("tab");
        if ($selectedTab && $tabPrefix && strpos($selectedTab, $tabPrefix) === 0) {
            $selectedTab = str_replace($tabPrefix, "", $selectedTab);
        }
        $code = "<ul class=\"nav nav-tabs admin-tabs\" role=\"tablist\">" . PHP_EOL;
        foreach ($tabs as $i => $label) {
            $tabIDNum = $i + 1;
            if ($selectedTab != 0) {
                $isTabActive = $selectedTab == $tabIDNum ? " class=\"active\"" : "";
            } else {
                $isTabActive = $defaultTabOpen && !$selectedTab && $i == 0 ? " class=\"active\"" : "";
            }
            $code .= "<li" . $isTabActive . ">" . "<a class=\"tab-top\" href=\"#tab" . $tabPrefix . $tabIDNum . "\" role=\"tab\" data-toggle=\"tab\" id=\"tabLink" . $tabIDNum . "\" data-tab-id=\"" . $tabIDNum . "\">" . $label . "</a></li>" . PHP_EOL;
        }
        $isActive = "";
        if ($selectedTab == 1) {
            $isActive = " active";
        }
        if ($defaultTabOpen && !$selectedTab) {
            $isActive = " active";
        }
        $code .= "</ul>" . PHP_EOL . "<div class=\"tab-content admin-tabs\">" . PHP_EOL . "  <div class=\"tab-pane" . $isActive . "\" id=\"tab" . $tabPrefix . $this->tabCount . "\">";
        $this->tabCount++;
        if (3 <= count($tabs)) {
            $this->addHeadOutput(View\Asset::jsInclude("bootstrap-tabdrop.js"));
            $this->addHeadOutput(View\Asset::cssInclude("tabdrop.css"));
            $this->addHeadJqueryCode("\$(\".admin-tabs\").tabdrop(); \$(window).resize();");
        }
        $additionalJQueryCode = "\$( \"a.tab-top\" ).click( function() {\n    var tabId = \$(this).data('tab-id');\n    \$(\"#tab" . $tabPrefix . "\").val(tabId);\n    window.location.hash = 'tab=' + " . $tabPrefix . " + tabId;\n});\n\nvar selectedTab = " . $selectedTab . ";\n\nif (selectedTab == 0) {\n    refreshedTab = window.location.hash;\n    if (refreshedTab) {\n        refreshedTab = refreshedTab.substring(5);\n        \$(\"a[href='#tab\" + " . $tabPrefix . " + refreshedTab + \"']\").click();\n    }\n}\n";
        $this->addHeadJqueryCode($additionalJQueryCode);
        if (!$defaultTabOpen) {
            $this->addHeadJqueryCode($this->makeAdminTabsToggle());
        }
        return $code;
    }
    public function nextAdminTab()
    {
        $whmcs = \App::self();
        $selectedTab = (int) $whmcs->get_req_var("tab");
        $isActive = $this->defaultTabOpen && $this->tabCount == $selectedTab ? " active" : "";
        $code = "  </div>" . PHP_EOL . "  <div class=\"tab-pane" . $isActive . "\" id=\"tab" . $this->tabPrefix . $this->tabCount . "\">";
        $this->tabCount++;
        return $code;
    }
    public function endAdminTabs()
    {
        return "  </div>" . "</div>";
    }
    protected function makeAdminTabsToggle()
    {
        $tabPrefix = $this->tabPrefix;
        $idPosition = strlen("#tab" . $tabPrefix);
        $togglableTabsJQueryCode = "/**\n * We want to make the adminTabs on this page toggle\n */\n\$( \"a[href^='#tab" . $tabPrefix . "']\" ).click( function() {\n    var tabID = \$(this).attr('href').substr(" . $idPosition . ");\n    var tabToHide = \$(\"#tab" . $tabPrefix . "\" + tabID);\n    if(tabToHide.hasClass('active')) {\n        tabToHide.removeClass('active');\n    }  else {\n        tabToHide.addClass('active')\n    }\n});";
        return $togglableTabsJQueryCode;
    }
    public function modal($name, $title, $message, array $buttons = array(), $size = "", $panelType = "primary")
    {
        switch ($size) {
            case "small":
                $dialogClass = "modal-dialog modal-sm";
                break;
            case "large":
                $dialogClass = "modal-dialog modal-lg";
                break;
            default:
                $dialogClass = "modal-dialog";
        }
        switch ($panelType) {
            case "default":
            case "primary":
            case "success":
            case "info":
            case "warning":
            case "danger":
                $panel = "panel-" . $panelType;
                break;
            default:
                $panel = "panel-primary";
        }
        $buttonsOutput = "";
        foreach ($buttons as $button) {
            $id = View\Helper::generateCssFriendlyId($name, $button["title"]);
            $onClick = isset($button["onclick"]) ? "onclick='" . $button["onclick"] . "'" : "data-dismiss=\"modal\"";
            $class = isset($button["class"]) ? $button["class"] : "btn-default";
            $type = isset($button["type"]) ? $button["type"] : "button";
            $buttonsOutput .= "<button type=\"" . $type . "\" id=\"" . $id . "\" class=\"btn " . $class . "\" " . $onClick . ">\n    " . $button["title"] . "\n</button>";
        }
        $modalOutput = "<div class=\"modal fade\" id=\"modal" . $name . "\" role=\"dialog\" aria-labelledby=\"" . $name . "Label\" aria-hidden=\"true\">\n    <div class=\"" . $dialogClass . "\">\n        <div class=\"modal-content panel " . $panel . "\">\n            <div id=\"modal" . $name . "Heading\" class=\"modal-header panel-heading\">\n                <button type=\"button\" class=\"close\" data-dismiss=\"modal\">\n                    <span aria-hidden=\"true\">&times;</span>\n                    <span class=\"sr-only\">" . $this->lang("global", "close") . "</span>\n                </button>\n                <h4 class=\"modal-title\" id=\"" . $name . "Label\">" . $title . "</h4>\n            </div>\n            <div id=\"modal" . $name . "Body\" class=\"modal-body panel-body\">\n                " . $message . "\n            </div>\n            <div id=\"modal" . $name . "Footer\" class=\"modal-footer panel-footer\">\n                " . $buttonsOutput . "\n            </div>\n        </div>\n    </div>\n</div>";
        return $modalOutput;
    }
    public function modalWithConfirmation($name, $question, $url, $jsVariable = "invoice")
    {
        $name = View\Helper::generateCssFriendlyId($name);
        $token = generate_token("link");
        $okOnClick = "window.location='" . $url . "' + " . $jsVariable . " + '" . $token . "';";
        $modalOutput = "<div class=\"modal fade\" id=\"" . $name . "\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"" . $name . "Label\" aria-hidden=\"true\">\n    <div class=\"modal-dialog\">\n        <div class=\"modal-content panel\">\n            <div class=\"modal-body panel-body\">\n                " . $question . "\n            </div>\n            <div class=\"modal-footer panel-footer\">\n                <button type='button' id='" . $name . "-cancel' class='btn btn-default' data-dismiss='modal'>\n                    " . $this->lang("global", "cancel") . "\n                </button>\n                <button type='button' id='" . $name . "-ok' class='btn btn-primary' data-dismiss='modal' onclick=\"" . $okOnClick . "\">\n                    " . $this->lang("global", "ok") . "\n                </button>\n            </div>\n        </div>\n    </div>\n</div>";
        $this->extrajscode[] = "function " . $name . "(id) {\n    " . $jsVariable . " = id;\n    \$('#" . $name . "').modal('show');\n}";
        return $modalOutput;
    }
    public function clientSearchDropdown($name, $selectedOption = "", array $options = array(), $placeHolder = "", $valueAttribute = "id", $tabOrder = 0)
    {
        $viewPartial = new Admin\ApplicationSupport\View\Html\Helper\ClientSearchDropdown($name, $selectedOption, $options, $placeHolder, $valueAttribute, $tabOrder);
        $js = $viewPartial->getFormattedHtmlHeadContent();
        if (in_array($js, $this->headOutput) === false) {
            $this->addHeadOutput($js);
        }
        return $viewPartial->getFormattedBodyContent();
    }
    public function performClientSearch($searchTerm)
    {
        $searchResults = array();
        $clientId = (int) \App::getFromRequest("clientId");
        $matchingClients = Database\Capsule::table("tblclients");
        if ($searchTerm) {
            $matchingClients->whereRaw("CONCAT(firstname, ' ', lastname) LIKE '%" . $searchTerm . "%'")->orWhere("email", "LIKE", "%" . $searchTerm . "%")->orWhere("companyname", "LIKE", "%" . $searchTerm . "%");
            if ((int) $searchTerm) {
                $matchingClients->orWhere("id", "=", (int) $searchTerm)->orWhere("id", "LIKE", "%" . (int) $searchTerm . "%");
            }
        } else {
            $matchingClients->limit(30);
        }
        if ($clientId && !$searchTerm) {
            static $clientCount = NULL;
            if (!$clientCount) {
                $clientCount = Database\Capsule::table("tblclients")->count("id");
            }
            $offsetStart = 15;
            if (15 < $clientId && 30 < $clientCount) {
                if ($clientCount < $clientId + 15) {
                    $offsetStart = 30 - ($clientCount - $clientId);
                }
                $matchingClients->offset($clientId - $offsetStart);
            }
        }
        foreach ($matchingClients->get() as $client) {
            $searchResults[] = array("id" => $client->id, "name" => Input\Sanitize::decode($client->firstname . " " . $client->lastname), "companyname" => Input\Sanitize::decode($client->companyname), "email" => Input\Sanitize::decode($client->email));
        }
        return $searchResults;
    }
    public function flash($title, $message, $status = "success")
    {
        $flashMessages = Session::get("flash");
        if (!is_array($flashMessages)) {
            $flashMessages = array();
        }
        $flashMessages[\App::getPhpSelf()] = array("title" => $title, "message" => $message, "status" => $status);
        Session::setAndRelease("flash", $flashMessages);
    }
    public function getFlash()
    {
        $phpSelf = \App::getPhpSelf();
        $flashMessages = Session::get("flash");
        if (isset($flashMessages[$phpSelf])) {
            $flashMessage = $flashMessages[$phpSelf];
            unset($flashMessages[$phpSelf]);
            Session::setAndRelease("flash", $flashMessages);
            return $flashMessage;
        }
        return false;
    }
    public function getFlashAsInfobox()
    {
        if ($flash = $this->getFlash()) {
            return infoBox($flash["title"], $flash["message"], $flash["status"]);
        }
        return "";
    }
    public function addMarkdownEditor($jsVariable = "openTicketMDE", $uniqueId = "ticket_open", $elementId = "replymessage", $addFilesToHead = true)
    {
        $locale = preg_replace("/[^a-zA-Z0-9_\\-]*/", "", \AdminLang::getLanguageLocale());
        $locale = $locale == "locale" ? "en" : substr($locale, 0, 2);
        $phpSelf = \App::getPhpSelf();
        $token = generate_token("plain");
        $this->addHeadJqueryCode("var element = jQuery(\"#" . $elementId . "\"),\n    counter = 0;\nvar " . $jsVariable . " = element.markdown(\n    {\n        footer: '<div id=\"" . $elementId . "-footer\" class=\"markdown-editor-status\"></div>',\n        autofocus: false,\n        savable: false,\n        resize: 'vertical',\n        iconlibrary: 'glyph',\n        language: '" . $locale . "',\n        onShow: function(e){\n            var content = '',\n                save_enabled = false;\n            if(typeof(Storage) !== \"undefined\") {\n                // Code for localStorage/sessionStorage.\n                content = localStorage.getItem(\"" . $uniqueId . "\");\n                save_enabled = true;\n                if (content && typeof(content) !== \"undefined\") {\n                    e.setContent(content);\n                }\n            }\n            jQuery(\"#" . $elementId . "-footer\").html(parseMdeFooter(content, save_enabled, 'saved'));\n        },\n        onChange: function(e){\n            var content = e.getContent(),\n                save_enabled = false;\n            if(typeof(Storage) !== \"undefined\") {\n                counter = 3;\n                save_enabled = true;\n                localStorage.setItem(\"" . $uniqueId . "\", content);\n                doCountdown();\n            }\n            jQuery(\"#" . $elementId . "-footer\").html(parseMdeFooter(content, save_enabled));\n        },\n        onPreview: function(e){\n            var originalContent = e.getContent(),\n                parsedContent;\n\n            jQuery.ajax({\n                url: '" . $phpSelf . "',\n                async: false,\n                data: {token: '" . $token . "', action: 'parseMarkdown', content: originalContent},\n                dataType: 'json',\n                success: function (data) {\n                    parsedContent = data;\n                }\n            });\n\n            return parsedContent.body ? parsedContent.body : '';\n        },\n        additionalButtons: [\n            [{\n                name: \"groupCustom\",\n                data: [{\n                    name: \"cmdHelp\",\n                    title: \"Help\",\n                    hotkey: \"Ctrl+F1\",\n                    btnClass: \"btn open-modal\",\n                    icon: {\n                        glyph: 'fas fa-question-circle',\n                        fa: 'fas fa-question-circle',\n                        'fa-3': 'icon-question-sign'\n                    },\n                    callback: function(e) {\n                        e.\$editor.removeClass(\"md-fullscreen-mode\");\n                    }\n                }]\n            }]\n        ],\n        hiddenButtons: [\n            'cmdImage'\n        ]\n    }\n);\n\njQuery('button[data-handler=\"bootstrap-markdown-cmdHelp\"]')\n    .attr('data-modal-title', 'Markdown Guide')\n    .attr('data-modal-size', 'modal-lg')\n    .attr('href', 'supporttickets.php?action=markdown');\n\nelement.closest(\"form\").bind({\n    submit: function() {\n        if(typeof(Storage) !== \"undefined\") {\n            // Code for localStorage/sessionStorage.\n            if (jQuery(this).attr('data-no-clear') == \"false\") {\n                localStorage.removeItem(\"" . $uniqueId . "\");\n            }\n        }\n    }\n});");
        if ($addFilesToHead) {
            $this->addHeadJqueryCode("function parseMdeFooter(content, auto_save, saveText)\n{\n    if (typeof saveText == 'undefined') {\n        saveText = 'autosaving';\n    }\n    var pattern = /[^\\s]+/g,\n        m = [],\n        word_count = 0,\n        line_count = 0;\n    if (content) {\n        m = content.match(pattern);\n        line_count = content.split(/\\r\\n|\\r|\\n/).length;\n    }\n    if (m) {\n        for(var i = 0; i < m.length; i++) {\n            if(m[i].charCodeAt(0) >= 0x4E00) {\n                word_count += m[i].length;\n            } else {\n                word_count += 1;\n            }\n        }\n    }\n    return '<div class=\"smallfont\">lines: ' + line_count\n        + '&nbsp;&nbsp;&nbsp;words: ' + word_count + ''\n        + (auto_save ? '&nbsp;&nbsp;&nbsp;<span class=\"markdown-save\">' + saveText + '</span>' : '')\n        + '</div>';\n}\n\nfunction doCountdown()\n{\n    if (counter >= 0) {\n        if (counter == 0) {\n            jQuery(\"span.markdown-save\").html('saved');\n        }\n        counter--;\n        setTimeout(doCountdown, 1000);\n    }\n}");
        }
    }
    public function formatImportantClientNotes(array $notes)
    {
        $output = "<div class=\"client-notes\">";
        foreach ($notes as $data) {
            $output .= "<div class=\"panel panel-warning\">\n    <div class=\"panel-heading\">\n        " . $data["adminuser"] . "\n        <div class=\"pull-right\">\n            " . $data["modified"] . "\n        </div>\n    </div>\n    <div class=\"panel-body\">\n        <div class=\"row\">\n            <div class=\"col-md-11\">\n                " . $data["note"] . "\n            </div>\n            <div class=\"col-md-1 pull-right text-right\">\n                <a href=\"clientsnotes.php?userid=" . $data["userid"] . "&action=edit&id=" . $data["id"] . "\">\n                    <img src=\"images/edit.gif\" width=\"16\" height=\"16\" align=\"absmiddle\" />\n                </a>\n            </div>\n        </div>\n    </div>\n</div>";
        }
        $output .= "</div>";
        return $output;
    }
    public function getTranslationLink($type, $id, $customFieldType = "")
    {
        if (!$type || !Config\Setting::getValue("EnableTranslations")) {
            return "";
        }
        $linkId = "translate" . str_replace(" ", "", ucwords(str_replace(array(".", "_"), " ", $type)));
        $linkTitle = \AdminLang::trans("global.translate");
        $save = \AdminLang::trans("global.savechanges");
        $displayType = str_replace(array("_", "."), array(" ", " "), $type);
        $modalTitle = \AdminLang::trans("dynamicTranslation.title") . " " . titleCase($displayType);
        if ($customFieldType && in_array($customFieldType, array("addon", "client", "product", "support"))) {
            $type .= "&cf-type=" . $customFieldType;
        }
        if (!$this->translateJqueryDefined) {
            $code = "jQuery('.btn-translate').click(function(e) {\n    e.preventDefault();\n    var url = jQuery(this).attr('href'),\n        modalTitle = jQuery(this).data('modal-title');\n\n    var origvalue = jQuery(this).closest('td').find('input').val();\n    if (!origvalue) {\n        origvalue = jQuery(this).closest('td').find('textarea').val();\n    }\n    var postData = 'origvalue=' + origvalue;\n\n    openModal(url, postData, modalTitle, \"modal-lg\", \"\", \"" . $save . "\", \"btnSaveTranslations\");\n});";
            $this->addHeadJqueryCode($code);
            $this->translateJqueryDefined = true;
        }
        return "<a id=\"" . $linkId . "\" href=\"configtranslations.php?type=" . $type . "&id=" . $id . "\" class=\"btn btn-default btn-translate\" data-modal-title=\"" . $modalTitle . "\"><i class=\"fas fa-edit\"></i> " . $linkTitle . "</a>";
    }
    protected function addToTopBarNotifications($htmlOutput)
    {
        $this->topBarNotifications[] = $htmlOutput;
        return $this;
    }
    protected function getTopBarNotifications()
    {
        return implode("\n", $this->topBarNotifications);
    }
    protected function getGlobalAdminWarningNotifications()
    {
        return $this->getGlobalWarningNotification();
    }
    protected function validateAuthConfirmation($password)
    {
        $auth = new Auth();
        $auth->getInfobyID($this->getAdminID());
        if ($auth->comparePassword($password)) {
            @Session::start();
            Session::set("AuthConfirmationTimestamp", Carbon::now()->getTimestamp());
            return true;
        }
        return false;
    }
    public function hasAuthConfirmation()
    {
        $authConfirmationTimestamp = Session::get("AuthConfirmationTimestamp");
        if (!empty($authConfirmationTimestamp) && is_numeric($authConfirmationTimestamp)) {
            $seconds = Carbon::createFromTimestamp($authConfirmationTimestamp)->diffInSeconds(Carbon::now());
            return $seconds <= 30 * 60;
        }
        return false;
    }
    public function requireAuthConfirmation()
    {
        $previousPost = !empty($_POST) ? $_POST : array();
        foreach (array("confirmpw", "token", "authconfirm") as $key) {
            unset($previousPost[$key]);
        }
        $this->assign("incorrect", false);
        if (\App::getFromRequest("authconfirm")) {
            if ($this->validateAuthConfirmation(\App::getFromRequest("confirmpw"))) {
                if (empty($previousPost)) {
                    $redirectParams = $_GET;
                    $redirectParams["nocache"] = \Illuminate\Support\Str::random();
                    \App::redirect(null, $redirectParams);
                }
            } else {
                $this->assign("incorrect", true);
            }
        }
        if (!$this->hasAuthConfirmation()) {
            $repostFields = array();
            $fillData = function ($key, $value, &$fields, $path = "") use(&$fillData) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $fillData($key, $subValue, $fields, $path . "[" . $subKey . "]");
                    }
                } else {
                    $fields[$key . $path] = $value;
                }
            };
            foreach ($previousPost as $key => $value) {
                $fillData($key, $value, $repostFields);
            }
            $this->assign("post_fields", $repostFields);
            $this->content = $this->getTemplate("authconfirm");
            $this->display();
            throw new Exception\ProgramExit();
        }
    }
    public function jsonResponse($data)
    {
        $this->setBodyContent($data);
        $this->output();
        Terminus::getInstance()->doExit();
    }
    public function getChartFunctions()
    {
        return $this->chartFunctions;
    }
    public static function dismissFeatureHighlightsUntilUpdateForAdmin($adminId, $forVersion = Notification\VersionFeatureHighlights::FEATURE_HIGHLIGHT_VERSION)
    {
        $data = json_decode(Config\Setting::getValue("FeatureHighlightsByAdmin"), true);
        if (!is_array($data)) {
            $data = array();
        }
        $data[$adminId] = $forVersion;
        Config\Setting::setValue("FeatureHighlightsByAdmin", json_encode($data));
    }
    public function isFeatureHighlightsDismissedUntilUpdate($forVersion = Notification\VersionFeatureHighlights::FEATURE_HIGHLIGHT_VERSION)
    {
        $data = json_decode(Config\Setting::getValue("FeatureHighlightsByAdmin"), true);
        if (!is_array($data)) {
            $data = array();
        }
        $adminId = $this->getAdminID();
        if (!array_key_exists($adminId, $data)) {
            return false;
        }
        try {
            $dismissedVersion = new Version\SemanticVersion($data[$adminId]);
        } catch (\Exception $e) {
            return false;
        }
        $currentVersion = new Version\SemanticVersion($forVersion);
        return Version\SemanticVersion::compare($currentVersion, $dismissedVersion, "<") || Version\SemanticVersion::compare($currentVersion, $dismissedVersion, "==");
    }
    public function removeFeatureHighlightsPermanentDismissal()
    {
        $data = json_decode(Config\Setting::getValue("FeatureHighlightsByAdmin"), true);
        if (!is_array($data)) {
            return true;
        }
        $adminId = $this->getAdminID();
        if (array_key_exists($adminId, $data)) {
            unset($data[$adminId]);
            Config\Setting::setValue("FeatureHighlightsByAdmin", json_encode($data));
        }
        return true;
    }
    public function dismissFeatureHighlightsUntilUpdate($forVersion = Notification\VersionFeatureHighlights::FEATURE_HIGHLIGHT_VERSION)
    {
        self::dismissFeatureHighlightsUntilUpdateForAdmin($this->getAdminID(), $forVersion);
    }
    public function dismissFeatureHighlightsForSession($forVersion = Notification\VersionFeatureHighlights::FEATURE_HIGHLIGHT_VERSION)
    {
        Session::setAndRelease("FeatureHighlightsSeenForVersion", $forVersion);
    }
    public function shouldSeeFeatureHighlights($forVersion = Notification\VersionFeatureHighlights::FEATURE_HIGHLIGHT_VERSION)
    {
        $sessionValue = Session::get("FeatureHighlightsSeenForVersion");
        try {
            if ($sessionValue) {
                $versionHighlightsWereSeenFor = new Version\SemanticVersion($sessionValue);
            } else {
                $data = json_decode(Config\Setting::getValue("FeatureHighlightsByAdmin"), true);
                $adminId = $this->getAdminID();
                if (!is_array($data) || !isset($data[$adminId])) {
                    return true;
                }
                $versionHighlightsWereSeenFor = new Version\SemanticVersion($data[$adminId]);
            }
            $versionHighlightsAreAvailableFor = new Version\SemanticVersion($forVersion);
            if (Version\SemanticVersion::compare($versionHighlightsWereSeenFor, $versionHighlightsAreAvailableFor, "<")) {
                return true;
            }
        } catch (\Exception $e) {
            return true;
        }
        return false;
    }
}

?>