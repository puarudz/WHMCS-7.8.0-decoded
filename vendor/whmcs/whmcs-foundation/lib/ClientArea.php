<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class ClientArea extends Http\Message\AbstractViewableResponse
{
    protected $renderedOutput = "";
    private $pageTitle = "";
    private $displayTitle = "";
    private $tagLine = "";
    private $breadcrumb = array();
    private $breadCrumbHtml = "";
    private $templateFile = "";
    private $templateVariables = array();
    private $wrappedWithHeaderFooter = true;
    private $inorderform = false;
    private $insupportmodule = false;
    protected $skipMainBodyContainer = false;
    protected $baseUrl = "";
    protected $outputHooks = array("ClientAreaPage");
    protected $client = NULL;
    private $smarty = "";
    public function __construct($data = "", $status = 200, array $headers = array())
    {
        parent::__construct($data, $status, $headers);
        if (defined("PERFORMANCE_DEBUG")) {
            define("PERFORMANCE_STARTTIME", microtime());
        }
        $this->initializeView();
    }
    public function isInOrderForm()
    {
        $this->inorderform = true;
    }
    public function resetRenderedOutput()
    {
        $this->renderedOutput = "";
        return $this;
    }
    protected function initializeView()
    {
        $this->resetRenderedOutput();
        $this->baseUrl = Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"]);
        global $smartyvalues;
        $preBuiltSmartyValues = $smartyvalues;
        $smartyvalues = array();
        $this->startSmarty();
        if (is_array($preBuiltSmartyValues)) {
            foreach ($preBuiltSmartyValues as $key => $value) {
                $this->smarty->assign($key, $value);
            }
        }
    }
    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
        return $this;
    }
    public function getPageTitle()
    {
        return $this->pageTitle;
    }
    public function addToBreadCrumb($link, $text)
    {
        if ($link instanceof \Psr\Http\Message\UriInterface) {
            $uri = (string) $link;
        } else {
            if (preg_match("#^http(s)?://#", $link)) {
                $uri = $link;
            } else {
                if ($link != "/" && ($this->baseUrl === "" || strpos($link, $this->baseUrl) !== 0)) {
                    if (strpos($link, "/") === 0) {
                        $link = substr($link, 1);
                    }
                    $uri = $this->baseUrl . "/" . $link;
                } else {
                    $uri = $link;
                }
            }
        }
        $this->breadcrumb[] = array("link" => $uri, "label" => $text);
        return $this;
    }
    public function resetBreadCrumb()
    {
        $this->breadcrumb = array();
        return $this;
    }
    public function getUserID()
    {
        return (int) Session::get("uid");
    }
    public function getClient()
    {
        return $this->client;
    }
    public function isLoggedIn()
    {
        return $this->getUserID() ? true : false;
    }
    public function requireLogin()
    {
        $whmcs = \App::self();
        if ($this->isLoggedIn()) {
            if (Session::get("2fabackupcodenew")) {
                $this->assign("showingLoginPage", true);
                $this->setTemplate("logintwofa");
                $twofa = new TwoFactorAuthentication();
                if ($twofa->setClientID($this->getUserID())) {
                    $backupcode = $twofa->generateNewBackupCode();
                    $this->assign("newbackupcode", $backupcode);
                    Session::delete("2fabackupcodenew");
                } else {
                    $this->assign("newbackupcodeerror", true);
                }
                $this->output();
                exit;
            }
            return true;
        }
        $_SESSION["loginurlredirect"] = html_entity_decode($_SERVER["REQUEST_URI"]);
        if (Session::get("2faverifyc")) {
            $this->assign("showingLoginPage", true);
            $this->setTemplate("logintwofa");
            if (Session::get("2fabackupcodenew")) {
                $this->assign("newbackupcode", true);
            } else {
                if ($whmcs->get_req_var("incorrect")) {
                    $this->assign("incorrect", true);
                }
            }
            $twofa = new TwoFactorAuthentication();
            if ($twofa->setClientID(Session::get("2faclientid"))) {
                if (!$twofa->isActiveClients() || !$twofa->isEnabled()) {
                    Session::destroy();
                    redir();
                }
                if ($whmcs->get_req_var("backupcode")) {
                    $this->assign("backupcode", true);
                } else {
                    $challenge = $twofa->moduleCall("challenge");
                    if ($challenge) {
                        $this->assign("challenge", $challenge);
                    } else {
                        $this->assign("error", "Bad 2 Factor Auth Module. Please contact support.");
                    }
                }
            } else {
                $this->assign("error", "An error occurred. Please try again.");
            }
        } else {
            $remoteAuthData = (new Authentication\Remote\Management\Client\ViewHelper())->getTemplateData(Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_LOGIN);
            foreach ($remoteAuthData as $key => $value) {
                $this->assign($key, $value);
            }
            $this->setTemplate("login");
            $this->assign("loginpage", true);
            $this->assign("showingLoginPage", true);
            $this->assign("formaction", Utility\Environment\WebHelper::getBaseUrl() . "/dologin.php");
            $this->assign("incorrect", (bool) $whmcs->get_req_var("incorrect"));
            $this->assign("ssoredirect", (bool) $whmcs->get_req_var("ssoredirect"));
            $this->assign("captcha", new Utility\Captcha());
            $this->assign("captchaForm", Utility\Captcha::FORM_LOGIN);
            $this->assign("invalid", Session::getAndDelete("CaptchaError"));
        }
        $this->output();
        exit;
    }
    public function setTemplate($template)
    {
        $this->templateFile = $template;
        return $this;
    }
    public function getTemplateFile()
    {
        return $this->templateFile;
    }
    public function assign($key, $value, $allowOverride = true)
    {
        if ($allowOverride || !isset($this->templateVariables[$key]) || !$this->smarty->tpl_vars[$key]) {
            $this->templateVariables[$key] = $value;
            $this->smarty->assign($key, $value);
        }
        return $this;
    }
    public function setTemplateVariables($data)
    {
        $this->templateVariables = array_merge($this->templateVariables, $data);
    }
    public function getTemplateVariables()
    {
        return $this->templateVariables;
    }
    public static function getRawStatus($val)
    {
        $val = strtolower($val);
        $val = str_replace(" ", "", $val);
        $val = str_replace("-", "", $val);
        return $val;
    }
    protected function startSmartyIfNotStarted()
    {
        if (is_object($this->smarty)) {
            return true;
        }
        return $this->startSmarty();
    }
    protected function startSmarty()
    {
        global $smarty;
        if (!$smarty) {
            $smarty = new Smarty();
        }
        $this->smarty =& $smarty;
        $this->initEmptyTemplateVars();
        return true;
    }
    protected function initEmptyTemplateVars()
    {
        $emptyTemplateParameters = array("displayTitle", "tagline", "type", "textcenter", "hide", "additionalClasses", "idname", "errorshtml", "title", "msg", "desc", "errormessage", "newbackupcode", "error", "livehelpjs", "editLink");
        foreach ($emptyTemplateParameters as $templateParam) {
            if (!isset($this->smarty->tpl_vars[$templateParam])) {
                $this->assign($templateParam, "", false);
            }
        }
        $this->assign("showbreadcrumb", false, false);
        $this->assign("showingLoginPage", false, false);
        $this->assign("incorrect", false, false);
        $this->assign("backupcode", false, false);
        $this->assign("newbackupcodeerror", false, false);
        $this->assign("kbarticle", array("title" => ""), false);
    }
    public function setDisplayTitle($displayTitle)
    {
        $this->displayTitle = $displayTitle;
        $this->assign("displayTitle", $displayTitle);
        return $this;
    }
    public function getDisplayTitle()
    {
        return $this->displayTitle;
    }
    public function setTagLine($tagline)
    {
        $this->tagLine = $tagline;
        $this->assign("tagline", $tagline);
        return $this;
    }
    public function getCurrentPageName()
    {
        $filename = $_SERVER["PHP_SELF"];
        $filename = substr($filename, strrpos($filename, "/"));
        $filename = str_replace("/", "", $filename);
        $filename = explode(".", $filename);
        $filename = $filename[0];
        return $filename;
    }
    protected function registerDefaultTPLVars()
    {
        global $_LANG;
        $whmcs = \App::self();
        $this->assign("template", $whmcs->getClientAreaTemplate()->getName());
        $this->assign("language", \Lang::getName());
        $this->assign("LANG", $_LANG);
        $this->assign("companyname", Config\Setting::getValue("CompanyName"));
        $this->assign("logo", Config\Setting::getValue("LogoURL"));
        $this->assign("charset", Config\Setting::getValue("Charset"));
        $this->assign("pagetitle", $this->pageTitle);
        $this->assign("filename", $this->getCurrentPageName());
        $this->assign("token", generate_token("plain"));
        $this->assign("reCaptchaPublicKey", Config\Setting::getValue("ReCAPTCHAPublicKey"));
        $this->assign("servedOverSsl", $whmcs->in_ssl());
        $this->assign("versionHash", View\Helper::getAssetVersionHash());
        if ($whmcs->getSystemURL() != "http://www.yourdomain.com/whmcs/") {
            $this->assign("systemurl", $whmcs->getSystemURL());
        }
        $this->assign("systemsslurl", $whmcs->getSystemURL());
        $this->assign("systemNonSSLURL", $whmcs->getSystemURL());
        $assetHelper = \DI::make("asset");
        $this->assign("WEB_ROOT", $assetHelper->getWebRoot());
        $this->assign("BASE_PATH_CSS", $assetHelper->getCssPath());
        $this->assign("BASE_PATH_JS", $assetHelper->getJsPath());
        $this->assign("BASE_PATH_FONTS", $assetHelper->getFontsPath());
        $this->assign("BASE_PATH_IMG", $assetHelper->getImgPath());
        $this->assign("todaysdate", date("l, jS F Y"));
        $this->assign("date_day", date("d"));
        $this->assign("date_month", date("m"));
        $this->assign("date_year", date("Y"));
        if (file_exists(ROOTDIR . "/assets/img/logo.png")) {
            $assetLogoPath = $assetHelper->getImgPath() . "/logo.png";
        } else {
            if (file_exists(ROOTDIR . "/assets/img/logo.jpg")) {
                $assetLogoPath = $assetHelper->getImgPath() . "/logo.jpg";
            } else {
                $assetLogoPath = "";
            }
        }
        $this->assign("assetLogoPath", $assetLogoPath);
        $this->assign("skipMainBodyContainer", $this->skipMainBodyContainer);
        $langChangeEnabled = $whmcs->get_config("AllowLanguageChange") ? true : false;
        $this->assign("langchange", $langChangeEnabled);
        $this->assign("languagechangeenabled", $langChangeEnabled);
        $this->assign("languages", \Lang::getLanguages());
        $locales = \Lang::getLocales();
        $this->assign("locales", $locales);
        $activeLocale = null;
        foreach ($locales as $locale) {
            if ($locale["language"] == \Lang::getName()) {
                $activeLocale = $locale;
                break;
            }
        }
        $this->assign("activeLocale", $activeLocale);
        $carbonObject = new Carbon();
        $carbonObject->setLocale($activeLocale["languageCode"]);
        $this->assign("carbon", $carbonObject);
    }
    protected function getCurrencyOptions()
    {
        $currenciesarray = array();
        $result = select_query("tblcurrencies", "id,code,prefix,suffix,`default`", "", "code", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $currenciesarray[] = array("id" => $data["id"], "code" => $data["code"], "prefix" => $data["prefix"], "suffix" => $data["suffix"], "default" => $data["default"]);
        }
        if (count($currenciesarray) == 1) {
            $currenciesarray = "";
        }
        return $currenciesarray;
    }
    protected function getLanguageSwitcherHTML()
    {
        $whmcs = \App::self();
        if (!Config\Setting::getValue("AllowLanguageChange")) {
            return false;
        }
        $setlanguage = "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"];
        $count = 0;
        foreach ($_GET as $k => $v) {
            $prefix = $count == 0 ? "?" : "&amp;";
            $setlanguage .= $prefix . htmlentities($k) . "=" . htmlentities($v);
            $count++;
        }
        $setlanguage .= "\" name=\"languagefrm\" id=\"languagefrm\"><strong>" . $whmcs->get_lang("language") . ":</strong> <select name=\"language\" onchange=\"languagefrm.submit()\">";
        foreach (Language\ClientLanguage::getLanguages() as $lang) {
            $setlanguage .= "<option";
            if ($lang == \Lang::getName()) {
                $setlanguage .= " selected=\"selected\"";
            }
            $setlanguage .= ">" . ucfirst($lang) . "</option>";
        }
        $setlanguage .= "</select></form>";
        return $setlanguage;
    }
    public function initPage()
    {
        global $_LANG;
        global $clientsdetails;
        $this->resetRenderedOutput();
        $this->startSmartyIfNotStarted();
        $client = null;
        $clientAlerts = array();
        $clientsdetails = array();
        $clientsstats = array();
        $loggedinuser = array();
        $contactpermissions = array();
        $emailVerificationPending = false;
        if ($this->isLoggedIn()) {
            $clientId = $this->getUserID();
            $client = User\Client::find($clientId);
            $this->client = $client;
            $legacyClient = new Client($client);
            $clientsdetails = $legacyClient->getDetails();
            if (!function_exists("getClientsDetails")) {
                require ROOTDIR . "/includes/clientfunctions.php";
            }
            $clientsstats = getClientsStats($clientId);
            if ($contactId = (int) Session::get("cid")) {
                $result = select_query("tblcontacts", "id,firstname,lastname,email,permissions", array("id" => $contactId, "userid" => $clientId));
                $data = mysql_fetch_array($result);
                $loggedinuser = array("contactid" => $data["id"], "firstname" => $data["firstname"], "lastname" => $data["lastname"], "email" => $data["email"]);
                $contactpermissions = explode(",", $data[4]);
            } else {
                $loggedinuser = array("userid" => $clientId, "firstname" => $clientsdetails["firstname"], "lastname" => $clientsdetails["lastname"], "email" => $clientsdetails["email"]);
                $contactpermissions = array("profile", "contacts", "products", "manageproducts", "domains", "managedomains", "invoices", "tickets", "affiliates", "emails", "orders");
            }
            $alerts = new User\Client\AlertFactory($client);
            $clientAlerts = $alerts->build();
            if (Config\Setting::getValue("EnableEmailVerification")) {
                $emailVerificationPending = !$client->isEmailAddressVerified();
            }
            \Menu::addContext("client", $client);
        }
        $this->assign("loggedin", $this->isLoggedIn());
        $this->assign("client", $client);
        $this->assign("clientsdetails", $clientsdetails);
        $this->assign("clientAlerts", $clientAlerts);
        $this->assign("clientsstats", $clientsstats);
        $this->assign("loggedinuser", $loggedinuser);
        $this->assign("contactpermissions", $contactpermissions);
        $this->assign("emailVerificationPending", $emailVerificationPending);
        $this->assign("phoneNumberInputStyle", (int) Config\Setting::getValue("PhoneNumberDropdown"));
    }
    public function getSingleTPLOutput($templatepath, $templateVariables = array())
    {
        global $smartyvalues;
        $this->startSmartyIfNotStarted();
        $this->registerDefaultTPLVars();
        if (is_array($smartyvalues)) {
            foreach ($smartyvalues as $key => $value) {
                $this->assign($key, $value);
            }
        }
        foreach ($this->templateVariables as $key => $value) {
            $this->smarty->assign($key, $value);
        }
        if (is_array($templateVariables)) {
            foreach ($templateVariables as $key => $value) {
                $this->smarty->assign($key, $value);
            }
        }
        if (substr($templatepath, 0, 1) == "/" || substr($templatepath, 0, 1) == "\\") {
            $templatecode = $this->smarty->fetch(ROOTDIR . $templatepath);
        } else {
            $templatecode = $this->smarty->fetch(ROOTDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . \App::getClientAreaTemplate()->getName() . DIRECTORY_SEPARATOR . $templatepath . ".tpl");
        }
        $this->smarty->clear_all_assign();
        return $templatecode;
    }
    protected function runClientAreaOutputHook($hookName)
    {
        $hookResponses = run_hook($hookName, $this->templateVariables);
        $output = "";
        foreach ($hookResponses as $response) {
            if ($response) {
                $output .= $response . "\n";
            }
        }
        return $output;
    }
    public static function getConditionalLinks()
    {
        $whmcs = \App::self();
        $calinkupdatecc = isset($_SESSION["calinkupdatecc"]) ? $_SESSION["calinkupdatecc"] : CALinkUpdateCC();
        $security = isset($_SESSION["calinkupdatesq"]) ? $_SESSION["calinkupdatesq"] : CALinkUpdateSQ();
        if (!$security) {
            $twofa = new TwoFactorAuthentication();
            if ($twofa->isActiveClients()) {
                $security = true;
            } else {
                if (\DI::make("remoteAuth")->getEnabledProviders()) {
                    $security = true;
                }
            }
        }
        return array("updatecc" => $calinkupdatecc, "updatesq" => $security, "security" => $security, "allowClientRegistration" => Config\Setting::getValue("AllowClientRegister"), "addfunds" => Config\Setting::getValue("AddFundsEnabled"), "masspay" => Config\Setting::getValue("EnableMassPay"), "affiliates" => Config\Setting::getValue("AffiliateEnabled"), "domainreg" => Config\Setting::getValue("AllowRegister"), "domaintrans" => Config\Setting::getValue("AllowTransfer"), "domainown" => Config\Setting::getValue("AllowOwnDomain"), "pmaddon" => get_query_val("tbladdonmodules", "value", array("module" => "project_management", "setting" => "clientenable")));
    }
    protected function buildBreadCrumbHtml()
    {
        $breadcrumb = array();
        foreach ($this->breadcrumb as $vals) {
            $breadcrumb[] = "<a href=\"" . $vals["link"] . "\">" . $vals["label"] . "</a>";
        }
        return implode(" > ", $breadcrumb);
    }
    public function getBreadCrumbHtml()
    {
        if ($this->breadCrumbHtml) {
            return $this->breadCrumbHtml;
        }
        return $this->buildBreadCrumbHtml();
    }
    public function setBreadCrumbHtml($breadCrumbHtml)
    {
        $this->breadCrumbHtml = $breadCrumbHtml;
        return $this;
    }
    public static function getCurrentPageLinkBack()
    {
        $currentPageLinkBack = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . "?";
        foreach ($_GET as $k => $v) {
            if (!in_array($k, array("language", "currency"))) {
                $currentPageLinkBack .= urlencode(html_entity_decode($k)) . "=" . urlencode(html_entity_decode($v)) . "&amp;";
            }
        }
        return $currentPageLinkBack;
    }
    public static function isAdminMasqueradingAsClient()
    {
        $isAdmin = Session::get("adminid");
        $userId = Session::get("uid");
        return $isAdmin && $userId;
    }
    public static function calculatePwStrengthThresholds()
    {
        $pwStrengthRequired = Config\Setting::getValue("RequiredPWStrength");
        if (is_numeric($pwStrengthRequired)) {
            $pwStrengthRequired = (int) $pwStrengthRequired;
        } else {
            $pwStrengthRequired = 50;
        }
        if ($pwStrengthRequired < 0) {
            $pwStrengthRequired = 0;
        }
        if (100 < $pwStrengthRequired) {
            $pwStrengthRequired = 100;
        }
        if (0 < $pwStrengthRequired) {
            $pwStrengthAdvised = $pwStrengthRequired + round(100 - $pwStrengthRequired) / 2;
        } else {
            $pwStrengthAdvised = 0;
        }
        return array("pwStrengthErrorThreshold" => $pwStrengthRequired, "pwStrengthWarningThreshold" => $pwStrengthAdvised);
    }
    public function outputWithoutExit()
    {
        global $whmcs;
        global $licensing;
        global $smartyvalues;
        $templateFile = $this->getTemplateFile();
        if (!$templateFile) {
            exit("Missing Template File '" . $templateFile . "'");
        }
        $this->registerDefaultTPLVars();
        $cart = new OrderForm();
        $this->assign("cartitemcount", $cart->getNumItemsInCart());
        $this->assign("breadcrumb", $this->breadcrumb);
        $this->assign("breadcrumbnav", $this->getBreadCrumbHtml());
        $this->assign("currentpagelinkback", static::getCurrentPageLinkBack());
        $this->assign("setlanguage", $this->getLanguageSwitcherHTML());
        $this->assign("currencies", $this->getCurrencyOptions());
        $this->assign("twitterusername", Config\Setting::getValue("TwitterUsername"));
        $this->assign("condlinks", self::getConditionalLinks());
        $this->assign("templatefile", $templateFile);
        $this->assign("adminLoggedIn", (bool) Session::get("adminid"));
        $this->assign("adminMasqueradingAsClient", static::isAdminMasqueradingAsClient());
        $orderFormTemplateName = isset($smartyvalues["carttpl"]) ? $smartyvalues["carttpl"] : "";
        if (!$orderFormTemplateName) {
            $orderFormTemplateName = $this->templateVariables["carttpl"];
        }
        if (is_array($smartyvalues)) {
            $smartyvalues = array_merge($smartyvalues, static::calculatePwStrengthThresholds());
            foreach ($smartyvalues as $key => $value) {
                $this->assign($key, $value);
            }
        }
        $loggedInClientFirstName = "";
        $loggedInUser = $this->templateVariables["loggedinuser"];
        if (isset($loggedInUser["firstname"])) {
            $loggedInClientFirstName = $loggedInUser["firstname"];
        }
        $conditionalLinks = static::getConditionalLinks();
        $primaryNavbar = \Menu::primaryNavbar($loggedInClientFirstName, $conditionalLinks);
        $secondaryNavbar = \Menu::secondaryNavbar($loggedInClientFirstName, $conditionalLinks);
        run_hook("ClientAreaPrimaryNavbar", $primaryNavbar);
        run_hook("ClientAreaSecondaryNavbar", $secondaryNavbar);
        run_hook("ClientAreaNavbars", null);
        $primarySidebar = \Menu::primarySidebar();
        $secondarySidebar = \Menu::secondarySidebar();
        run_hook("ClientAreaPrimarySidebar", array($primarySidebar), true);
        run_hook("ClientAreaSecondarySidebar", array($secondarySidebar), true);
        run_hook("ClientAreaSidebars", null);
        $this->assign("primaryNavbar", View\Menu\Item::sort($primaryNavbar));
        $this->assign("secondaryNavbar", View\Menu\Item::sort($secondaryNavbar));
        $this->assign("primarySidebar", View\Menu\Item::sort($primarySidebar));
        $this->assign("secondarySidebar", View\Menu\Item::sort($secondarySidebar));
        if (empty($this->templateVariables["displayTitle"])) {
            $this->templateVariables["displayTitle"] = $this->templateVariables["pagetitle"];
        }
        foreach ($this->templateVariables as $key => $value) {
            $this->smarty->assign($key, $value);
        }
        if (isset($GLOBALS["pagelimit"])) {
            $smartyvalues["itemlimit"] = $GLOBALS["pagelimit"];
        }
        if (isset($this->templateVariables["loginpage"]) && $this->templateVariables["loginpage"] === true) {
            $pageLoginVariables = run_hook("ClientAreaPageLogin", $this->templateVariables);
            foreach ($pageLoginVariables as $loginVariables) {
                foreach ($loginVariables as $key => $value) {
                    $this->assign($key, $value);
                }
            }
        }
        $sidebarsToCleanup = array($this->templateVariables["primarySidebar"], $this->templateVariables["secondarySidebar"]);
        foreach ($sidebarsToCleanup as $sidebar) {
            if ($sidebar && $sidebar instanceof View\Menu\Item) {
                \Menu::removeEmptyChildren($sidebar);
            }
        }
        $this->runOutputHooks()->assign("headoutput", $this->runClientAreaOutputHook("ClientAreaHeadOutput"))->assign("headeroutput", $this->runClientAreaOutputHook("ClientAreaHeaderOutput"));
        $footerOutput = $this->runClientAreaOutputHook("ClientAreaFooterOutput");
        if (array_key_exists("credit_card_input", $this->templateVariables) && $this->templateVariables["credit_card_input"]) {
            $footerOutput .= $this->templateVariables["credit_card_input"];
            $this->smarty->clearAssign("credit_card_input");
        }
        $this->assign("footeroutput", $footerOutput);
        $licenseBannerHtml = $this->getLicenseBannerHtml();
        $activeTemplate = $whmcs->getClientAreaTemplate()->getName();
        if ($this->isWrappedWithHeaderFooter()) {
            $header_file = $this->smarty->fetch($activeTemplate . "/header.tpl");
            $footer_file = $this->smarty->fetch($activeTemplate . "/footer.tpl");
        }
        if ($this->inorderform) {
            try {
                $body_file = $this->smarty->fetch(ROOTDIR . "/templates/orderforms/" . View\Template\OrderForm::factory($templateFile . ".tpl", $orderFormTemplateName)->getName() . "/" . $templateFile . ".tpl");
            } catch (Exception\View\TemplateNotFound $e) {
                logActivity("Unable to load the " . $templateFile . ".tpl file from the " . $orderFormTemplateName . " order form template or any of its parents.");
                $body_file = "<p>" . \Lang::trans("unableToLoadShoppingCart") . "</p>";
            }
        } else {
            if ($this->insupportmodule) {
                $body_file = $this->smarty->fetch(ROOTDIR . "/templates/" . Config\Setting::getValue("SupportModule") . "/" . $templateFile . ".tpl");
            } else {
                if (substr($templateFile, 0, 1) == "/" || substr($templateFile, 0, 1) == "\\") {
                    $body_file = $this->smarty->fetch(ROOTDIR . $templateFile);
                } else {
                    $body_file = $this->smarty->fetch(ROOTDIR . "/templates/" . $activeTemplate . "/" . $templateFile . ".tpl");
                }
            }
        }
        $this->smarty->clearAllAssign();
        $copyrighttext = $licensing->getBrandingRemoval() ? "" : "<p style=\"text-align:center;\">Powered by <a href=\"https://www.whmcs.com/\" target=\"_blank\">WHMCompleteSolution</a></p>";
        if (isset($_SESSION["adminid"])) {
            $adminloginlink = "<div style=\"position:absolute;top:0px;right:0px;padding:5px;background-color:#000066;font-family:Tahoma;font-size:11px;color:#ffffff\" class=\"adminreturndiv\">Logged in as Administrator | <a href=\"" . $whmcs->get_admin_folder_name() . "/";
            if (isset($_SESSION["uid"])) {
                $adminloginlink .= "clientssummary.php?userid=" . $_SESSION["uid"] . "&return=1";
            }
            $adminloginlink .= "\" style=\"color:#6699ff\">Return to Admin Area</a></div>\n\n    ";
        } else {
            $adminloginlink = "";
        }
        if (!$this->isWrappedWithHeaderFooter()) {
            $template_output = $body_file;
        } else {
            $template_output = $header_file . PHP_EOL . $licenseBannerHtml . PHP_EOL . $body_file . PHP_EOL . $copyrighttext . PHP_EOL . $adminloginlink . PHP_EOL . $footer_file;
        }
        if (!in_array($templateFile, array("3dsecure", "forwardpage", "viewinvoice"))) {
            $template_output = preg_replace("/(<form\\W[^>]*\\bmethod=('|\"|)POST('|\"|)\\b[^>]*>)/i", "\\1" . "\n" . generate_token(), $template_output);
            if ($this instanceof View\LinkDecoratorInterface) {
                $template_output = $this->decorateLinksInText($template_output);
            }
            $template_output = View\Asset::conditionalFontawesomeCssInclude($template_output);
        }
        echo $template_output;
        if (defined("PERFORMANCE_DEBUG")) {
            global $query_count;
            $exectime = microtime() - PERFORMANCE_STARTTIME;
            echo "<p>Performance Debug: " . $exectime . " Queries: " . $query_count . "</p>";
        }
    }
    public function output()
    {
        $this->outputWithoutExit();
        exit;
    }
    public function getOutputContent()
    {
        if (!$this->renderedOutput) {
            ob_start();
            $this->initPage();
            $this->outputWithoutExit();
            $this->renderedOutput = ob_get_clean();
        }
        return $this->renderedOutput;
    }
    public function getLicenseBannerMessage()
    {
        return \DI::make("license")->getBanner();
    }
    public function getLicenseBannerHtml()
    {
        $licenseBannerMsg = $this->getLicenseBannerMessage();
        return $licenseBannerMsg ? "<div style=\"margin:0 0 10px 0;padding:10px 35px;background-color:#ffffd2;color:#555;font-size:16px;text-align:center;\">" . $licenseBannerMsg . "</div>" : "";
    }
    public function disableHeaderFooterOutput()
    {
        $this->wrappedWithHeaderFooter = false;
        return $this;
    }
    public function isWrappedWithHeaderFooter()
    {
        return $this->wrappedWithHeaderFooter;
    }
    public function addOutputHookFunction($name)
    {
        $this->outputHooks[] = $name;
        return $this;
    }
    protected function runOutputHooks()
    {
        $hookParameters = $this->templateVariables;
        unset($hookParameters["LANG"]);
        foreach ($this->outputHooks as $hookFunction) {
            $hookResponses = run_hook($hookFunction, $hookParameters);
            foreach ($hookResponses as $hookTemplateVariables) {
                foreach ($hookTemplateVariables as $k => $v) {
                    $this->assign($k, $v);
                    $hookParameters[$k] = $v;
                }
            }
        }
        return $this;
    }
    public function skipMainBodyContainer()
    {
        $this->skipMainBodyContainer = true;
    }
}

?>