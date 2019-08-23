<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup;

class SetupTasks
{
    public function evaluateAndGet()
    {
        return array(array("label" => \AdminLang::trans("setupTask.general"), "link" => "configgeneral.php", "completed" => $this->isCompanyNameAndLogoSet()), array("label" => \AdminLang::trans("setupTask.automation"), "link" => "configauto.php", "completed" => (new \WHMCS\Cron())->hasCronEverBeenInvoked()), array("label" => \AdminLang::trans("setupTask.gateways"), "link" => "configgateways.php", "completed" => 0 < count((new \WHMCS\Module\Gateway())->getActiveGateways())), array("label" => \AdminLang::trans("setupTask.merchant"), "link" => "configgateways.php?type=merchant", "completed" => 0 < count((new \WHMCS\Module\Gateway())->getMerchantGateways())), array("label" => \AdminLang::trans("setupTask.registrars"), "link" => "configregistrars.php", "completed" => 0 < count((new \WHMCS\Module\Registrar())->getActiveModules())), array("label" => \AdminLang::trans("setupTask.product"), "link" => "configproducts.php", "completed" => 0 < \WHMCS\Product\Product::count()), array("label" => \AdminLang::trans("setupTask.support"), "link" => "configticketdepartments.php", "completed" => 0 < \WHMCS\Support\Department::count()), array("label" => \AdminLang::trans("setupTask.notifications"), "link" => routePath("admin-setup-notifications-overview"), "completed" => 0 < \WHMCS\Notification\Rule::active()->count()), array("label" => \AdminLang::trans("setupTask.marketconnectSSL"), "link" => "marketconnect.php?learnmore=symantec", "completed" => \WHMCS\MarketConnect\MarketConnect::isActive("symantec")), array("label" => \AdminLang::trans("setupTask.marketconnectWeebly"), "link" => "marketconnect.php?learnmore=weebly", "completed" => \WHMCS\MarketConnect\MarketConnect::isActive("weebly")), array("label" => \AdminLang::trans("setupTask.marketconnectSitelock"), "link" => "marketconnect.php?learnmore=sitelock", "completed" => \WHMCS\MarketConnect\MarketConnect::isActive("sitelock")), array("label" => \AdminLang::trans("setupTask.marketconnectSpam"), "link" => "marketconnect.php?learnmore=spamexperts", "completed" => \WHMCS\MarketConnect\MarketConnect::isActive("spamexperts")), array("label" => \AdminLang::trans("setupTask.signInIntegrations"), "link" => routePath("admin-setup-authn-view"), "completed" => 0 < \WHMCS\Authentication\Remote\ProviderSetting::enabled()->count()), array("label" => \AdminLang::trans("setupTask.applicationLinks"), "link" => "configapplinks.php", "completed" => 0 < \WHMCS\ApplicationLink\ApplicationLink::where("module_type", "servers")->where("module_name", "cpanel")->where("is_enabled", 1)->count()), array("label" => \AdminLang::trans("setupTask.backups"), "link" => "configbackups.php", "completed" => 0 < count((new \WHMCS\Backups\Backups())->getActiveProviders())));
    }
    protected function isCompanyNameAndLogoSet()
    {
        return \WHMCS\Config\Setting::getValue("CompanyName") != "Company Name" && \WHMCS\Config\Setting::getValue("LogoURL");
    }
}

?>