<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Plesk_Utils
{
    public static function getAccountsCount($userId)
    {
        $hostingAccounts = WHMCS\Database\Capsule::table("tblhosting")->join("tblservers", "tblservers.id", "=", "tblhosting.server")->where("tblhosting.userid", $userId)->where("tblservers.type", "plesk")->whereIn("tblhosting.domainstatus", array("Active", "Suspended", "Pending"))->count();
        $hostingAddonAccounts = WHMCS\Database\Capsule::table("tblhostingaddons")->join("tblservers", "tblhostingaddons.server", "=", "tblservers.id")->where("tblhostingaddons.userid", $userId)->where("tblservers.type", "plesk")->whereIn("status", array("Active", "Suspended", "Pending"))->count();
        return $hostingAccounts + $hostingAddonAccounts;
    }
}

?>