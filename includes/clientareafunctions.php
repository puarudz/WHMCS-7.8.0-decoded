<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function initialiseLoggedInClient()
{
    global $smarty;
    global $clientsdetails;
    $client = NULL;
    $clientAlerts = array();
    $clientsdetails = array();
    $clientsstats = array();
    $loggedinuser = array();
    $contactpermissions = array();
    $emailVerificationPending = false;
    $clientId = (int) WHMCS\Session::get("uid");
    if ($clientId) {
        $client = WHMCS\User\Client::find($clientId);
        $legacyClient = new WHMCS\Client($client);
        $clientsdetails = $legacyClient->getDetails();
        if (!function_exists("getClientsStats")) {
            require ROOTDIR . "/includes/clientfunctions.php";
        }
        $clientsstats = getClientsStats($clientId);
        $contactid = (int) WHMCS\Session::get("cid");
        if ($contactid) {
            $contactdata = WHMCS\User\Client\Contact::where("id", $contactid)->where("userid", $clientId)->first();
            if ($contactdata) {
                $loggedinuser = array("contactid" => $contactdata->getAttribute("id"), "firstname" => $contactdata->getAttribute("firstname"), "lastname" => $contactdata->getAttribute("lastname"), "email" => $contactdata->getAttribute("email"));
                $contactpermissions = explode(",", $contactdata["permissions"]);
            }
        } else {
            $loggedinuser = array("userid" => $clientId, "firstname" => $clientsdetails["firstname"], "lastname" => $clientsdetails["lastname"], "email" => $clientsdetails["email"]);
            $contactpermissions = array("profile", "contacts", "products", "manageproducts", "domains", "managedomains", "invoices", "tickets", "affiliates", "emails", "orders");
        }
        $alerts = new WHMCS\User\Client\AlertFactory($client);
        $clientAlerts = $alerts->build();
        if (WHMCS\Config\Setting::getValue("EnableEmailVerification")) {
            $emailVerificationPending = !$client->isEmailAddressVerified();
        }
    }
    $smarty->assign("loggedin", (bool) $clientId);
    $smarty->assign("client", $client);
    $smarty->assign("clientsdetails", $clientsdetails);
    $smarty->assign("clientAlerts", $clientAlerts);
    $smarty->assign("clientsstats", $clientsstats);
    $smarty->assign("loggedinuser", $loggedinuser);
    $smarty->assign("contactpermissions", $contactpermissions);
    $smarty->assign("emailVerificationPending", $emailVerificationPending);
    return $client;
}
function initialiseClientArea($pageTitle, $displayTitle, $tagline, $pageIcon = NULL, $breadcrumb = NULL, $smartyValues = array())
{
    global $_LANG;
    global $smarty;
    global $smartyvalues;
    if ($smartyValues) {
        $smartyvalues = array_merge($smartyvalues, $smartyValues);
    }
    if (defined("PERFORMANCE_DEBUG")) {
        define("PERFORMANCE_STARTTIME", microtime());
    }
    if (is_null($pageIcon) && is_null($breadcrumb)) {
        $pageIcon = $displayTitle;
        $displayTitle = $pageTitle;
        $breadcrumb = $tagline;
        $tagline = "";
    }
    $whmcs = App::self();
    $filename = $whmcs->getCurrentFilename();
    $smarty = new WHMCS\Smarty();
    $emptyTemplateParameters = array("displayTitle", "tagline", "type", "textcenter", "hide", "additionalClasses", "idname", "errorshtml", "title", "msg", "desc", "errormessage", "livehelpjs");
    foreach ($emptyTemplateParameters as $templateParam) {
        $smarty->assign($templateParam, "");
    }
    $setlanguage = "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"];
    $count = 0;
    foreach ($_GET as $k => $v) {
        $prefix = $count == 0 ? "?" : "&amp;";
        $setlanguage .= $prefix . htmlentities($k) . "=" . htmlentities($v);
        $count++;
    }
    $setlanguage .= "\" name=\"languagefrm\" id=\"languagefrm\"><strong>" . $_LANG["language"] . ":</strong> <select name=\"language\" onchange=\"languagefrm.submit()\">";
    foreach (WHMCS\Language\ClientLanguage::getLanguages() as $lang) {
        $setlanguage .= "<option";
        if ($lang == Lang::getName()) {
            $setlanguage .= " selected=\"selected\"";
        }
        $setlanguage .= ">" . ucfirst($lang) . "</option>";
    }
    $setlanguage .= "</select></form>";
    $smarty->assign("setlanguage", $setlanguage);
    $smarty->assign("languages", Lang::getLanguages());
    $locales = Lang::getLocales();
    $smarty->assign("locales", $locales);
    $activeLocale = NULL;
    foreach ($locales as $locale) {
        if ($locale["language"] == Lang::getName()) {
            $activeLocale = $locale;
            break;
        }
    }
    $smarty->assign("activeLocale", $activeLocale);
    $carbonObject = new WHMCS\Carbon();
    $carbonObject->setLocale($activeLocale["languageCode"]);
    $smarty->assign("carbon", $carbonObject);
    $smarty->assign("showbreadcrumb", false);
    $smarty->assign("showingLoginPage", false);
    $smarty->assign("incorrect", false);
    $smarty->assign("kbarticle", array("title" => ""));
    $smarty->assign("template", $whmcs->getClientAreaTemplate()->getName());
    $smarty->assign("language", Lang::getName());
    $smarty->assign("LANG", $_LANG);
    $smarty->assign("companyname", WHMCS\Config\Setting::getValue("CompanyName"));
    $smarty->assign("logo", WHMCS\Config\Setting::getValue("LogoURL"));
    $smarty->assign("charset", WHMCS\Config\Setting::getValue("Charset"));
    $smarty->assign("pagetitle", $pageTitle);
    $smarty->assign("displayTitle", $displayTitle);
    $smarty->assign("tagline", $tagline);
    $smarty->assign("pageicon", $pageIcon);
    $smarty->assign("filename", $filename);
    $smarty->assign("breadcrumb", breakBreadcrumbHTMLIntoParts($breadcrumb));
    $smarty->assign("breadcrumbnav", $breadcrumb);
    $smarty->assign("todaysdate", $carbonObject->format("l, jS F Y"));
    $smarty->assign("date_day", $carbonObject->format("d"));
    $smarty->assign("date_month", $carbonObject->format("m"));
    $smarty->assign("date_year", $carbonObject->format("Y"));
    $smarty->assign("token", generate_token("plain"));
    $smarty->assign("reCaptchaPublicKey", WHMCS\Config\Setting::getValue("ReCAPTCHAPublicKey"));
    $smarty->assign("servedOverSsl", $whmcs->in_ssl());
    $smarty->assign("versionHash", WHMCS\View\Helper::getAssetVersionHash());
    $smarty->assign("systemurl", $whmcs->getSystemURL());
    $smarty->assign("systemsslurl", $whmcs->getSystemURL());
    $smarty->assign("systemNonSSLURL", $whmcs->getSystemURL());
    $assetHelper = DI::make("asset");
    $smarty->assign("WEB_ROOT", $assetHelper->getWebRoot());
    $smarty->assign("BASE_PATH_CSS", $assetHelper->getCssPath());
    $smarty->assign("BASE_PATH_JS", $assetHelper->getJsPath());
    $smarty->assign("BASE_PATH_FONTS", $assetHelper->getFontsPath());
    $smarty->assign("BASE_PATH_IMG", $assetHelper->getImgPath());
    if (file_exists(ROOTDIR . "/assets/img/logo.png")) {
        $assetLogoPath = $assetHelper->getImgPath() . "/logo.png";
    } else {
        if (file_exists(ROOTDIR . "/assets/img/logo.jpg")) {
            $assetLogoPath = $assetHelper->getImgPath() . "/logo.jpg";
        } else {
            $assetLogoPath = "";
        }
    }
    $smarty->assign("assetLogoPath", $assetLogoPath);
    $client = initialiseloggedinclient();
    $langChangeEnabled = WHMCS\Config\Setting::getValue("AllowLanguageChange") ? true : false;
    $smarty->assign("langchange", $langChangeEnabled);
    $smarty->assign("languagechangeenabled", $langChangeEnabled);
    $smarty->assign("currentpagelinkback", WHMCS\ClientArea::getCurrentPageLinkBack());
    $currenciesarray = array();
    $result = select_query("tblcurrencies", "id,code,`default`", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $currenciesarray[] = array("id" => $data["id"], "code" => $data["code"], "default" => $data["default"]);
    }
    if (count($currenciesarray) == 1) {
        $currenciesarray = "";
    }
    $smarty->assign("currencies", $currenciesarray);
    $smarty->assign("twitterusername", WHMCS\Config\Setting::getValue("TwitterUsername"));
    $smarty->assign("announcementsFbRecommend", WHMCS\Config\Setting::getValue("AnnouncementsFBRecommend"));
    $smarty->assign("condlinks", WHMCS\ClientArea::getConditionalLinks());
    Menu::addContext("client", $client);
    Menu::addContext("currencies", $currenciesarray);
    Menu::addContext("carbon", $carbonObject);
    $smartyvalues = array();
}
function outputClientArea($templatefile, $nowrapper = false, $hookFunctions = array(), $smartyValues = array())
{
    global $CONFIG;
    global $smarty;
    global $smartyvalues;
    global $orderform;
    global $usingsupportmodule;
    if (!empty($smartyValues)) {
        $smartyvalues = $smartyValues;
    }
    $whmcs = App::self();
    $licensing = DI::make("license");
    if (!$templatefile) {
        exit("Invalid Entity Requested");
    }
    if ($licensing->getBrandingRemoval()) {
        $copyrighttext = "";
    } else {
        $copyrighttext = "<p style=\"text-align:center;\">Powered by <a href=\"https://www.whmcs.com/\" target=\"_blank\">WHMCompleteSolution</a></p>";
    }
    if (isset($_SESSION["adminid"])) {
        $adminloginlink = "<div style=\"position:absolute;top:0px;right:0px;padding:5px;background-color:#000066;font-family:Tahoma;font-size:11px;color:#ffffff\" class=\"adminreturndiv\">Logged in as Administrator | <a href=\"" . $whmcs->get_admin_folder_name() . "/";
        if (isset($_SESSION["uid"])) {
            $adminloginlink .= "clientssummary.php?userid=" . $_SESSION["uid"] . "&return=1";
        }
        $adminloginlink .= "\" style=\"color:#6699ff\">Return to Admin Area</a></div>\n\n";
    } else {
        $adminloginlink = "";
    }
    $loggedInClientFirstName = "";
    $loggedInUser = $smarty->tpl_vars["loggedinuser"]->value;
    if (isset($loggedInUser["firstname"])) {
        $loggedInClientFirstName = $loggedInUser["firstname"];
    }
    $conditionalLinks = WHMCS\ClientArea::getConditionalLinks();
    $primaryNavbar = Menu::primaryNavbar($loggedInClientFirstName, $conditionalLinks);
    $secondaryNavbar = Menu::secondaryNavbar($loggedInClientFirstName, $conditionalLinks);
    run_hook("ClientAreaPrimaryNavbar", $primaryNavbar);
    run_hook("ClientAreaSecondaryNavbar", $secondaryNavbar);
    run_hook("ClientAreaNavbars", NULL);
    $primarySidebar = Menu::primarySidebar();
    $secondarySidebar = Menu::secondarySidebar();
    run_hook("ClientAreaPrimarySidebar", array($primarySidebar), true);
    run_hook("ClientAreaSecondarySidebar", array($secondarySidebar), true);
    run_hook("ClientAreaSidebars", NULL);
    $smarty->assign("primaryNavbar", WHMCS\View\Menu\Item::sort($primaryNavbar));
    $smarty->assign("secondaryNavbar", WHMCS\View\Menu\Item::sort($secondaryNavbar));
    $smarty->assign("primarySidebar", WHMCS\View\Menu\Item::sort($primarySidebar));
    $smarty->assign("secondarySidebar", WHMCS\View\Menu\Item::sort($secondarySidebar));
    if (isset($GLOBALS["pagelimit"])) {
        $smartyvalues["itemlimit"] = $GLOBALS["pagelimit"];
    }
    $cart = new WHMCS\OrderForm();
    $orderFormTemplateName = isset($smartyvalues["carttpl"]) ? $smartyvalues["carttpl"] : "";
    $smartyvalues["cartitemcount"] = $cart->getNumItemsInCart();
    $smartyvalues["templatefile"] = $templatefile;
    $smartyvalues["adminLoggedIn"] = (bool) WHMCS\Session::get("adminid");
    $smartyvalues["adminMasqueradingAsClient"] = WHMCS\ClientArea::isAdminMasqueradingAsClient();
    if ($smartyvalues) {
        $smartyvalues = array_merge($smartyvalues, WHMCS\ClientArea::calculatePwStrengthThresholds());
        foreach ($smartyvalues as $key => $value) {
            $smarty->assign($key, $value);
        }
    }
    $hookParameters = $smarty->getTemplateVars();
    unset($hookParameters["LANG"]);
    $hookFunctions = array_merge(array("ClientAreaPage"), $hookFunctions);
    foreach ($hookFunctions as $hookFunction) {
        $hookResponses = run_hook($hookFunction, $hookParameters);
        foreach ($hookResponses as $hookTemplateVariables) {
            foreach ($hookTemplateVariables as $k => $v) {
                $hookParameters[$k] = $v;
                if (isset($smartyvalues[$k])) {
                    $smartyvalues[$k] = $v;
                }
                $smarty->assign($k, $v);
            }
        }
    }
    $sidebarVarsToCleanup = array($smarty->tpl_vars["primarySidebar"], $smarty->tpl_vars["secondarySidebar"]);
    foreach ($sidebarVarsToCleanup as $var) {
        if ($var && $var->value instanceof WHMCS\View\Menu\Item) {
            Menu::removeEmptyChildren($var->value);
        }
    }
    $hookResponses = run_hook("ClientAreaHeadOutput", $hookParameters);
    $headOutput = "";
    foreach ($hookResponses as $response) {
        if ($response) {
            $headOutput .= $response . "\n";
        }
    }
    $smarty->assign("headoutput", $headOutput);
    $hookResponses = run_hook("ClientAreaHeaderOutput", $hookParameters);
    $headerOutput = "";
    foreach ($hookResponses as $response) {
        if ($response) {
            $headerOutput .= $response . "\n";
        }
    }
    $smarty->assign("headeroutput", $headerOutput);
    $hookResponses = run_hook("ClientAreaFooterOutput", $hookParameters);
    $footerOutput = "";
    foreach ($hookResponses as $response) {
        if ($response) {
            $footerOutput .= $response . "\n";
        }
    }
    if (array_key_exists("credit_card_input", $smartyvalues) && $smartyvalues["credit_card_input"]) {
        $footerOutput .= $smartyvalues["credit_card_input"];
        $smarty->clearAssign("credit_card_input");
    }
    $smarty->assign("footeroutput", $footerOutput);
    $activeTemplate = $whmcs->getClientAreaTemplate()->getName();
    if (!$nowrapper) {
        $header_file = $smarty->fetch($activeTemplate . "/header.tpl");
        $footer_file = $smarty->fetch($activeTemplate . "/footer.tpl");
    }
    $clientArea = new WHMCS\ClientArea();
    $licenseBannerHtml = $clientArea->getLicenseBannerHtml();
    $clientAreaTemplatePath = ROOTDIR . "/templates/" . $activeTemplate . "/" . $templatefile . ".tpl";
    if ($orderform) {
        try {
            $body_file = $smarty->fetch(ROOTDIR . "/templates/orderforms/" . WHMCS\View\Template\OrderForm::factory($templatefile . ".tpl", $orderFormTemplateName)->getName() . "/" . $templatefile . ".tpl");
        } catch (WHMCS\Exception\View\TemplateNotFound $e) {
            if ($templatefile == "login") {
                $body_file = $smarty->fetch($clientAreaTemplatePath);
            } else {
                logActivity("Unable to load the " . $templatefile . ".tpl file from the " . $orderFormTemplateName . " order form template or any of its parents.");
                $body_file = "<p>" . Lang::trans("unableToLoadShoppingCart") . "</p>";
            }
        }
    } else {
        if ($usingsupportmodule) {
            $body_file = $smarty->fetch(ROOTDIR . "/templates/" . $CONFIG["SupportModule"] . "/" . $templatefile . ".tpl");
        } else {
            if (substr($templatefile, 0, 1) == "/" || substr($templatefile, 0, 1) == "\\") {
                $body_file = $smarty->fetch(ROOTDIR . $templatefile);
            } else {
                $body_file = $smarty->fetch($clientAreaTemplatePath);
            }
        }
    }
    if ($nowrapper) {
        $template_output = $body_file;
    } else {
        $template_output = $header_file . PHP_EOL . $licenseBannerHtml . PHP_EOL . $body_file . PHP_EOL . $copyrighttext . PHP_EOL . $adminloginlink . PHP_EOL . $footer_file;
    }
    if (!in_array($templatefile, array("3dsecure", "forwardpage", "viewinvoice"))) {
        $template_output = preg_replace("/(<form\\W[^>]*\\bmethod=('|\"|)POST('|\"|)\\b[^>]*>)/i", "\\1" . "\n" . generate_token(), $template_output);
        $template_output = WHMCS\View\Asset::conditionalFontawesomeCssInclude($template_output);
    }
    echo $template_output;
    if (defined("PERFORMANCE_DEBUG")) {
        global $query_count;
        $exectime = microtime() - PERFORMANCE_STARTTIME;
        echo "<p>Performance Debug: " . $exectime . " Queries: " . $query_count . "</p>";
    }
}
function processSingleTemplate($templatepath, $templatevars)
{
    global $smarty;
    global $smartyvalues;
    if ($smartyvalues) {
        foreach ($smartyvalues as $key => $value) {
            $smarty->assign($key, $value);
        }
    }
    foreach ($templatevars as $key => $value) {
        $smarty->assign($key, $value);
    }
    $templatecode = $smarty->fetch(ROOTDIR . $templatepath);
    return $templatecode;
}
function processSingleSmartyTemplate($smarty, $templatepath, $values)
{
    foreach ($values as $key => $value) {
        $smarty->assign($key, $value);
    }
    $templatecode = $smarty->fetch(ROOTDIR . $templatepath);
    return $templatecode;
}
function CALinkUpdateCC()
{
    $result = select_query("tblpaymentgateways", "gateway", array("setting" => "type", "value" => "CC"));
    while ($data = mysql_fetch_array($result)) {
        $gateway = $data["gateway"];
        if (!isValidforPath($gateway)) {
            exit("Invalid Gateway Module Name");
        }
        if (file_exists(ROOTDIR . "/modules/gateways/" . $gateway . ".php")) {
            require_once ROOTDIR . "/modules/gateways/" . $gateway . ".php";
        }
        if (function_exists($gateway . "_remoteupdate")) {
            $_SESSION["calinkupdatecc"] = 1;
            return true;
        }
    }
    if (!WHMCS\Config\Setting::getValue("CCNeverStore")) {
        $result = select_query("tblpaymentgateways", "COUNT(*)", "setting='type' AND (value='CC' OR value='OfflineCC')");
        $data = mysql_fetch_array($result);
        if ($data[0]) {
            $_SESSION["calinkupdatecc"] = 1;
            return true;
        }
    }
    $_SESSION["calinkupdatecc"] = 0;
    return false;
}
function CALinkUpdateSQ()
{
    $get_sq_count = get_query_val("tbladminsecurityquestions", "COUNT(id)", "");
    if (0 < $get_sq_count) {
        $_SESSION["calinkupdatesq"] = 1;
        return true;
    }
    if (1 <= WHMCS\ApplicationLink\ApplicationLink::whereIsEnabled(1)->count()) {
        $_SESSION["calinkupdatesq"] = 1;
        return true;
    }
    $_SESSION["calinkupdatesq"] = 0;
    return false;
}
function clientAreaTableInit($name, $defaultorderby, $defaultsort, $numitems)
{
    $whmcs = App::self();
    $requestedLimit = $whmcs->get_req_var("itemlimit");
    $orderby = $whmcs->get_req_var("orderby");
    $page = (int) $whmcs->get_req_var("page");
    $useServerSidePagination = true;
    $template = $whmcs->getClientAreaTemplate();
    if (!is_null($template)) {
        $properties = $template->getConfig()->getProperties();
        $useServerSidePagination = isset($properties["serverSidePagination"]) ? (bool) $properties["serverSidePagination"] : true;
    }
    $limitToApply = 10;
    if (!$useServerSidePagination) {
        $limitToApply = -1;
    } else {
        if (strtolower($requestedLimit) == "all") {
            WHMCS\Cookie::set("ItemsPerPage", -1);
            $limitToApply = -1;
        } else {
            if (is_numeric($requestedLimit)) {
                WHMCS\Cookie::set("ItemsPerPage", $requestedLimit);
                $limitToApply = $requestedLimit;
            } else {
                if (is_numeric($cookieStoredLimit = WHMCS\Cookie::get("ItemsPerPage"))) {
                    $limitToApply = $cookieStoredLimit;
                }
            }
        }
    }
    $GLOBALS["pagelimit"] = $limitToApply;
    if ($page < 1 || $numitems < ($page - 1) * $limitToApply || $limitToApply < 0) {
        $page = 1;
    }
    $GLOBALS["page"] = $page;
    if (!isset($_SESSION["ca" . $name . "orderby"])) {
        $_SESSION["ca" . $name . "orderby"] = $defaultorderby;
    }
    if (!isset($_SESSION["ca" . $name . "sort"])) {
        $_SESSION["ca" . $name . "sort"] = $defaultsort;
    }
    if ($_SESSION["ca" . $name . "orderby"] == $orderby) {
        if ($_SESSION["ca" . $name . "sort"] == "ASC") {
            $_SESSION["ca" . $name . "sort"] = "DESC";
        } else {
            $_SESSION["ca" . $name . "sort"] = "ASC";
        }
    }
    if ($orderby) {
        $_SESSION["ca" . $name . "orderby"] = $_REQUEST["orderby"];
    }
    $orderby = preg_replace("/[^a-z0-9]/", "", $_SESSION["ca" . $name . "orderby"]);
    $sort = $_SESSION["ca" . $name . "sort"];
    if (!in_array($sort, array("ASC", "DESC"))) {
        $sort = "ASC";
    }
    if ($useServerSidePagination && 0 < $limitToApply) {
        $limit = ($page - 1) * $limitToApply . "," . $limitToApply;
    } else {
        $limit = "";
    }
    return array($orderby, $sort, $limit);
}
function clientAreaTablePageNav($numitems)
{
    $numitems = (int) $numitems;
    $pagenumber = (int) $GLOBALS["page"];
    $pagelimit = (int) $GLOBALS["pagelimit"];
    if (0 < $pagelimit) {
        $totalpages = ceil($numitems / $pagelimit);
    } else {
        $totalpages = 1;
    }
    $prevpage = $pagenumber != 1 ? $pagenumber - 1 : "";
    $nextpage = $pagenumber != $totalpages && $numitems ? $pagenumber + 1 : "";
    if (!$totalpages) {
        $totalpages = 1;
    }
    return array("numitems" => $numitems, "numproducts" => $numitems, "pagenumber" => $pagenumber, "itemsperpage" => $pagelimit, "itemlimit" => 0 < $pagelimit ? $pagelimit : "99999999", "totalpages" => $totalpages, "prevpage" => $prevpage, "nextpage" => $nextpage);
}
function clientAreaInitCaptcha()
{
    $captcha = "";
    if (WHMCS\Config\Setting::getValue("CaptchaSetting") == "on" || WHMCS\Config\Setting::getValue("CaptchaSetting") == "offloggedin" && !WHMCS\Session::get("uid")) {
        if (in_array(WHMCS\Config\Setting::getValue("CaptchaType"), array("recaptcha", "invisible"))) {
            require ROOTDIR . "/includes/recaptchalib.php";
            $captcha = WHMCS\Config\Setting::getValue("CaptchaType");
        } else {
            $captcha = "default";
        }
    }
    $GLOBALS["captcha"] = $captcha;
    $GLOBALS["capatacha"] = $captcha;
    return $captcha;
}
function clientAreaReCaptchaHTML()
{
    if ($GLOBALS["captcha"] != "recaptcha") {
        return "";
    }
    if (!function_exists("recaptcha_get_html")) {
        App::load_function("recaptchalib");
    }
    $publickey = WHMCS\Config\Setting::getValue("ReCAPTCHAPublicKey");
    return recaptcha_get_html($publickey);
}
function breakBreadcrumbHTMLIntoParts($breadcrumbHTML)
{
    $breadcrumb = array();
    $parts = explode(" > ", $breadcrumbHTML);
    foreach ($parts as $part) {
        $parts2 = explode("\">", $part, 2);
        $link = str_replace("<a href=\"", "", $parts2[0]);
        $breadcrumb[] = array("link" => $link, "label" => strip_tags($parts2[1]));
    }
    return $breadcrumb;
}

?>