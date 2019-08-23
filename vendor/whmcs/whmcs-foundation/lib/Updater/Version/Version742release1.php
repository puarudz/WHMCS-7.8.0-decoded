<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version742release1 extends IncrementalVersion
{
    protected $updateActions = array("removeComposerInstallUpdateHooks", "addPTtoccTLDs");
    protected function removeComposerInstallUpdateHooks()
    {
        $directoryToClean = ROOTDIR . str_replace("/", DIRECTORY_SEPARATOR, "/vendor/whmcs/whmcs-foundation/lib/Installer/Composer/Hooks");
        if (is_dir($directoryToClean)) {
            \WHMCS\Utility\File::recursiveDelete($directoryToClean);
        }
    }
    protected function addPTtoccTLDs()
    {
        $arrayTopLevelDomainsAndCategories = array("pt" => array("ccTLD"), "com.pt" => array("ccTLD"), "edu.pt" => array("ccTLD"));
        return $this->addDomainsToCategories(json_encode($arrayTopLevelDomainsAndCategories));
    }
}

?>