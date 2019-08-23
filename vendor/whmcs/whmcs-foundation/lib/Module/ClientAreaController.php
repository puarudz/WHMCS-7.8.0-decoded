<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class ClientAreaController
{
    public function index(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $moduleName = $request->getAttribute("module");
        $moduleIncludePath = "/modules/addons/" . $moduleName;
        $addonModule = new Addon();
        $addonModule->load($moduleName);
        $configarray = $addonModule->call("config");
        $modulevars = array();
        $result = select_query("tbladdonmodules", "", array("module" => $moduleName));
        while ($data = mysql_fetch_array($result)) {
            $modulevars[$data["setting"]] = $data["value"];
        }
        if (!count($modulevars)) {
            redir();
        }
        $modulevars["modulelink"] = "index.php?m=" . $moduleName;
        $_ADDONLANG = array();
        $clientAreaLanguage = \Lang::getName();
        if (!isValidforPath($clientAreaLanguage)) {
            exit("Invalid Client Area Language Name");
        }
        $addonLanguageFilePath = ROOTDIR . $moduleIncludePath . "/lang/" . $clientAreaLanguage . ".php";
        if (file_exists($addonLanguageFilePath)) {
            require $addonLanguageFilePath;
        } else {
            if ($configarray["language"]) {
                if (!isValidforPath($configarray["language"])) {
                    exit("Invalid Addon Module Default Language Name");
                }
                $addonlangfile = ROOTDIR . $moduleIncludePath . "/lang/" . $configarray["language"] . ".php";
                if (file_exists($addonlangfile)) {
                    require $addonlangfile;
                }
            }
        }
        if (count($_ADDONLANG)) {
            $modulevars["_lang"] = $_ADDONLANG;
        }
        $results = $addonModule->call("clientarea", $modulevars);
        if (!is_array($results)) {
            logActivity("Addon Module \"" . $moduleName . "\" returned an invalid client area output response type");
            redir();
        }
        if (isset($results["requirelogin"]) && $results["requirelogin"] && !\WHMCS\Session::get("uid")) {
            $clientArea = new \WHMCS\ClientArea\ClientAreaController();
            return $clientArea->loginWithRedirect($request);
        }
        $whmcs = \App::self();
        if (isset($results["forcessl"]) && $results["forcessl"] && $whmcs->isSSLAvailable()) {
            $smartyvalues["systemurl"] = $whmcs->getSystemURL();
            if (!$whmcs->in_ssl()) {
                \WHMCS\Session::set("FORCESSL", true);
                $whmcs->redirectSystemURL($whmcs->getCurrentFilename(false), $_REQUEST);
            }
        }
        $view = new \WHMCS\ClientArea();
        if (isset($results["templatefile"]) && $results["templatefile"] && ($templatePath = $addonModule->findTemplate($results["templatefile"]))) {
            $view->setTemplate($templatePath);
        } else {
            logActivity("Addon Module \"" . $moduleName . "\" requested template file \"" . $results["templatefile"] . ".tpl\" which could not be found");
            redir();
        }
        if (isset($results["pagetitle"]) && $results["pagetitle"]) {
            $view->setPageTitle($results["pagetitle"]);
        }
        if (isset($results["displayTitle"]) && $results["displayTitle"]) {
            $view->setDisplayTitle($results["displayTitle"]);
        } else {
            $view->setDisplayTitle($view->getPageTitle());
        }
        if (isset($results["tagline"]) && $results["tagline"]) {
            $view->setTagLine($results["tagline"]);
        }
        if (isset($results["breadcrumb"])) {
            if (is_array($results["breadcrumb"])) {
                $view->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
                foreach ($results["breadcrumb"] as $link => $label) {
                    $view->addToBreadCrumb($link, $label);
                }
            } else {
                if (is_string($results["breadcrumb"])) {
                    $view->setBreadCrumbHtml("<a href=\"index.php\">" . \Lang::trans("globalsystemname") . "</a> > " . $results["breadcrumb"]);
                }
            }
        } else {
            $view->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        }
        $templateVariables = array();
        if (isset($results["vars"]) && is_array($results["vars"])) {
            $templateVariables = $results["vars"];
        }
        if (isset($results["templateVariables"]) && is_array($results["templateVariables"])) {
            $templateVariables = array_merge($templateVariables, $results["templateVariables"]);
        }
        $view->setTemplateVariables($templateVariables);
        $view->addOutputHookFunction("ClientAreaPageAddonModule");
        return $view;
    }
}

?>