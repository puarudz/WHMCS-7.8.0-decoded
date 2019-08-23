<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Handler;

class PrettyHtmlHandler extends CriticalHtmlHandler
{
    public function handle()
    {
        if (headers_sent() || $this->isActuallyError()) {
            return \Whoops\Handler\Handler::DONE;
        }
        if (defined("APICALL")) {
        } else {
            try {
                $this->log($this->getException());
                (new \Zend\Diactoros\Response\SapiEmitter())->emit(new \Zend\Diactoros\Response\HtmlResponse($this->getHtmlErrorPage(), 500));
                return \Whoops\Handler\Handler::QUIT;
            } catch (\Exception $e) {
                $this->log($e);
            }
        }
        return \Whoops\Handler\Handler::DONE;
    }
    public function getHtmlErrorPage()
    {
        try {
            $html = \WHMCS\View\HtmlErrorPage::getTemplateErrorPage();
            $knownIssues = \WHMCS\View\HtmlErrorPage::getHtmlAnyEnvironmentIssues();
            if (!$html) {
                $contact = "\n<p>If the problem persists, please <a href=\"mailto:{{email}}\">contact us</a>.</p>\n<p class=\"back-to-home\"><a href=\"{{systemurl}}\">&laquo; Back to Homepage</a></p>\n";
                $errorPage = new \WHMCS\View\HtmlErrorPage();
                $errorPage->body .= $contact;
                if ($knownIssues) {
                    $errorPage->body .= "{{environmentIssues}}";
                }
                if (\WHMCS\Utility\ErrorManagement::isDisplayErrorCurrentlyVisible()) {
                    $errorPage->body .= "<p class=\"debug\">{{stacktrace}}</p>";
                }
                if (\WHMCS\Admin::getId()) {
                    if (\WHMCS\Utility\ErrorManagement::isDisplayErrorCurrentlyVisible()) {
                        $errorPage->body = str_replace("{{stacktrace}}", "{{adminHelp}}<br/>{{stacktrace}}", $errorPage->body);
                    } else {
                        if (\WHMCS\User\Admin\Permission::currentAdminHasPermissionName("Configure General Settings")) {
                            $errorPage->body .= "<p class=\"debug\">{{adminHelp}}<br/>{{stacktrace}}</p>";
                        } else {
                            $errorPage->body .= "<p class=\"debug\">{{adminHelp}}</p>";
                        }
                    }
                }
                $html = $errorPage->getHtmlErrorPage();
            }
            $systemUrl = @\WHMCS\Config\Setting::getValue("SystemURL");
            if (1 < strlen($systemUrl) && substr($systemUrl, -1) == "/") {
                $systemUrl = substr($systemUrl, 0, -1);
            }
            if ($systemUrl) {
                $html = str_replace("{{systemurl}}", $systemUrl, $html);
            } else {
                $html = str_replace("{{systemurl}}", "index.php", $html);
            }
            $email = @\WHMCS\Config\Setting::getValue("Email");
            if ($email) {
                $html = str_replace("{{email}}", $email, $html);
            } else {
                $contact = $systemUrl ? $systemUrl . "/contact.php" : "contact.php";
                $html = str_replace("mailto:{{email}}", $contact, $html);
            }
            $showStacktrace = \WHMCS\Utility\ErrorManagement::isDisplayErrorCurrentlyVisible() || \WHMCS\User\Admin\Permission::currentAdminHasPermissionName("Configure General Settings");
            if ($showStacktrace) {
                $html = str_replace("{{stacktrace}}", \WHMCS\View\HtmlErrorPage::getHtmlStackTrace($this->getException()), $html);
            } else {
                $html = str_replace("{{stacktrace}}", "", $html);
            }
            if (\WHMCS\Admin::getID()) {
                $html = str_replace("{{adminHelp}}", \WHMCS\View\HtmlErrorPage::getHtmlAdminHelp($showStacktrace), $html);
                $html = str_replace("{{environmentIssues}}", $knownIssues, $html);
            } else {
                $html = str_replace("{{adminHelp}}", "", $html);
            }
            $html = str_replace("{{environmentIssues}}", "", $html);
        } catch (\Exception $e) {
            $html = (new \WHMCS\View\HtmlErrorPage())->getHtmlErrorPage();
        }
        return $html;
    }
}

?>