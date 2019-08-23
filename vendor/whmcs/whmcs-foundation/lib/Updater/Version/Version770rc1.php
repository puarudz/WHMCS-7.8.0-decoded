<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version770rc1 extends IncrementalVersion
{
    protected $updateActions = array("removeUnusedLegacyModules");
    private function getUnusedLegacyModules()
    {
        return array("gateways" => array("eeecurrency"), "servers" => array("lxadmin", "veportal", "xpanel"), "registrars" => array("ovh", "resellone", "dotdns"));
    }
    protected function removeUnusedLegacyModules()
    {
        (new \WHMCS\Module\LegacyModuleCleanup())->removeModulesIfInstalledAndUnused($this->getUnusedLegacyModules());
        return $this;
    }
}

?>