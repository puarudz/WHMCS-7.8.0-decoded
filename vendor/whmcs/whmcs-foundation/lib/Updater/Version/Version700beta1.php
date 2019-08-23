<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version700beta1 extends IncrementalVersion
{
    protected $updateActions = array("removeLegacyClassLocations");
    public function removeLegacyClassLocations()
    {
        $legacyClassesDir = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR;
        $dirsToRemove = array($legacyClassesDir . "WHMCS", $legacyClassesDir . "phlyLabs");
        foreach ($dirsToRemove as $dir) {
            if (is_dir($dir)) {
                try {
                    \WHMCS\Utility\File::recursiveDelete($dir);
                } catch (\Exception $e) {
                }
            }
        }
        return $this;
    }
}

?>