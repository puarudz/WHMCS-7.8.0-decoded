<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Backups;

class Backups
{
    public function getActiveProviders()
    {
        $activeBackupSystems = \WHMCS\Config\Setting::getValue("ActiveBackupSystems");
        $activeBackupSystems = explode(",", $activeBackupSystems);
        $activeBackupSystems = array_filter($activeBackupSystems);
        return $activeBackupSystems;
    }
}

?>