<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class UpdateEnvironment
{
    public static function initEnvironment($updateTempDir)
    {
        $environmentErrors = array();
        if (empty($updateTempDir) || !is_dir($updateTempDir)) {
            $environmentErrors[] = \AdminLang::trans("update.missingUpdateTempDir");
        } else {
            if (!is_writable($updateTempDir)) {
                $environmentErrors[] = \AdminLang::trans("update.updateTempDirNotWritable");
            }
        }
        return $environmentErrors;
    }
}

?>