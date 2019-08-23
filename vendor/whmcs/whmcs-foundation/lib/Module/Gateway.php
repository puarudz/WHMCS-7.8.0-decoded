<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Gateway extends AbstractModule
{
    protected $type = self::TYPE_GATEWAY;
    protected $usesDirectories = false;
    protected $activeList = "";
    protected $legacyGatewayParams = array();
    const WORKFLOW_ASSISTED = "assisted";
    const WORKFLOW_REMOTE = "remote";
    const WORKFLOW_NOLOCALCARDINPUT = "nolocalcardinput";
    const WORKFLOW_TOKEN = "token";
    const WORKFLOW_MERCHANT = "merchant";
    const WORKFLOW_THIRDPARTY = "thirdparty";
    public function __construct()
    {
        $whmcs = \WHMCS\Application::getInstance();
        $this->addParam("companyname", $whmcs->get_config("CompanyName"));
        $this->addParam("systemurl", $whmcs->getSystemURL());
        $this->addParam("langpaynow", $whmcs->get_lang("invoicespaynow"));
        if (!function_exists("getGatewaysArray")) {
            $whmcs->load_function("gateway");
        }
    }
    public function getActiveModules()
    {
        return $this->getActiveGateways();
    }
    public function getList($type = "")
    {
        $modules = parent::getList($type);
        foreach ($modules as $key => $module) {
            if ($module == "index") {
                unset($modules[$key]);
            }
        }
        return $modules;
    }
    public static function factory($name)
    {
        $gateway = new Gateway();
        if (!$gateway->load($name)) {
            throw new \WHMCS\Exception\Fatal("Module Not Found");
        }
        if (!$gateway->isLoadedModuleActive()) {
            throw new \WHMCS\Exception\Fatal("Module Not Activated");
        }
        return $gateway;
    }
    public function getActiveGateways()
    {
        if (is_array($this->activeList)) {
            return $this->activeList;
        }
        $this->activeList = array();
        $result = select_query("tblpaymentgateways", "DISTINCT gateway", "`setting` NOT IN ('forcesubscriptions', 'forceonetime')");
        while ($data = mysql_fetch_array($result)) {
            $gateway = $data[0];
            if (\WHMCS\Gateways::isNameValid($gateway)) {
                $this->activeList[] = $gateway;
            }
        }
        return $this->activeList;
    }
    public function getMerchantGateways()
    {
        return \WHMCS\Database\Capsule::table("tblpaymentgateways")->distinct("gateway")->where("setting", "type")->where("value", "CC")->orderBy("gateway")->pluck("gateway");
    }
    public function isActiveGateway($gateway)
    {
        $gateways = $this->getActiveGateways();
        return in_array($gateway, $gateways);
    }
    public function getDisplayName()
    {
        if ($this->getLoadedModule()) {
            return (string) $this->getParam("name");
        }
        $paymentGateways = new \WHMCS\Gateways();
        return $paymentGateways->getDisplayName($this->loadedmodule);
    }
    public function getAvailableGateways($invoiceid = "")
    {
        $validgateways = array();
        $result = full_query("SELECT DISTINCT gateway, (SELECT value FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `name`, (SELECT `order` FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `order` FROM `tblpaymentgateways` g1 WHERE setting='visible' AND value='on' ORDER BY `order` ASC");
        while ($data = mysql_fetch_array($result)) {
            $validgateways[$data[0]] = $data[1];
        }
        if ($invoiceid) {
            $invoiceid = (int) $invoiceid;
            $invoicegateway = get_query_val("tblinvoices", "paymentmethod", array("id" => $invoiceid));
            $disabledgateways = array();
            $result = select_query("tblinvoiceitems", "", array("type" => "Hosting", "invoiceid" => $invoiceid));
            while ($data = mysql_fetch_assoc($result)) {
                $relid = $data["relid"];
                if ($relid) {
                    $result2 = full_query("SELECT pg.disabledgateways AS disabled FROM tblhosting h LEFT JOIN tblproducts p on h.packageid = p.id LEFT JOIN tblproductgroups pg on p.gid = pg.id where h.id = " . (int) $relid);
                    $data2 = mysql_fetch_assoc($result2);
                    $gateways = explode(",", $data2["disabled"]);
                    foreach ($gateways as $gateway) {
                        if (array_key_exists($gateway, $validgateways) && $gateway != $invoicegateway) {
                            unset($validgateways[$gateway]);
                        }
                    }
                }
            }
        }
        return $validgateways;
    }
    public function getFirstAvailableGateway()
    {
        $gateways = $this->getAvailableGateways();
        return key($gateways);
    }
    public function load($module, $globalVariable = NULL)
    {
        global $GATEWAYMODULE;
        $GATEWAYMODULE = array();
        $licensing = \DI::make("license");
        $module = \App::sanitize("0-9a-z_-", $module);
        $modulePath = $this->getModulePath($module);
        \Log::debug("Attempting to load module", array("type" => $this->getType(), "module" => $module, "path" => $modulePath));
        $loadStatus = false;
        if (file_exists($modulePath)) {
            if (!is_null($globalVariable)) {
                global ${$globalVariable};
            }
            if (!function_exists($module . "_config") && !function_exists($module . "_link") && !function_exists($module . "_capture")) {
                require_once $modulePath;
            }
            $this->setLoadedModule($module);
            $this->setMetaData($this->getMetaData());
            $loadStatus = true;
        }
        $this->legacyGatewayParams[$module] = $GATEWAYMODULE;
        if ($loadStatus) {
            $this->loadSettings();
        }
        $this->legacyGatewayFields = $GATEWAYMODULE;
        return $loadStatus;
    }
    public function loadSettings()
    {
        $gateway = $this->getLoadedModule();
        $settings = array("paymentmethod" => $gateway);
        $result = select_query("tblpaymentgateways", "", array("gateway" => $gateway));
        while ($data = mysql_fetch_array($result)) {
            $setting = $data["setting"];
            $value = $data["value"];
            $this->addParam($setting, $value);
            $settings[$setting] = $value;
        }
        return $settings;
    }
    public function isLoadedModuleActive()
    {
        return $this->getParam("type") ? true : false;
    }
    public function call($function, array $params = array())
    {
        $this->addParam("paymentmethod", $this->getLoadedModule());
        $userId = 0;
        if (array_key_exists("clientdetails", $params)) {
            $userId = $params["clientdetails"]["userid"];
        }
        if (!$userId) {
            $userId = \WHMCS\Session::get("uid");
        }
        $clientBeforeCall = \WHMCS\User\Client::find($userId);
        $result = parent::call($function, $params);
        if ($clientBeforeCall && in_array($function, array("capture", "3dsecure", "orderformcheckout"))) {
            $this->processClientAfterCall($clientBeforeCall, $params);
        }
        return $result;
    }
    private function migrateUpdatedCardData(\WHMCS\User\Client $client, \WHMCS\Payment\PayMethod\Model $payMethod)
    {
        if ($payMethod->payment instanceof \WHMCS\Payment\Contracts\CreditCardDetailsInterface) {
            $legacyCardData = getClientDefaultCardDetails($client->id, "forceLegacy");
            $payment = $payMethod->payment;
            if ($legacyCardData["cardnum"]) {
                $payment->setCardNumber($legacyCardData["cardnum"]);
            }
            if ($legacyCardData["cardlastfour"]) {
                $payment->setLastFour($legacyCardData["cardlastfour"]);
            }
            if ($legacyCardData["cardtype"]) {
                $payment->setCardType($legacyCardData["cardtype"]);
            }
            if ($legacyCardData["startdate"]) {
                $payment->setStartDate(\WHMCS\Carbon::createFromCcInput($legacyCardData["startdate"]));
            }
            if ($legacyCardData["expdate"]) {
                $payment->setExpiryDate(\WHMCS\Carbon::createFromCcInput($legacyCardData["expdate"]));
            }
            if ($legacyCardData["issuenumber"]) {
                $payment->setIssueNumber($legacyCardData["issuenumber"]);
            }
            $payment->save();
            $client->markCardDetailsAsMigrated();
        }
    }
    private function processClientAfterCall(\WHMCS\User\Client $clientBeforeCall, array $callParams)
    {
        $clientAfterCall = $clientBeforeCall->fresh();
        $invoiceModel = \WHMCS\Billing\Invoice::find($callParams["invoiceid"]);
        if (!$invoiceModel) {
            return NULL;
        }
        if (!$invoiceModel->payMethod || $invoiceModel->payMethod->trashed()) {
            return NULL;
        }
        if ($clientAfterCall->paymentGatewayToken !== $clientBeforeCall->paymentGatewayToken && $invoiceModel->payMethod->payment instanceof \WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
            if ($clientAfterCall->paymentGatewayToken) {
                $payment = $invoiceModel->payMethod->payment;
                $payment->setRemoteToken($clientAfterCall->paymentGatewayToken);
                $payment->save();
                $clientAfterCall->paymentGatewayToken = "";
                $clientAfterCall->save();
            } else {
                $invoiceModel->payMethod->delete();
            }
        }
        if ($clientAfterCall->creditCardType !== $clientBeforeCall->creditCardType) {
            if (!empty($clientAfterCall->creditCardType)) {
                $this->migrateUpdatedCardData($clientAfterCall, $invoiceModel->payMethod);
            } else {
                if (!$clientAfterCall->paymentGatewayToken) {
                    $invoiceModel->payMethod->delete();
                }
            }
        }
    }
    public function activate(array $parameters = array())
    {
        if ($this->isLoadedModuleActive()) {
            throw new \WHMCS\Exception\Module\NotActivated("Module already active");
        }
        $lastOrder = (int) get_query_val("tblpaymentgateways", "`order`", array("setting" => "name", "gateway" => $this->getLoadedModule()), "order", "DESC");
        if (!$lastOrder) {
            $lastOrder = (int) get_query_val("tblpaymentgateways", "`order`", "", "order", "DESC");
            $lastOrder++;
        }
        $configData = $this->getConfiguration();
        $displayName = $configData["FriendlyName"]["Value"];
        $gatewayType = $this->functionExists("capture") ? "CC" : "Invoices";
        $this->saveConfigValue("name", $displayName, $lastOrder);
        $this->saveConfigValue("type", $gatewayType);
        $this->saveConfigValue("visible", "on");
        if ($configData["RemoteStorage"]) {
            $this->saveConfigValue("remotestorage", "1");
        }
        $hookFile = $this->getModuleDirectory($this->getLoadedModule()) . DIRECTORY_SEPARATOR . "hooks.php";
        if (file_exists($hookFile)) {
            $hooks = array_filter(explode(",", \WHMCS\Config\Setting::getValue("GatewayModuleHooks")));
            if (!in_array($this->getLoadedModule(), $hooks)) {
                $hooks[] = $this->getLoadedModule();
            }
            \WHMCS\Config\Setting::setValue("GatewayModuleHooks", implode(",", $hooks));
        }
        if (!function_exists("logAdminActivity")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php";
        }
        logAdminActivity("Gateway Module Activated: '" . $displayName . "'");
        $this->load($this->getLoadedModule());
        $this->updateConfiguration($parameters);
        return true;
    }
    public function deactivate(array $parameters = array())
    {
        if (!$this->isLoadedModuleActive()) {
            throw new \WHMCS\Exception\Module\NotActivated("Module not active");
        }
        if (empty($parameters["newGateway"])) {
            throw new \WHMCS\Exception\Module\NotServicable("New Module Required");
        }
        if ($this->getLoadedModule() != $parameters["newGateway"]) {
            $tables = array("tblaccounts", "tbldomains", "tblhosting", "tblhostingaddons", "tblinvoices", "tblorders");
            foreach ($tables as $table) {
                $field = "paymentmethod";
                if ($table == "tblaccounts") {
                    $field = "gateway";
                }
                \WHMCS\Database\Capsule::table($table)->where($field, $this->getLoadedModule())->update(array($field => $parameters["newGateway"]));
            }
            $configData = $this->getConfiguration();
            $displayName = $configData["FriendlyName"]["Value"];
            \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $this->getLoadedModule())->delete();
            $hooks = array_filter(explode(",", \WHMCS\Config\Setting::getValue("GatewayModuleHooks")));
            if (in_array($this->getLoadedModule(), $hooks)) {
                $hooks = array_flip($hooks);
                unset($hooks[$this->getLoadedModule()]);
                $hooks = array_flip($hooks);
                \WHMCS\Config\Setting::setValue("GatewayModuleHooks", implode(",", $hooks));
            }
            if (!function_exists("logAdminActivity")) {
                require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php";
            }
            logAdminActivity("Gateway Module Deactivated: '" . $displayName . "'" . " to '" . $parameters["newGatewayName"] . "'");
            return true;
        } else {
            throw new \WHMCS\Exception\Module\NotImplemented("Invalid New Module");
        }
    }
    protected function saveConfigValue($setting, $value, $order = 0)
    {
        delete_query("tblpaymentgateways", array("gateway" => $this->getLoadedModule(), "setting" => $setting));
        insert_query("tblpaymentgateways", array("gateway" => $this->getLoadedModule(), "setting" => $setting, "value" => $value, "order" => $order));
        $this->addParam($setting, $value);
    }
    public function getConfiguration()
    {
        if (!$this->getLoadedModule()) {
            throw new \WHMCS\Exception("No module loaded to fetch configuration for");
        }
        if ($this->functionExists("config")) {
            return $this->call("config");
        }
        if ($this->functionExists("activate")) {
            $module = $this->getLoadedModule();
            $legacyDisplayName = isset($this->legacyGatewayParams[$module][$module . "visiblename"]) ? $this->legacyGatewayParams[$module][$module . "visiblename"] : ucfirst($module);
            $legacyNotes = isset($this->legacyGatewayParams[$module][$module . "notes"]) ? $this->legacyGatewayParams[$module][$module . "notes"] : "";
            $this->call("activate");
            $response = array_merge(array("FriendlyName" => array("Type" => "System", "Value" => $legacyDisplayName)), defineGatewayFieldStorage(true));
            if (!empty($legacyNotes)) {
                $response["UsageNotes"] = array("Type" => "System", "Value" => $legacyNotes);
            }
            return $response;
        }
        throw new \WHMCS\Exception\Module\NotImplemented();
    }
    public function updateConfiguration(array $parameters = array())
    {
        if (!$this->isLoadedModuleActive()) {
            throw new \WHMCS\Exception\Module\NotActivated("Module not active");
        }
        if (0 < count($parameters)) {
            $configData = $this->getConfiguration();
            $displayName = $configData["FriendlyName"]["Value"];
            foreach ($parameters as $key => $value) {
                if (array_key_exists($key, $configData)) {
                    $this->saveConfigValue($key, $value);
                }
            }
            if (!function_exists("logAdminActivity")) {
                require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php";
            }
            logAdminActivity("Gateway Module Configuration Updated: '" . $displayName . "'");
        }
    }
    public function getAdminActivationForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configgateways.php")->setMethod(\WHMCS\View\Form::METHOD_POST)->setParameters(array("token" => generate_token("plain"), "action" => "activate", "gateway" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.activate")));
    }
    public function getAdminManagementForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configgateways.php")->setMethod(\WHMCS\View\Form::METHOD_POST)->setParameters(array("manage" => true, "gateway" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.manage")));
    }
    public function getOnBoardingRedirectHtml()
    {
        if (!$this->getMetaDataValue("apiOnboarding")) {
            return "";
        }
        $redirectUrl = $this->getMetaDataValue("apiOnboardingRedirectUrl");
        $callbackPath = $this->getMetaDataValue("apiOnboardingCallbackPath");
        $admin = \WHMCS\User\Admin::getAuthenticatedUser();
        $params = array("firstname" => $admin->firstName, "lastname" => $admin->lastName, "companyname" => \WHMCS\Config\Setting::getValue("CompanyName"), "email" => $admin->email, "whmcs_callback_url" => \App::getSystemUrl() . $callbackPath, "return_url" => fqdnRoutePath("admin-setup-payments-gateways-onboarding-return"));
        $buttonValue = "Click here if not redirected automatically";
        $output = "<html><head><title>Redirecting...</title></head>" . "<body onload=\"document.onboardfrm.submit()\">" . "<p>Please wait while you are redirected...</p>" . "<form method=\"post\" action=\"" . $redirectUrl . "\" name=\"onboardfrm\">";
        foreach ($params as $key => $value) {
            $output .= "<input type=\"hidden\" name=\"" . $key . "\" value=\"" . \WHMCS\Input\Sanitize::makeSafeForOutput($value) . "\">";
        }
        $output .= "<input type=\"submit\" value=\"" . $buttonValue . "\" class=\"btn btn-default\">" . "</form>" . "</body></html>";
        return $output;
    }
    public function getWorkflowType()
    {
        if ($this->functionExists("credit_card_input")) {
            return static::WORKFLOW_ASSISTED;
        }
        if ($this->functionExists("remoteinput")) {
            return static::WORKFLOW_REMOTE;
        }
        if ($this->functionExists("nolocalcc")) {
            return static::WORKFLOW_NOLOCALCARDINPUT;
        }
        if ($this->functionExists("storeremote")) {
            return static::WORKFLOW_TOKEN;
        }
        if ($this->functionExists("capture")) {
            return static::WORKFLOW_MERCHANT;
        }
        return static::WORKFLOW_THIRDPARTY;
    }
    public function isTokenised()
    {
        $tokenizedWorkflows = array(static::WORKFLOW_ASSISTED, static::WORKFLOW_REMOTE, static::WORKFLOW_NOLOCALCARDINPUT, static::WORKFLOW_TOKEN);
        return in_array($this->getWorkflowType(), $tokenizedWorkflows);
    }
    public function supportsLocalBankDetails()
    {
        return $this->functionExists("localbankdetails");
    }
    public function supportsAutoCapture()
    {
        return $this->functionExists("capture");
    }
    public function getBaseGatewayType()
    {
        $type = "3rdparty";
        if ($this->supportsAutoCapture()) {
            $type = "creditcard";
        }
        if ($this->supportsLocalBankDetails()) {
            $type = "bankaccount";
        }
        return $type;
    }
}

?>