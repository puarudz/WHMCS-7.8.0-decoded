<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\General\UriManagement;

class ConfigurationController
{
    protected $relativePath = "";
    const PATH_COMPARISON_TEST = "/resources/test";
    const PATH_COMPARISON_INDEX = "";
    const ROUTE_TYPE_REWRITE = "/detect-route-environment";
    const ROUTE_TYPE_ACCEPTPATHINFO = "/index.php/detect-route-environment";
    const ROUTE_TYPE_BASIC = "/index.php?rp=/detect-route-environment";
    const SETTING_MODE_OVERRIDE = "UriModeOverride";
    const SETTING_AUTO_MANAGE = "UriRewriteAutoManage";
    public function __construct($pathComparison = NULL)
    {
        if (is_null($pathComparison)) {
            $pathComparison = self::PATH_COMPARISON_INDEX;
        }
        $this->setRelativePath($pathComparison);
    }
    public function detectRouteEnvironment(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!$this->isAuthorizedRequest($request)) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMessage" => "Access Denied"), "403");
        }
        $requestUri = $request->getServerParams()["REQUEST_URI"];
        $baseUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        if (strpos($requestUri, $baseUrl) === 0) {
            $requestUri = str_replace($baseUrl, "", $requestUri);
        }
        if (substr($requestUri, 0, 1) != "/") {
            $requestUri = "/" . $requestUri;
        }
        $data = array("mode" => $this->getModeFromUri($requestUri));
        return new \WHMCS\Http\Message\JsonResponse($data);
    }
    public function remoteDetectEnvironmentMode(\WHMCS\Http\Message\ServerRequest $request)
    {
        $pathComparison = $request->get("pathComparison", "");
        if (!empty($pathComparison) && in_array($pathComparison, array(self::PATH_COMPARISON_TEST, self::PATH_COMPARISON_INDEX))) {
            $this->setRelativePath($pathComparison);
        }
        $mode = $this->queryEnvironmentMode();
        $responseData = array("status" => "okay", "data" => array("mode" => $mode));
        if ($request->get("setDetected")) {
            $subRequest = (new \WHMCS\Http\Message\ServerRequest())->withParsedBody(array("mode" => $mode, "setOverride" => false));
            $subResponse = $this->setEnvironmentMode($subRequest);
            $subResponseData = $subResponse->getRawData();
            if ($subResponseData["status"] == "okay") {
                $responseData["successMessage"] = $subResponseData["successMessage"];
            }
        }
        return new \WHMCS\Http\Message\JsonResponse($responseData);
    }
    public function setEnvironmentMode(\WHMCS\Http\Message\ServerRequest $request)
    {
        $mode = $request->get("mode", "");
        try {
            if (!$mode) {
                throw new \RuntimeException("Missing \"mode\" parameter");
            }
            $uriPath = new \WHMCS\Route\UriPath();
            $uriPath->setMode($mode);
            logActivity("Updated URI Path Mode to: " . $mode);
            if (class_exists("AdminLang")) {
                switch ($mode) {
                    case \WHMCS\Route\UriPath::MODE_REWRITE:
                        $valueName = \AdminLang::trans("uriPathMgmt.btnModeRewrite");
                        break;
                    case \WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO:
                        $valueName = \AdminLang::trans("uriPathMgmt.btnModeAcceptPathInfo");
                        break;
                    default:
                        $valueName = \AdminLang::trans("uriPathMgmt.btnModeBasic");
                }
            } else {
                $valueName = $mode;
            }
            if ($request->has("setOverride")) {
                $this->setUriPathModeSetting((bool) $request->get("setOverride"), false);
            }
            $responseData = array("status" => "okay", "successMessage" => "Path Mode Value set to \"" . $valueName . "\"");
        } catch (\Exception $e) {
            $responseData = array("status" => "error", "errorMessage" => $e->getMessage());
        }
        return new \WHMCS\Http\Message\JsonResponse($responseData);
    }
    public function getRelativePath()
    {
        return $this->relativePath;
    }
    public function setRelativePath($relativePath)
    {
        $this->relativePath = $relativePath;
        return $this;
    }
    public static function generateAuthorization()
    {
        $token = str_random(5);
        \WHMCS\TransientData::getInstance()->store("detect-route-environment", $token, 60);
        return $token;
    }
    protected function isAuthorizedRequest(\WHMCS\Http\Message\ServerRequest $request)
    {
        if ($request->has("token")) {
            $storedToken = \WHMCS\TransientData::getInstance()->retrieve("detect-route-environment");
            if ($storedToken && $request->get("token") === $storedToken) {
                return true;
            }
        }
        return false;
    }
    protected function getModeFromUri($routePath)
    {
        $pathParts = explode("?", $routePath);
        $relativePath = $this->getRelativePath();
        switch ($pathParts[0]) {
            case $relativePath . self::ROUTE_TYPE_REWRITE:
                $mode = \WHMCS\Route\UriPath::MODE_REWRITE;
                break;
            case $relativePath . self::ROUTE_TYPE_ACCEPTPATHINFO:
                $mode = \WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO;
                break;
            default:
                if (strpos($pathParts[0], "index.php") !== false && isset($pathParts[1]) && strpos(urldecode($pathParts[1]), "rp=/detect-route-environment") !== false) {
                    $mode = \WHMCS\Route\UriPath::MODE_BASIC;
                } else {
                    $mode = \WHMCS\Route\UriPath::MODE_UNKNOWN;
                }
        }
        return $mode;
    }
    public function getAllKnowRouteTypes()
    {
        return array(self::ROUTE_TYPE_REWRITE, self::ROUTE_TYPE_ACCEPTPATHINFO, self::ROUTE_TYPE_BASIC);
    }
    protected function queryEnvironmentMode()
    {
        try {
            $token = self::generateAuthorization();
            $baseUrl = \WHMCS\Config\Setting::getValue("SystemURL");
            if (substr($baseUrl, -1) == "/") {
                $baseUrl = substr($baseUrl, 0, -1);
            }
            if (!$baseUrl) {
                throw new \RuntimeException("System URL must be configured to perform route mode test");
            }
            $client = new \GuzzleHttp\Client(array("defaults" => array("verify" => false, "exceptions" => false, "timeout" => 10)));
            $modes = \WHMCS\Route\UriPath::getAllKnownModes();
            $relativePath = $this->getRelativePath();
            foreach ($this->getAllKnowRouteTypes() as $path) {
                $path = $baseUrl . $relativePath . $path;
                $response = $client->get($path, array("query" => array("token" => $token)));
                if ($response->getStatusCode() == 200) {
                    $body = $response->getBody();
                    $body = json_decode((string) $body, true);
                    if (is_array($body) && !empty($body["mode"]) && in_array($body["mode"], $modes)) {
                        return $body["mode"];
                    }
                }
            }
        } catch (\Exception $e) {
            logActivity("Unable to perform route mode test: " . $e->getMessage());
        }
        return \WHMCS\Route\UriPath::MODE_UNKNOWN;
    }
    public function updateUriManagementSetting(\WHMCS\Http\Message\ServerRequest $request)
    {
        if (!$request->has("state")) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMessage" => "Missing required \"state\" parameter"));
        }
        if (!$request->has("setting")) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMessage" => "Missing required \"setting\" parameter"));
        }
        $settingToAffect = $request->get("setting");
        if (!in_array($settingToAffect, array(static::SETTING_MODE_OVERRIDE, static::SETTING_AUTO_MANAGE))) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMessage" => "Invalid \"setting\" parameter"));
        }
        $state = $request->get("state");
        $state = in_array($state, array("true", 1, "1", "on", true), true) ? true : false;
        switch ($settingToAffect) {
            case static::SETTING_MODE_OVERRIDE:
                $response = $this->setUriPathModeSetting($state);
                break;
            case static::SETTING_AUTO_MANAGE:
            default:
                $response = $this->setUriRewriteSetting($state);
        }
        return $response;
    }
    private function setUriPathModeSetting($state, $queryEnvironment = true)
    {
        $stateName = $state ? "Enabled" : "Disabled";
        $result = array("status" => "okay", "successMessage" => "Path Mode Override " . $stateName);
        if ($queryEnvironment && !$state) {
            $uriPath = new \WHMCS\Route\UriPath();
            try {
                $bestMode = $this->queryEnvironmentMode();
                $uriPath->setMode($bestMode);
            } catch (\Exception $e) {
                $bestMode = $uriPath::MODE_UNKNOWN;
            }
            $uriPath->setMode($bestMode);
            $result["data"] = array("mode" => $bestMode);
        }
        \WHMCS\Config\Setting::setValue(static::SETTING_MODE_OVERRIDE, (int) $state);
        logActivity(sprintf("Uri Path Management Setting \"URI Mode Override\" %s", $stateName));
        return new \WHMCS\Http\Message\JsonResponse($result);
    }
    private function setUriRewriteSetting($state)
    {
        \WHMCS\Config\Setting::setValue(static::SETTING_AUTO_MANAGE, (int) $state);
        $stateName = $state ? "Enabled" : "Disabled";
        logActivity(sprintf("URI Path Management Setting \"Rewrite Auto Management\" %s", $stateName));
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "okay", "successMessage" => "Auto-Managed Rewrite " . $stateName));
    }
    public function applyBestConfiguration(\WHMCS\Http\Message\ServerRequest $request)
    {
        if ($request->request()->get("enableRewriteMgmt", false)) {
            $this->setUriRewriteSetting(true);
        }
        $responseData = array();
        $rewriteFile = null;
        $isAutoManaged = (bool) \WHMCS\Config\Setting::getValue(static::SETTING_AUTO_MANAGE);
        try {
            if (!$isAutoManaged) {
                throw new \WHMCS\Exception\Information("Not Auto-Managed");
            }
            $successMessages = array();
            $rewriteFile = \WHMCS\Route\Rewrite\File::factory(\WHMCS\Route\Rewrite\File::FILE_DEFAULT);
            if (!$rewriteFile->isInSync()) {
                $rewriteFile->updateWhmcsRuleSet();
            }
            $successMessages[] = "Rewrite rules applied.";
            if ($rewriteFile) {
                $rewriteFile->flock(LOCK_UN);
                unset($rewriteFile);
            }
            $responseData = array("status" => "okay");
            $subRequest = $request->withAttribute("setDetected", true);
            $subResponse = $this->remoteDetectEnvironmentMode($subRequest);
            $subResponse = $subResponse->getRawData();
            if (isset($subResponse["successMessage"])) {
                $successMessages[] = $subResponse["successMessage"];
                $responseData["data"]["mode"] = $subResponse["data"]["mode"];
            }
            $responseData["successMessage"] = implode("\n", $successMessages);
            $responseData["successMessageHtml"] = (new View\Helper\SimpleSetting())->resetSuccessHtml($successMessages);
        } catch (\Exception $e) {
            if ($e instanceof \WHMCS\Exception\Information) {
                $htaccessFile = ROOTDIR . "/" . \WHMCS\Route\Rewrite\File::FILE_DEFAULT;
                try {
                    if (!$rewriteFile) {
                        $rewriteFile = \WHMCS\Route\Rewrite\File::factory($htaccessFile);
                    }
                    if ($rewriteFile->isExclusivelyWhmcs() || $rewriteFile->isEmpty()) {
                        $responseData = array("status" => "prompt", "promptMessage" => "Rewrite Auto-Management must" . " be enabled before any modification to " . $htaccessFile, "promptTitle" => "Enable Rewrite Auto-Management");
                    } else {
                        if ($rewriteFile) {
                            $rewriteFile->flock(LOCK_UN);
                            unset($rewriteFile);
                        }
                        $subRequest = (new \WHMCS\Http\Message\ServerRequest())->withAttribute("pathComparison", static::PATH_COMPARISON_TEST);
                        $distModeResponse = $this->remoteDetectEnvironmentMode($subRequest);
                        $distModeResponse = $distModeResponse->getRawData();
                        $mode = $distModeResponse["data"]["mode"];
                        if ($mode == \WHMCS\Route\UriPath::MODE_REWRITE) {
                            $responseData = array("status" => "prompt", "promptMessage" => "Rewrite Auto-Management must" . " be enabled before any modification to " . $htaccessFile, "promptTitle" => "Enable Rewrite Auto-Management");
                        } else {
                            $successMessages = array();
                            $responseData = array("status" => "okay");
                            $this->setRelativePath(static::PATH_COMPARISON_INDEX);
                            $subRequest = (new \WHMCS\Http\Message\ServerRequest())->withAttribute("setDetected", true);
                            $subResponse = $this->remoteDetectEnvironmentMode($subRequest);
                            $subResponse = $subResponse->getRawData();
                            if (isset($subResponse["successMessage"])) {
                                $successMessages[] = $subResponse["successMessage"];
                                $responseData["data"]["mode"] = $subResponse["data"]["mode"];
                            }
                            $responseData["successMessage"] = implode("\n", $successMessages);
                            $responseData["successMessageHtml"] = (new View\Helper\SimpleSetting())->resetSuccessHtml($successMessages);
                        }
                    }
                } catch (\Exception $e) {
                    $responseData = array("status" => "error", "errorMessage" => $e->getMessage());
                }
            } else {
                $responseData = array("status" => "error", "errorMessage" => $e->getMessage());
            }
        }
        if ($rewriteFile) {
            $rewriteFile->flock(LOCK_UN);
            unset($rewriteFile);
        }
        return new \WHMCS\Http\Message\JsonResponse($responseData);
    }
    public function view(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $content = "";
        $aInt = new \WHMCS\Admin("Configure General Settings", false);
        $aInt->title = "URI Path Management";
        $aInt->sidebar = "config";
        $aInt->icon = "autosettings";
        $aInt->helplink = "URI Path Management";
        $managementSettingName = static::SETTING_AUTO_MANAGE;
        $overrideSettingName = static::SETTING_MODE_OVERRIDE;
        $managementStatus = \WHMCS\Config\Setting::getValue($managementSettingName) ? "checked" : "";
        $storedOverrideStatus = \WHMCS\Config\Setting::getValue($overrideSettingName);
        $testedModeValue = $this->queryEnvironmentMode();
        $uriPath = new \WHMCS\Route\UriPath();
        $uriPathModeHtml = "";
        $storedMode = $uriPath->getMode();
        if ($storedOverrideStatus) {
            $effectiveMode = $storedMode;
        } else {
            $effectiveMode = $testedModeValue;
        }
        $validModes = array();
        foreach ($uriPath::getAllKnownModes() as $option) {
            switch ($option) {
                case \WHMCS\Route\UriPath::MODE_REWRITE:
                    $validModes[$option] = \AdminLang::trans("uriPathMgmt.btnModeRewrite");
                    break;
                case \WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO:
                    $validModes[$option] = \AdminLang::trans("uriPathMgmt.btnModeAcceptPathInfo");
                    break;
                default:
                    $validModes[$option] = \AdminLang::trans("uriPathMgmt.btnModeBasic");
            }
            $uriPathModeHtml .= sprintf("<li><a href=\"#%s\">%s</a></li>", $option, $validModes[$option]);
        }
        $selectedMode = isset($validModes[$effectiveMode]) ? $validModes[$effectiveMode] : $validModes[\WHMCS\Route\UriPath::MODE_BASIC];
        $viableItem = "<li class=\"list-group-item list-group-item-success\"><span class=\"glyphicon glyphicon-ok\" aria-hidden=\"true\"></span>&nbsp;%s</li>";
        $nonviableItem = "<li class=\"list-group-item list-group-item-danger\"><span class=\"glyphicon glyphicon-remove\" aria-hidden=\"true\"></span>&nbsp;%s</li>";
        if ($testedModeValue == \WHMCS\Route\UriPath::MODE_REWRITE) {
            $supportedEnvironmentModes = sprintf($viableItem, $validModes[\WHMCS\Route\UriPath::MODE_REWRITE]) . sprintf($viableItem, $validModes[\WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO]) . sprintf($viableItem, $validModes[\WHMCS\Route\UriPath::MODE_BASIC]);
        } else {
            if ($testedModeValue == \WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO) {
                $supportedEnvironmentModes = sprintf($nonviableItem, $validModes[\WHMCS\Route\UriPath::MODE_REWRITE]) . sprintf($viableItem, $validModes[\WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO]) . sprintf($viableItem, $validModes[\WHMCS\Route\UriPath::MODE_BASIC]);
            } else {
                $supportedEnvironmentModes = sprintf($nonviableItem, $validModes[\WHMCS\Route\UriPath::MODE_REWRITE]) . sprintf($nonviableItem, $validModes[\WHMCS\Route\UriPath::MODE_ACCEPTPATHINFO]) . sprintf($viableItem, $validModes[\WHMCS\Route\UriPath::MODE_BASIC]);
            }
        }
        if ($storedOverrideStatus) {
            $overrideStatusChecked = "checked";
            $overrideStatusDisabled = "";
        } else {
            $overrideStatusChecked = "";
            $overrideStatusDisabled = "disabled";
        }
        $desc = "URI Path Management determine how links will be generated on page render, as well as how WHMCS will process and route HTTP requests.";
        $desc2 = "URI Rewrite Auto Management is designed for systems using the Apache web server. " . "This feature works by creating and maintaining directives and mod_rewrite rules in the root directory's .htaccess. " . "The generated content is optimized for WHMCS and may not be fully compatible with other content you have placed in the .htaccess file. " . "If you wish to manually manage the .htaccess file you may disable the feature.";
        $desc2 = nl2br($desc2);
        $content .= "<p>" . $desc . "</p>";
        $content .= $aInt->beginAdminTabs(array("Path Mode", "Rewrite File"), true, "UriMgmt");
        $whmcsRules = implode("<br/>", array_merge(array(\WHMCS\Route\Rewrite\File::MARKER_BEGIN), \WHMCS\Input\Sanitize::makeSafeForOutput((new \WHMCS\Route\Rewrite\RuleSet())->generateRuleSet()), array(\WHMCS\Route\Rewrite\File::MARKER_END)));
        $syncLabelClasses = "label-success";
        $syncLabelText = "Up To Date";
        $syncButtonClasses = "btn-default disabled";
        $rewriteFile = "";
        try {
            $rewriteFile = \WHMCS\Route\Rewrite\File::Factory(\WHMCS\Route\Rewrite\File::FILE_DEFAULT);
            if (!$rewriteFile->isInSync()) {
                $syncLabelClasses = "label-info";
                $syncLabelText = "Out Of Sync";
                $syncButtonClasses = "btn-info";
            }
        } catch (\Exception $e) {
            $syncLabelClasses = "label-danger";
            $syncLabelText = "File Not Available";
            $syncButtonClasses = "btn-default disabled";
        }
        if ($rewriteFile) {
            $rewriteFile->flock(LOCK_UN);
            unset($rewriteFile);
        }
        $htaccessFile = ROOTDIR . "/" . \WHMCS\Route\Rewrite\File::FILE_DEFAULT;
        $desc4 = "Path Mode determines how links will be generated.";
        $tab1 = "<div class=\"container-fluid\">\n    <h2>Path Mode Overview</h2>\n    <p>" . $desc4 . "</p>\n    <div class=\"container-fluid\">\n        <div class=\"inset-grey-bg\">\n            <table class=\"table\">\n                <thead>\n                    <tr>\n                        <th>URI Path Mode</th>\n                        <th class=\"hidden-xs\"><th>\n                    </tr> \n                </thead>\n                <tbody>\n                    <tr>\n                        <td>Mode Override</td>\n                        <td><input id=\"inputUriModeOverride\" type=\"checkbox\" name=\"" . $overrideSettingName . "\" " . $overrideStatusChecked . " class=\"urimgmt-toggle-switch\"></td>\n                        <td class=\"hidden-xs\">&nbsp;</td>\n                    </tr>\n                    <tr>\n                        <td>Mode Value</td>\n                        <td>\n                        <div class=\"text-center btn-group\">\n                          <button id=\"buttonUriPathMode\" type=\"button\" class=\"btn btn-default dropdown-toggle " . $overrideStatusDisabled . "\" data-toggle=\"dropdown\">\n                            <span class=\"selection\">" . $selectedMode . "</span>&nbsp;<span class=\"caret\"></span>\n                          </button>\n                          <ul class=\"dropdown-menu\" role=\"menu\">\n                            " . $uriPathModeHtml . "\n                          </ul>\n                        </div>\n\n                        </td>\n                        <td class=\"hidden-xs\">&nbsp;</td>\n                    </tr>\n                </tbody>\n            </table>\n        </div>\n    </div>\n</div>\n<hr/>\n<div class=\"container-fluid\">\n    <h2>Environment Mode Inspection</h2>\n    <p>Below is the results from the latest environment mode test of your installation.</p>\n    <div class=\"row\">\n        <div class=\"col-xs-6\">\n            <ul class=\"list-group\">\n            " . $supportedEnvironmentModes . "\n            </ul>\n        </div>\n    </div>\n</div>";
        $content .= $tab1 . $aInt->nextAdminTab();
        $tab2 = "<div class=\"container-fluid\">\n    <h2>Rewrite Management Overview</h2>\n    <p>" . $desc2 . "</p>\n    <div class=\"container-fluid\">\n        <div class=\"inset-grey-bg\">\n            <table class=\"table\">\n                <thead>\n                    <tr>\n                        <th>URI Rewrite Management</th>\n                        <th class=\"hidden-xs\"><th>\n                    </tr> \n                </thead>\n                <tbody>\n                    <tr>\n                        <td>Auto-Managed Rewrite</td>\n                        <td><input id=\"inputUriGlobalManagement\" type=\"checkbox\" name=\"" . $managementSettingName . "\" " . $managementStatus . " class=\"urimgmt-toggle-switch\"></td>\n                        <td class=\"hidden-xs\">&nbsp;</td>\n                    </tr>\n                    <tr>\n                        <td>WHMCS Rules<br/><span id=\"syncLabel\" class=\"label " . $syncLabelClasses . "\">" . $syncLabelText . "</span></td>\n                        <td><button id=\"btnRewriteRuleSync\" type=\"button\" class=\"btn " . $syncButtonClasses . "\">Synchronize</button></td>\n                        <td class=\"hidden-xs\">&nbsp;</td>\n                    </tr>\n                </tbody>\n            </table>\n        </div>\n    </div>\n</div>\n<hr/>\n<div class=\"container-fluid\">\n<h2>WHMCS Rules</h2>\n    <p>Below are the rewrite rules for this version of WHMCS, specific to your installation's root htaccess file: <var>" . $htaccessFile . "</var></p>\n    <pre id=\"preWhmcsRules\" class=\"pre-scrollable\">" . $whmcsRules . "</pre>\n</div>";
        $content .= $tab2 . $aInt->endAdminTabs();
        $token = generate_token("plain");
        $jQueryCodeForceUpdateMode = "WHMCS.http.jqClient.post(\n    'configurimgmt.php',\n    {\n        action: 'updateUriPathMode',\n        mode: '" . $effectiveMode . "',\n        token: '" . $token . "'\n    }\n)\n.done(function(data){\n    if (data.status != 'error') {\n        jQuery.growl.notice({ title: \"Auto-Detect & Save\", message: \"Updated URI Path Mode to " . $selectedMode . "\" });\n    }\n});";
        $jQueryCode = "jQuery(\".dropdown-menu li a\").click(function(e){\n    e.preventDefault();\n    \$(this).parents(\".btn-group\").find('.selection').text(\$(this).text());\n    var buttonValue = \$(this).attr('href');\n    var newMode = buttonValue.substring(1);\n    \n     WHMCS.http.jqClient.post(\n        'configurimgmt.php',\n        {\n            action: 'updateUriPathMode',\n            mode: newMode,\n            token: '" . $token . "'\n        }\n    )\n    .done(function(data){\n        if (data.status == 'okay') {\n            jQuery.growl.notice({ title: \"Setting Updated\", message: data.successMessage });\n        } else {\n            jQuery.growl.error({ title: \"Error\", message: data.errorMessage });\n        }\n    });\n});\n\njQuery(\".urimgmt-toggle-switch\").bootstrapSwitch(\n    {\n        'onColor': 'success',\n        'onSwitchChange': function(event, state)\n        {\n            WHMCS.http.jqClient.post(\n                'configurimgmt.php',\n                {\n                    action: 'toggle',\n                    state: state,\n                    setting: event.target['name'],\n                    token: '" . $token . "'\n                }\n            )\n            .done(function(data){\n                if (data.status == 'error') {\n                    jQuery.growl.error({ title: \"Error\", message: data.errorMessage });\n                } else {\n                    jQuery.growl.notice({ title: \"Setting Updated\", message: data.successMessage });\n                    if (event.target['name'] == 'UriModeOverride') {\n                        if (state) {\n                            jQuery(\"#buttonUriPathMode\").removeClass(\"disabled\");\n                        } else {\n                            jQuery(\"#buttonUriPathMode\").addClass(\"disabled\");\n                            var newModeRaw = data.data.mode;\n                            var modeAnchor = jQuery(\"#buttonUriPathMode\").parents(\".btn-group\").find('.dropdown-menu li a[href=\"#' + newModeRaw + '\"]');\n                            jQuery(\"#buttonUriPathMode\").parents(\".btn-group\").find('.selection').text(modeAnchor.text());\n                        }\n                    }\n                }\n            });\n        }\n    }\n);\n\njQuery('#btnRewriteRuleSync').click(function (event) {\n    event.preventDefault();\n    WHMCS.http.jqClient.post(\n        'configurimgmt.php',\n        {\n            action: 'synchronize',\n            token: '" . $token . "'\n        }\n    )\n    .done(function(data){\n        if (data.status == 'okay') {\n            jQuery(\"#btnRewriteRuleSync\")\n            .addClass(\"disabled btn-default\")\n            .removeClass(\"btn-info\");\n            jQuery(\"#syncLabel\")\n                .addClass(\"label-success\")\n                .removeClass(\"label-info label-danger\")\n                .text(data.state);\n             jQuery.growl.notice({ title: \"Completed\", message: data.successMessage });\n        } else {\n            jQuery.growl.error({ title: \"Error\", message: data.errorMessage });\n        }\n    });\n});";
        if ($request->getAttribute("modal-view", false)) {
            $aInt->setResponseType($aInt::RESPONSE_JSON_MODAL_MESSAGE);
        } else {
            $aInt->setResponseType($aInt::RESPONSE_HTML_MESSAGE);
        }
        $aInt->content = $content;
        $aInt->jquerycode = $jQueryCode;
        return $aInt->display();
    }
    public function synchronizeRules(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $rewriteFile = "";
        $result = array("status" => "okay", "successMessage" => "Rewrite Rules Updated", "state" => "Up To Date");
        try {
            $rewriteFile = \WHMCS\Route\Rewrite\File::factory(\WHMCS\Route\Rewrite\File::FILE_DEFAULT)->updateWhmcsRuleSet();
        } catch (\Exception $e) {
            logActivity("Failed to apply WHMCS rewrite rules per synchronization request");
            $result = array("status" => "error", "errorMessage" => $e->getMessage());
        }
        if ($rewriteFile) {
            $rewriteFile->flock(LOCK_UN);
            unset($rewriteFile);
        }
        return new \WHMCS\Http\Message\JsonResponse($result);
    }
}

?>