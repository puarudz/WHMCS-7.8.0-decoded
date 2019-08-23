<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Addon\Mailchimp;

class Controller
{
    protected $api = NULL;
    public function __construct()
    {
        $this->api = new Api();
    }
    public function index($vars)
    {
        $helper = new SettingsHelper($vars);
        if (!$helper->get("apiKey")) {
            return $this->setup($vars);
        }
        try {
            $response = $this->api->setApiKey($helper->get("apiKey"))->info();
            $accountName = $response["account_name"];
            $accountEmail = $response["email"];
            $accountUsername = $response["username"];
            $totalSubscribers = $response["total_subscribers"];
        } catch (Exceptions\InvalidApiKey $e) {
            return $this->settings($vars, false, "Your API Key appears to have become invalid. Please update it to continue.");
        } catch (\WHMCS\Exception $e) {
            return $this->settings($vars, false, "Unable to communicate with the Mailchimp API: " . $e->getMessage());
        }
        return array("action" => "index", "accountUsername" => $accountUsername);
    }
    public function settings($vars, $saveSuccess = false, $errorMsg = "")
    {
        $helper = new SettingsHelper($vars);
        if (!$helper->get("apiKey")) {
            return $this->index($vars);
        }
        return array("action" => "settings", "saveSuccess" => $saveSuccess, "errorMsg" => $errorMsg, "connectedListName" => $helper->get("primaryListName"), "requireUserOptIn" => \WHMCS\Config\Setting::getValue("EmailMarketingRequireOptIn"), "optInAgreementMsg" => \WHMCS\Config\Setting::getValue("EmailMarketingOptInMessage") ?: "I would like to be kept up-to-date with news, information and special offers");
    }
    public function savesettings($vars)
    {
        $helper = new SettingsHelper($vars);
        $apiKey = $helper->request("api_key");
        $requireUserOptIn = $helper->request("require_user_optin");
        $optInAgreementMsg = $helper->request("optin_agreement_msg");
        try {
            if ($apiKey) {
                $this->api->setApiKey($apiKey);
                $this->api->info();
                $helper->set("apiKey", $apiKey);
            }
            \WHMCS\Config\Setting::setValue("AllowClientsEmailOptOut", 1);
            \WHMCS\Config\Setting::setValue("EmailMarketingRequireOptIn", (int) $requireUserOptIn);
            \WHMCS\Config\Setting::setValue("EmailMarketingOptInMessage", $optInAgreementMsg);
            $vars["requireUserOptIn"] = (bool) $requireUserOptIn;
            $vars["optInAgreementMsg"] = $optInAgreementMsg;
            return $this->settings($vars, true);
        } catch (\WHMCS\Exception $e) {
            return $this->settings($vars, false, $e->getMessage());
        }
    }
    public function setup($vars)
    {
        return $this->apikeyinput($vars);
    }
    protected function apikeyinput($vars, $errorMsg = NULL)
    {
        return array("action" => "setup", "errorMsg" => $errorMsg);
    }
    public function validateapikey($vars)
    {
        $helper = new SettingsHelper($vars);
        $apiKey = $helper->request("api_key");
        if (!$apiKey) {
            $errorMsg = "API Key is required";
            return $this->apikeyinput($vars, $errorMsg);
        }
        try {
            $this->api->setApiKey($apiKey);
            $helper->set("apiKey", $apiKey);
            $vars["apiKey"] = $apiKey;
            return $this->showlistchoice($vars);
        } catch (\Exception $e) {
            return $this->apikeyinput($vars, $e->getMessage());
        }
    }
    protected function showlistchoice($vars, $errorMsg = NULL)
    {
        $helper = new SettingsHelper($vars);
        $primaryList = $helper->request("primary_list");
        $newListName = $helper->request("new_list_name");
        $fromEmail = $helper->request("from_email");
        $fromName = $helper->request("from_name");
        $permissionReminder = $helper->request("permission_reminder");
        $contactCompany = $helper->request("contact_company");
        $contactAddr1 = $helper->request("contact_addr1");
        $contactCity = $helper->request("contact_city");
        $contactState = $helper->request("contact_state");
        $contactZip = $helper->request("contact_zip");
        $contactCountry = $helper->request("contact_country");
        if (!$fromEmail) {
            $fromEmail = \WHMCS\Config\Setting::getValue("Email");
        }
        if (!$fromName) {
            $fromName = \WHMCS\Config\Setting::getValue("CompanyName");
        }
        if (!$contactCompany) {
            $contactCompany = \WHMCS\Config\Setting::getValue("CompanyName");
        }
        if (!$contactCountry) {
            $contactCountry = \WHMCS\Config\Setting::getValue("DefaultCountry");
        }
        $lists = $this->api->setApiKey($vars["apiKey"])->getLists();
        return array("action" => "chooselist", "errorMsg" => $errorMsg, "lists" => $lists["lists"], "primaryList" => $primaryList, "newListName" => $newListName, "fromEmail" => $fromEmail, "fromName" => $fromName, "permissionReminder" => $permissionReminder, "contactCompany" => $contactCompany, "contactAddr1" => $contactAddr1, "contactCity" => $contactCity, "contactState" => $contactState, "contactZip" => $contactZip, "contactCountry" => $contactCountry, "countries" => (new \WHMCS\Utility\Country())->getCountryNameArray());
    }
    public function validateprimarylist($vars)
    {
        $helper = new SettingsHelper($vars);
        $primaryList = $helper->request("primary_list");
        $newListName = $helper->request("new_list_name");
        $fromEmail = $helper->request("from_email");
        $fromName = $helper->request("from_name");
        $permissionReminder = $helper->request("permission_reminder");
        $contactCompany = $helper->request("contact_company");
        $contactAddr1 = $helper->request("contact_addr1");
        $contactCity = $helper->request("contact_city");
        $contactState = $helper->request("contact_state");
        $contactZip = $helper->request("contact_zip");
        $contactCountry = $helper->request("contact_country");
        if ($primaryList == "new") {
            try {
                $createResponse = $this->api->setApiKey($vars["apiKey"])->createList($newListName, $fromEmail, $fromName, $permissionReminder, $contactCompany, $contactAddr1, $contactCity, $contactState, $contactZip, $contactCountry);
                $primaryListId = $createResponse["id"];
                $primaryListName = $newListName;
            } catch (\WHMCS\Exception $e) {
                return $this->showlistchoice($vars, $e->getMessage());
            }
        } else {
            $primaryList = explode("-", $primaryList, 2);
            list($primaryListId, $primaryListName) = $primaryList;
        }
        $helper->set("primaryListId", $primaryListId);
        $helper->set("primaryListName", $primaryListName);
        $this->api->setApiKey($vars["apiKey"]);
        $createNewStore = false;
        if ($existingStoreListId = $this->api->getWhmcsStoreListId()) {
            if ($existingStoreListId === $primaryListId) {
            } else {
                $this->api->deleteStore();
                $createNewStore = true;
            }
        } else {
            $createNewStore = true;
        }
        if ($createNewStore) {
            $currency = \WHMCS\Billing\Currency::defaultCurrency()->first();
            $this->api->createStore($primaryListId, $currency->code, $currency->prefix);
        }
        return $this->sync($vars);
    }
    public function sync($vars)
    {
        return array("action" => "sync");
    }
    public function runsync($vars)
    {
        try {
            $mailchimp = new Mailchimp();
            $mailchimp->syncProducts();
            return array("ajax" => true, "success" => true);
        } catch (\WHMCS\Exception $e) {
            return array("ajax" => true, "success" => false, "error" => $e->getMessage());
        }
    }
    public function disconnect($vars)
    {
        $helper = new SettingsHelper($vars);
        if (!$helper->get("apiKey")) {
            return $this->index($vars);
        }
        return array();
    }
    public function disconnectconfirm($vars)
    {
        $helper = new SettingsHelper($vars);
        $helper->set("apiKey", "");
        $helper->set("primaryListId", "");
        $vars["apiKey"] = "";
        return $this->index($vars);
    }
}

?>