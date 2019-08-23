<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version720release1 extends IncrementalVersion
{
    protected $updateActions = array("renameClientIdToUseridInTblHostingAddons");
    protected function renameClientIdToUseridInTblHostingAddons()
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($schemaBuilder->hasColumn("tblhostingaddons", "client_id")) {
            \WHMCS\Database\Capsule::connection()->statement("ALTER TABLE tblhostingaddons CHANGE client_id userid int(10) NOT NULL DEFAULT '0'");
        }
    }
}

?>