<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version720beta3 extends IncrementalVersion
{
    protected $updateActions = array("addMissingDotToSubDomainProductOptions", "addAddonIdFieldToModLicensingIfLicensingAddonEnabled", "addAutoLinkCriteriaToMarketConnectAddons");
    protected function addMissingDotToSubDomainProductOptions()
    {
        $products = \WHMCS\Product\Product::where("subdomain", "!=", "")->get();
        foreach ($products as $product) {
            $original = $product->freeSubDomains;
            $modified = \WHMCS\Admin\Setup\ProductSetup::formatSubDomainValuesToEnsureLeadingDotAndUnique($original);
            if ($original !== $modified) {
                \WHMCS\Database\Capsule::table($product->getTable())->where("id", $product->id)->update(array("subdomain" => implode(",", $modified)));
            }
        }
        return $this;
    }
    protected function addAddonIdFieldToModLicensingIfLicensingAddonEnabled()
    {
        $isModuleEnabled = \WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "licensing")->count();
        if (0 < $isModuleEnabled && \WHMCS\Database\Capsule::schema()->hasTable("mod_licensing") && !\WHMCS\Database\Capsule::schema()->hasColumn("mod_licensing", "addon_id")) {
            \WHMCS\Database\Capsule::schema()->table("mod_licensing", function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->unsignedInteger("addon_id")->default(0)->after("serviceid");
            });
        }
    }
    protected function addAutoLinkCriteriaToMarketConnectAddons()
    {
        \WHMCS\Database\Capsule::table("tbladdons")->where("module", "=", "marketconnect")->where("name", "like", "SSL Certificates%")->update(array("autolinkby" => "{\"type\":[\"hostingaccount\",\"reselleraccount\",\"server\"]}"));
        \WHMCS\Database\Capsule::table("tbladdons")->where("module", "=", "marketconnect")->where("name", "like", "Weebly Website Builder%")->update(array("autolinkby" => "{\"type\":[\"hostingaccount\"]}"));
        \WHMCS\Database\Capsule::table("tbladdons")->where("module", "=", "marketconnect")->where("name", "like", "Email Spam Filtering%")->update(array("autolinkby" => "{\"type\":[\"hostingaccount\",\"reselleraccount\"]}"));
    }
}

?>