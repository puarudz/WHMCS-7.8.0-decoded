<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version760alpha1 extends IncrementalVersion
{
    protected $updateActions = array("removeUnusedLegacyModules", "conditionallyUpdate2CheckoutVariables", "addServiceRenewalFailedAdminEmailTemplate", "addDomainTransferCompletedEmailTemplate", "conditionallyCreateMaxMindPaymentTable", "conditionallyActivateNewWeeblyPlans", "updateWhmcsWhoisToWhmcsDomains", "conditionallyUpdateMaxMindTableValues");
    private function getUnusedLegacyModules()
    {
        return array("gateways" => array("amazonsimplepay"), "registrars" => array("distributeit"), "servers" => array("globalsignvouchers"));
    }
    protected function removeUnusedLegacyModules()
    {
        (new \WHMCS\Module\LegacyModuleCleanup())->removeModulesIfInstalledAndUnused($this->getUnusedLegacyModules());
        return $this;
    }
    protected function addServiceRenewalFailedAdminEmailTemplate()
    {
        $mailTemplate = \WHMCS\Mail\Template::where("name", "Service Renewal Failed")->first();
        if (!$mailTemplate) {
            $template = new \WHMCS\Mail\Template();
            $template->name = "Service Renewal Failed";
            $template->subject = "WHMCS Service Renewal Failed";
            $template->type = "admin";
            $template->message = "<p>\n    An automatic renewal attempt was triggered for this service but failed. The renewal will not be attempted again automatically. Please resolve the error and try again.\n</p>\n<p>\n    Client ID: {\$client_id}<br />\n    Service ID: {\$service_id}<br />\n    Product/Service: {\$service_product}<br />\n    Domain: {\$service_domain}<br />{if \$addon_id}\n    Addon ID: {\$addon_id}<br />\n    Addon: {\$addon_name}<br />\n    {/if}Error: {\$error_msg}\n</p>\n<p>\n    <a href=\"{\$whmcs_admin_url}/clientsservices.php?userid={\$client_id}&id={\$service_id}{if \$addon_id}&aid={\$addon_id}{/if}\">\n        Go to {if \$addon_id}addon{else}service{/if}\n    </a>\n</p>";
            $template->save();
        }
        return $this;
    }
    protected function addDomainTransferCompletedEmailTemplate()
    {
        $mailTemplate = \WHMCS\Mail\Template::where("name", "Domain Transfer Completed")->first();
        if (!$mailTemplate) {
            $template = new \WHMCS\Mail\Template();
            $template->name = "Domain Transfer Completed";
            $template->subject = "Transfer Completed for {\$domain_name}";
            $template->type = "domain";
            $template->message = "<p>Dear {\$client_name},</p>\n<p>We are pleased to confirm that your recent domain transfer has now been completed.</p>\n<p>Order Date: {\$domain_reg_date}<br />\nDomain: {\$domain_name}<br />\nStatus: {\$domain_status}</p>\n<p>You may now login to your client area at {\$whmcs_link} to manage your domain.</p>\n<p>{\$signature}</p>";
            $template->save();
        }
        return $this;
    }
    protected function conditionallyUpdate2CheckoutVariables()
    {
        $existingConfiguration = \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", "tco")->whereIn("setting", array("disablerecur", "forcerecur", "purchaseroutine"));
        $integrationMethod = "standard";
        $recurringBilling = "";
        foreach ($existingConfiguration->get() as $existingConfig) {
            switch ($existingConfig->setting) {
                case "purchaseroutine":
                    if ($existingConfig->value == "on") {
                        $integrationMethod = "legacy";
                    }
                    break;
                case "forcerecur":
                    if (!$recurringBilling && $existingConfig->value == "on") {
                        $recurringBilling = "forcerecur";
                    }
                    break;
                case "disablerecur":
                    if (!$recurringBilling && $existingConfig->value == "on") {
                        $recurringBilling = "disablerecur";
                    }
                    break;
            }
        }
        $existingConfiguration->delete();
        \WHMCS\Database\Capsule::table("tblpaymentgateways")->insert(array("gateway" => "tco", "setting" => "integrationMethod", "value" => $integrationMethod));
        \WHMCS\Database\Capsule::table("tblpaymentgateways")->insert(array("gateway" => "tco", "setting" => "recurringBilling", "value" => $recurringBilling));
        return $this;
    }
    protected function conditionallyCreateMaxMindPaymentTable()
    {
        $enabledModule = \WHMCS\Database\Capsule::table("tblfraud")->where("setting", "Enable")->where("value", "on")->value("fraud");
        if ($enabledModule && $enabledModule == "maxmind") {
            (new \WHMCS\Module\Fraud\MaxMind\Payment())->createTable();
        }
        return $this;
    }
    protected function conditionallyActivateNewWeeblyPlans()
    {
        $weeblyServices = \WHMCS\Database\Capsule::table("tblmarketconnect_services")->where("name", "weebly")->where("status", 1);
        if ($weeblyServices->first()) {
            $weeblyServices->update(array("product_ids" => "weebly_lite,weebly_starter,weebly_pro,weebly_business,weebly_performance"));
            \WHMCS\Database\Capsule::table("tblproducts")->where("servertype", "marketconnect")->where("configoption1", "like", "weebly_%")->increment("order", 1);
            \WHMCS\Database\Capsule::table("tbladdons")->join("tblmodule_configuration", function (\Illuminate\Database\Query\JoinClause $join) {
                $join->where("entity_type", "=", "addon")->where("setting_name", "=", "configoption1")->where("value", "like", "weebly_%")->on("tbladdons.id", "=", "tblmodule_configuration.entity_id");
            })->where("module", "marketconnect")->increment("weight", 1);
            $previousEnableTranslationsValue = \WHMCS\Config\Setting::getValue("EnableTranslations");
            \WHMCS\Config\Setting::setValue("EnableTranslations", "");
            try {
                $marketConnect = new \WHMCS\MarketConnect\MarketConnect();
                $marketConnect->createProductsFromApiResponse($this->weeblyNewProducts());
            } catch (\Exception $e) {
            }
            \WHMCS\Config\Setting::setValue("EnableTranslations", $previousEnableTranslationsValue);
            \WHMCS\MarketConnect\ServicesFeed::removeCache();
        }
        return $this;
    }
    private function weeblyNewProducts()
    {
        return json_decode("[{\"name\":\"Weebly Website Builder\",\"headline\":\"Building a Website Has Never Been Easier\"" . ",\"tagline\":\"Create the perfect site with powerful drag and drop tools\",\"products\":" . "[{\"type\":\"other\",\"name\":\"Lite\",\"description\":\"Weebly\\u2019s drag and drop website" . " builder makes it easy to create a powerful, professional website without any technical" . " skills. Try Weebly and create a 1 page site with this Lite entry level option.\"" . ",\"welcomeEmailName\":\"\",\"paymentType\":\"recurring\",\"autoSetup\":\"payment\",\"module\":\"marketconnect\"," . "\"moduleConfigOptions\":{\"1\":\"weebly_lite\",\"2\":\"\"},\"displayOrder\":1,\"isFeatured\":false," . "\"pricing\":{\"monthly\":{\"setup\":0,\"price\":1.99},\"quarterly\":{\"setup\":0,\"price\":5.85}," . "\"semiannually\":{\"setup\":0,\"price\":11.49},\"annually\":{\"setup\":0,\"price\":22.49},\"biennially\"" . ":{\"setup\":0,\"price\":43.99},\"triennially\":{\"setup\":0,\"price\":63.99}},\"addonLinkCriteria\":" . "{\"type\":[\"hostingaccount\"]}},{\"type\":\"other\",\"name\":\"Performance\",\"description\":" . "\"Weebly\\u2019s drag and drop website builder makes it easy to create a powerful" . ", professional website without any technical skills. The Performance plan offers more" . " features and scalability. Perfect for Power Sellers.\",\"welcomeEmailName\":\"\"," . "\"paymentType\":\"recurring\",\"autoSetup\":\"payment\",\"module\":\"marketconnect\"," . "\"moduleConfigOptions\":{\"1\":\"weebly_performance\",\"2\":\"\"},\"displayOrder\":5,\"isFeatured\":" . "false,\"pricing\":{\"monthly\":{\"setup\":0,\"price\":46.99},\"quarterly\":{\"setup\":0,\"price\":136.50}," . "\"semiannually\":{\"setup\":0,\"price\":264},\"annually\":{\"setup\":0,\"price\":468},\"biennially\"" . ":{\"setup\":0,\"price\":936},\"triennially\":{\"setup\":0,\"price\":1404}},\"addonLinkCriteria\":" . "{\"type\":[\"hostingaccount\"]}}]}]", true);
    }
    protected function updateWhmcsWhoisToWhmcsDomains()
    {
        $query = \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "domainLookupProvider");
        if (!$query->count()) {
            \WHMCS\Database\Capsule::table("tblconfiguration")->insert(array("setting" => "domainLookupProvider", "value" => "WhmcsDomains"));
        } else {
            $query = \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "domainLookupProvider")->whereIn("value", array("WhmcsWhois", "", "BasicWhois"));
            if ($query->count()) {
                $query->update(array("value" => "WhmcsDomains"));
            }
        }
        return $this;
    }
    protected function conditionallyUpdateMaxMindTableValues()
    {
        $maxMindModule = \WHMCS\Database\Capsule::table("tblfraud")->where("fraud", "maxmind");
        $settings = $maxMindModule->pluck("setting");
        if (count($settings)) {
            if (!in_array("userId", $settings)) {
                \WHMCS\Database\Capsule::table("tblfraud")->insert(array("fraud" => "maxmind", "setting" => "userId", "value" => ""));
            }
            if (!in_array("serviceType", $settings)) {
                \WHMCS\Database\Capsule::table("tblfraud")->insert(array("fraud" => "maxmind", "setting" => "serviceType", "value" => "Score"));
            }
            if (!in_array("ignoreAddressValidation", $settings)) {
                \WHMCS\Database\Capsule::table("tblfraud")->insert(array("fraud" => "maxmind", "setting" => "ignoreAddressValidation", "value" => ""));
            }
        }
    }
}

?>