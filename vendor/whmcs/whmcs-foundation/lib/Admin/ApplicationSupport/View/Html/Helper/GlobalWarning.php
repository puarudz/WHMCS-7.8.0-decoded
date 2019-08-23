<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html\Helper;

class GlobalWarning
{
    const GLOBAL_WARNING_COOKIE_NAME = "DismissGlobalWarning";
    public function getWarningScopes()
    {
        return array("nonStrictMode" => array("dismissed" => false, "lastChecked" => null, "frequency" => 14400), "ssl" => array("dismissed" => false, "lastChecked" => null, "frequency" => 1209600));
    }
    public function getNotifications()
    {
        $dismissed = $this->getDismissalTracker();
        $warnings = $this->getWarningScopes();
        $notification = "";
        foreach ($warnings as $alert => $details) {
            if (isset($dismissed[$alert])) {
                $details["dismissed"] = true;
                if (!empty($dismissed[$alert]["lastChecked"])) {
                    $details["lastChecked"] = $dismissed[$alert]["lastChecked"];
                }
            }
            $cutOffTime = \WHMCS\Carbon::now()->subSeconds($details["frequency"])->getTimestamp();
            if (!$details["dismissed"] || is_null($details["lastChecked"]) || $details["lastChecked"] < $cutOffTime) {
                $checkAction = "checkWarning" . ucfirst($alert);
                if (method_exists($this, $checkAction) && !$this->{$checkAction}()) {
                    $htmlAction = "getWarningHTML" . ucfirst($alert);
                    $notification = $this->{$htmlAction}() . $this->getGlobalWarningDismissalHTML($alert);
                    break;
                }
            }
        }
        return $notification;
    }
    public function getDismissalTracker()
    {
        $dismissed = $this->getCookie();
        if (!$dismissed || !is_array($dismissed)) {
            $dismissed = array();
        }
        return $dismissed;
    }
    public function updateDismissalTracker($alertToDismiss = "")
    {
        $scopes = $this->getWarningScopes();
        if ($alertToDismiss && array_key_exists($alertToDismiss, $scopes)) {
            $dismissed = $this->getDismissalTracker();
            $dismissed[$alertToDismiss]["dismissed"] = true;
            $dismissed[$alertToDismiss]["lastChecked"] = time();
            $this->setCookie($dismissed);
        }
        return $this;
    }
    protected function setCookie(array $data = array())
    {
        return \WHMCS\Cookie::set(static::GLOBAL_WARNING_COOKIE_NAME, $data);
    }
    protected function getCookie()
    {
        return \WHMCS\Cookie::get(static::GLOBAL_WARNING_COOKIE_NAME, true);
    }
    protected function getGlobalWarningDismissalHTML($alert)
    {
        if (!is_string($alert)) {
            $alert = "";
        }
        $globalAdminWarningDismissUrl = routePath("admin-dismiss-global-warning");
        $csrfToken = generate_token("plain");
        $alertLabel = ucfirst($alert);
        $html = "<script>\njQuery(document).ready(function(){\n    \$('#btnGlobalWarning" . $alertLabel . "').click(function () {\n        WHMCS.http.jqClient.post(\n            '" . $globalAdminWarningDismissUrl . "', \n            'token=" . $csrfToken . "&alert=" . $alert . "'\n        );\n    })\n});\n</script>\n<button type=\"button\" \n    id=\"btnGlobalWarning" . $alertLabel . "\"\n    class=\"close\" \n    data-dismiss=\"alert\" \n    aria-label=\"Close\"\n    >\n        <span aria-hidden=\"true\">&times;</span>\n    </button>";
        return $html;
    }
    protected function checkWarningSsl()
    {
        return \App::in_ssl();
    }
    protected function getWarningHTMLSsl()
    {
        $linkText = \AdminLang::trans("ssl_warning.buy_link");
        $link = "<a href=\"https://go.whmcs.com/1345/get-ssl\" \ntarget=\"_blank\" \nclass=\"alert-link\">" . $linkText . "</a>";
        $msg = \AdminLang::trans("ssl_warning.insecure_connection") . PHP_EOL . \AdminLang::trans("ssl_warning.dont_have_ssl", array(":buyLink" => $link));
        $html = "<i class=\"far fa-exclamation-triangle fa-fw\"></i>" . PHP_EOL . $msg;
        return $html;
    }
    protected function checkWarningNonStrictMode()
    {
        $lastSqlModeCheck = \WHMCS\Session::get("adminSqlStrictModeCheck");
        $justUnderFourHours = \WHMCS\Carbon::now()->subHours(4)->subMinute(1)->getTimestamp();
        if (!is_numeric($lastSqlModeCheck) || $lastSqlModeCheck < $justUnderFourHours) {
            if ($this->getDatabase()->isSqlStrictMode()) {
                return false;
            }
            \WHMCS\Session::setAndRelease("adminSqlStrictModeCheck", \WHMCS\Carbon::now()->getTimestamp());
        }
        return true;
    }
    protected function getDatabase()
    {
        return \DI::make("db");
    }
    protected function getWarningHTMLNonStrictMode()
    {
        $html = "<span>\n    <i class=\"fas fa-exclamation-triangle\"></i>\n    MySQL Strict Mode Detected:\n</span>\nMySQL strict mode must be disabled to ensure error free operation of WHMCS.\n<a href=\"https://docs.whmcs.com/Database_Setup\" target=\"_blank\">\n    Learn more &raquo;\n</a>";
        return $html;
    }
}

?>