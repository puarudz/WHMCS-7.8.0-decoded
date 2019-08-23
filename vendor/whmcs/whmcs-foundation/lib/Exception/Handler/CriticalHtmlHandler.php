<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Handler;

class CriticalHtmlHandler extends \Whoops\Handler\Handler
{
    use ExceptionLoggingTrait;
    public function handle()
    {
        if ($this->isActuallyError() && !$this->isActuallyFatalError()) {
            return \Whoops\Handler\Handler::LAST_HANDLER;
        }
        $this->log($this->getException());
        if (!headers_sent()) {
            header("HTTP/1.1 500 Internal Server Error");
        }
        $errorPage = new \WHMCS\View\HtmlErrorPage();
        if (\WHMCS\Utility\ErrorManagement::isDisplayErrorCurrentlyVisible()) {
            $knownIssues = "";
            if (\WHMCS\Admin::getId()) {
                $knownIssues = \WHMCS\View\HtmlErrorPage::getHtmlAnyEnvironmentIssues();
            }
            $errorPage->body .= $knownIssues . "<p class=\"debug\">" . \WHMCS\View\HtmlErrorPage::getHtmlStackTrace($this->getException()) . "</p>";
        }
        echo $errorPage->getHtmlErrorPage();
        return \Whoops\Handler\Handler::QUIT;
    }
    protected function isActuallyError()
    {
        $e = $this->getException();
        if ($e && ($e instanceof \ErrorException || $e instanceof \Error)) {
            return true;
        }
        return false;
    }
    protected function isActuallyFatalError()
    {
        $e = $this->getException();
        if ($e) {
            if ($e instanceof \Error) {
                return true;
            }
            if ($e instanceof \ErrorException && \Whoops\Util\Misc::isLevelFatal($e->getSeverity())) {
                return true;
            }
        }
        return false;
    }
}

?>