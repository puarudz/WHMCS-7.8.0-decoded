<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ClientArea;

class ClientAreaController
{
    public function homePage(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $query = $request->getQueryParams();
        if (!empty($query["rp"]) && strpos($query["rp"], "/detect-route-environment") !== false) {
            $controller = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController(\WHMCS\Admin\Setup\General\UriManagement\ConfigurationController::PATH_COMPARISON_INDEX);
            return $controller->detectRouteEnvironment($request);
        }
        if (\WHMCS\Config\Setting::getValue("DefaultToClientArea")) {
            return new \Zend\Diactoros\Response\RedirectResponse("clientarea.php");
        }
        if (!function_exists("ticketsummary")) {
            include_once ROOTDIR . "/includes/ticketfunctions.php";
        }
        $view = new \WHMCS\ClientArea();
        $view->setTemplate("homepage");
        $view->addOutputHookFunction("ClientAreaPageHome");
        $view->setPageTitle(\Lang::trans("globalsystemname"));
        $view->setDisplayTitle(\Lang::trans("globalsystemname"));
        $view->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $data = array();
        $data["announcements"] = $this->getAnnouncements();
        $routeSetting = \WHMCS\Config\Setting::getValue("RouteUriPathMode");
        $seoSetting = $routeSetting == \WHMCS\Route\UriPath::MODE_REWRITE ? 1 : 0;
        $data["seofriendlyurls"] = $seoSetting;
        if (\WHMCS\Config\Setting::getValue("AllowRegister")) {
            $data["registerdomainenabled"] = true;
        }
        if (\WHMCS\Config\Setting::getValue("AllowTransfer")) {
            $data["transferdomainenabled"] = true;
        }
        if (\WHMCS\Config\Setting::getValue("AllowOwnDomain")) {
            $data["owndomainenabled"] = true;
        }
        $captcha = new \WHMCS\Utility\Captcha();
        $data["captcha"] = $captcha;
        $data["captchaForm"] = \WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER;
        $data["recaptchahtml"] = clientAreaReCaptchaHTML();
        $data["capatacha"] = $captcha;
        $data["recapatchahtml"] = clientAreaReCaptchaHTML();
        $view->setTemplateVariables($data);
        return $view;
    }
    public function loginWithRedirect(\Psr\Http\Message\ServerRequestInterface $request)
    {
        \WHMCS\Session::set("loginurlredirect", html_entity_decode($request->getServerParams()["REQUEST_URI"]));
        $whmcs = \App::self();
        $data["showingLoginPage"] = true;
        if (\WHMCS\Session::get("2faverifyc")) {
            $templatefile = "logintwofa";
            if (\WHMCS\Session::get("2fabackupcodenew")) {
                $data["newbackupcode"] = true;
            } else {
                if ($whmcs->get_req_var("incorrect")) {
                    $data["incorrect"] = true;
                }
            }
            $twofa = new \WHMCS\TwoFactorAuthentication();
            if ($twofa->setClientID(\WHMCS\Session::get("2faclientid"))) {
                if (!$twofa->isActiveClients() || !$twofa->isEnabled()) {
                    \WHMCS\Session::destroy();
                    redir();
                }
                if ($whmcs->get_req_var("backupcode")) {
                    $data["backupcode"] = true;
                } else {
                    $challenge = $twofa->moduleCall("challenge");
                    if ($challenge) {
                        $data["challenge"] = $challenge;
                    } else {
                        $data["error"] = "Bad 2 Factor Auth Module. Please contact support.";
                    }
                }
            } else {
                $data["error"] = "An error occurred. Please try again.";
            }
        } else {
            $remoteAuthData = (new \WHMCS\Authentication\Remote\Management\Client\ViewHelper())->getTemplateData(\WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_LOGIN);
            foreach ($remoteAuthData as $key => $value) {
                $data[$key] = $value;
            }
            $templatefile = "login";
            $data["loginpage"] = true;
            $data["formaction"] = "dologin.php";
            $data["incorrect"] = (bool) $whmcs->get_req_var("incorrect");
            $data["ssoredirect"] = (bool) $whmcs->get_req_var("ssoredirect");
            $captcha = new \WHMCS\Utility\Captcha();
            $data["captcha"] = $captcha;
            $data["captchaForm"] = \WHMCS\Utility\Captcha::FORM_LOGIN;
            $data["invalid"] = \WHMCS\Session::getAndDelete("CaptchaError");
        }
        $view = new \WHMCS\ClientArea();
        $view->setTemplate($templatefile);
        $view->setPageTitle(\Lang::trans("login"));
        $view->setTemplateVariables($data);
        $view->addOutputHookFunction("ClientAreaPageLogin");
        return $view;
    }
    protected function getAnnouncements()
    {
        $activeLanguage = \WHMCS\Session::get("Language");
        if (!$activeLanguage) {
            $activeLanguage = \WHMCS\Config\Setting::getValue("Language");
        }
        $announcements = array();
        $result = select_query("tblannouncements", "", array("published" => "1"), "date", "DESC", "0,3");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $date = $data["date"];
            $title = $data["title"];
            $announcement = $data["announcement"];
            if ($activeLanguage) {
                $result2 = select_query("tblannouncements", "", array("parentid" => $id, "language" => $activeLanguage));
                $data = mysql_fetch_array($result2);
                if ($data["title"]) {
                    $title = $data["title"];
                }
                if ($data["announcement"]) {
                    $announcement = $data["announcement"];
                }
            }
            $formattedDate = fromMySQLDate($date, "", true);
            $announcements[] = array("id" => $id, "date" => $formattedDate, "rawDate" => $date, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "summary" => ticketsummary(strip_tags($announcement), 350), "text" => $announcement);
        }
        return $announcements;
    }
    public function sslPurchase(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (\WHMCS\MarketConnect\MarketConnect::isActive("symantec")) {
            \App::redirectToRoutePath("store-ssl-certificates-index");
        }
        \App::redirect("cart.php");
    }
}

?>