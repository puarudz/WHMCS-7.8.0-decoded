<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View;

class HtmlErrorPage
{
    public $title = "Oops!";
    public $body = "";
    public function __construct($title = NULL, $body = NULL, $css = NULL)
    {
        if (is_null($title)) {
            $title = $this->defaultTitle();
        }
        if (is_null($body)) {
            $body = $this->defaultBody();
        }
        if (is_null($css)) {
            $css = $this->defaultCss();
        }
        $this->title = $title;
        $this->body = $body;
        $this->css = $css;
    }
    public static function getHtmlAnyEnvironmentIssues()
    {
        $html = "";
        if (!\WHMCS\Utility\ErrorManagement::performErrorReportingSanityCheck()) {
            $currentLevel = error_reporting();
            $html .= "<p class=\"info\">" . "Error Reporting Immutable! WHMCS requires the ability to" . " control the PHP error_reporting value but your environment" . " is applying a restriction.  This inhibits WHMCS's abilities" . " when trying to mitigate against unexpected errors or data" . " integrity issues." . " Your current value of " . $currentLevel . " is more sensitive than the value used in" . " most conditions by WHMCS: " . \WHMCS\Utility\ErrorManagement::ERROR_LEVEL_ERRORS_VALUE . " (E_ALL ^ E_WARNING ^ E_USER_WARNING ^ E_NOTICE ^ E_USER_NOTICE ^ E_STRICT ^ E_DEPRECATED ^ E_USER_DEPRECATED).";
            if (strpos(php_sapi_name(), "fpm-fcgi") !== false) {
                $html .= " Your environment uses PHP-FPM, please ensure that" . " \"error_reporting\" is not defined via the \"php_admin_value\"" . " directive since that will cause immutable behavior.";
            }
            $html .= " WHMCS may not function properly until this is rectified." . "</span></p>";
        }
        return $html;
    }
    public static function getHtmlAdminHelp($hasStackAlready = true)
    {
        if ($hasStackAlready) {
            $html = "<span class=\"info\">For additional assistance, please reference the" . " <a href=\"https://docs.whmcs.com/Troubleshooting_Guide\" target=\"_blank\">" . "WHMCS TroubleShooting Guide &raquo;</a></span><br>";
        } else {
            $html = "<span class=\"info\">To receive a more detailed error message," . " please enable Display Errors via General Settings." . " <a href=\"https://docs.whmcs.com/Error_Management#Controlling_How_Errors_Are_Managed\" target=\"_blank\">" . "Learn More &raquo;</a></span><br>";
        }
        return $html;
    }
    public function getHtmlErrorPage()
    {
        $html = $this->defaultHtmlErrorPage();
        $html = str_replace("{{css}}", $this->css, $html);
        $html = str_replace("{{body}}", $this->body, $html);
        $html = str_replace("{{title}}", $this->title, $html);
        return $html;
    }
    public function defaultTitle()
    {
        return "Oops!";
    }
    public function defaultCss()
    {
        $css = "\n<style>\n        body {\n            margin: 30px 40px;\n            background-color: #f6f6f6;\n        }\n        .error-container {\n            padding: 50px 40px;\n            font-family: \"Helvetica Neue\",Helvetica,Arial,sans-serif;\n            font-size: 14px;\n        }\n        h1 {\n            margin: 0;\n            font-size: 48px;\n            font-weight: 400;\n        }\n        h2 {\n            margin: 0;\n            font-size: 26px;\n            font-weight: 300;\n        }\n        a {\n            color: #336699;\n        }\n        p.back-to-home {\n            margin-top: 30px;\n        }\n        p.debug{\n            padding: 20px 0;\n            font-family: \"Courier New\", Courier, monospace, serif;\n            font-size: 14px;\n        }\n        .info {\n            border: solid 1px #999;\n            padding: 5px;\n            background-color: #d9edf7;\n        }\n    </style>\n";
        return $css;
    }
    public function defaultBody()
    {
        $body = "\n<h1>{{title}}</h1>\n<h2>Something went wrong and we couldn't process your request.</h2>\n<p>Please go back to the previous page and try again.</p>\n";
        return $body;
    }
    public function defaultHtmlErrorPage()
    {
        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n  <head>\n    <meta charset=\"utf-8\">\n    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n    <title>{{title}}</title>\n    {{css}}\n  </head>\n  <body>\n    <div class=\"error-container\">{{body}}</div>\n  </body>\n</html>";
        return $html;
    }
    public static function getTemplateErrorPage()
    {
        global $aInt;
        $html = "";
        $templateFile = null;
        if (defined("ADMINAREA")) {
            $template = $aInt instanceof \WHMCS\Admin ? $aInt->adminTemplate : \WHMCS\Admin::DEFAULT_ADMIN_TEMPLATE;
            try {
                $templateFile = ROOTDIR . DIRECTORY_SEPARATOR . \App::getApplicationConfig()->customadminpath . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . "internal-error.tpl";
            } catch (\Error $e) {
            } catch (\Exception $e) {
            }
        }
        if (!$templateFile || !file_exists($templateFile) || !is_readable($templateFile)) {
            $template = @\WHMCS\Config\Setting::getValue("Template");
            $templateFile = ROOTDIR . "/templates/" . $template . "/error/internal-error.tpl";
        }
        if (file_exists($templateFile) || is_readable($templateFile)) {
            $html = file_get_contents($templateFile);
        }
        return $html;
    }
    public static function getHtmlStackTrace($exception)
    {
        $html = "";
        if (!$exception instanceof \Exception && !$exception instanceof \Error) {
            return $html;
        }
        $html = (string) $exception;
        $html = preg_replace("/[\n]+/", "\n", $html);
        return nl2br($html);
    }
}

?>