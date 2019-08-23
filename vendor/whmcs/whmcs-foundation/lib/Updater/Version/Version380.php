<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version380 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        $query = "ALTER TABLE `tblcustomfields` DROP `num` ;";
        $result = mysql_query($query);
        mysql_query("INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('EmailCSS', 'body,td { font-family: verdana; font-size: 11px; font-weight: normal; }\na { color: #0000ff; }')");
        mysql_import_file("upgrade380.sql");
        $query = "SELECT DISTINCT gid FROM tblproductconfigoptions";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $productconfigoptionspid = $data["gid"];
            $query = "INSERT INTO tblproductconfiggroups (id,name,description) VALUES ('" . $productconfigoptionspid . "','Default Options','For product ID " . $productconfigoptionspid . " - created by upgrade script')";
            $result2 = mysql_query($query);
            $query = "INSERT INTO tblproductconfiglinks (gid,pid) VALUES ('" . $productconfigoptionspid . "','" . $productconfigoptionspid . "')";
            $result2 = mysql_query($query);
        }
        return $this;
    }
}

?>