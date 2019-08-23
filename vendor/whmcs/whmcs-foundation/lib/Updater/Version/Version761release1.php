<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version761release1 extends IncrementalVersion
{
    protected $updateActions = array("correctWhmcsWhoisToWhmcsDomains");
    protected function correctWhmcsWhoisToWhmcsDomains()
    {
        $query = \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "domainLookupProvider");
        if (!$query->count()) {
            \WHMCS\Config\Setting::setValue("domainLookupProvider", "WhmcsDomains");
        } else {
            $settingNotConverted = \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "domainLookupProvider")->whereIn("value", array("BasicWhois", "WhmcsWhois", ""))->where("updated_at", "<", "2018-06-28 00:00:00")->first();
            if ($settingNotConverted) {
                \WHMCS\Config\Setting::setValue("domainLookupProvider", "WhmcsDomains");
            }
        }
        return $this;
    }
}

?>